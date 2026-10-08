<?php

namespace App\Services;

use App\Events\StaffScheduleUpdated;
use App\Models\Personnel;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PersonnelPendingApprovalService
{
    public function __construct(
        protected PersonnelTabelNoGeneratorService $tabelNoGenerator
    ) {}

    /**
     * Hazırkı vəzifəni əmək fəaliyyəti cədvəlinə cari qeyd kimi yazır (artıq varsa
     * toxunmur). Təsdiq gözləməyən — birbaşa əlavə edilən — işçi üçün də çağırılır ki,
     * staj və kartdakı "Əmək fəaliyyəti" hər iki yolda eyni olsun.
     */
    public function ensureCurrentLaborActivity(Personnel $personnel, string $joinDate): void
    {
        $personnel->loadMissing(['position:id,name', 'structure:id,coefficient']);

        $hasCurrentLabor = $personnel->laborActivities()
            ->where('is_current', true)
            ->whereNull('leave_date')
            ->exists();

        if ($hasCurrentLabor) {
            return;
        }

        $personnel->laborActivities()->create([
            'company_name' => (string) config('app.company', ''),
            'position' => (string) ($personnel->position?->name ?? ''),
            'coefficient' => $personnel->structure?->coefficient,
            'join_date' => $joinDate,
            'is_special_service' => true,
            'is_current' => true,
        ]);
    }

    public function approve(Personnel $personnel): void
    {
        DB::transaction(function () use ($personnel) {
            $personnel->refresh();

            if (! $personnel->is_pending) {
                return;
            }

            $joinDate = $personnel->join_work_date
                ? Carbon::parse($personnel->join_work_date)->format('Y-m-d')
                : now()->format('Y-m-d');

            $resolvedTabelNo = $this->tabelNoGenerator->resolveForApprovedPersonnel($personnel, $joinDate);

            $personnel->update([
                'is_pending' => false,
                'join_work_date' => $joinDate,
                'tabel_no' => $resolvedTabelNo,
            ]);

            $this->ensureCurrentLaborActivity($personnel, $joinDate);

            DB::afterCommit(function () use ($personnel) {
                if ($personnel->structure_id && $personnel->position_id) {
                    event(new StaffScheduleUpdated(
                        structure_id: (int) $personnel->structure_id,
                        position_id: (int) $personnel->position_id
                    ));
                }

                app(NotificationCampaignDispatcher::class)->dispatchEmploymentStarted($personnel, [
                    'event_source' => 'pending_personnel_approved',
                ]);
            });
        });
    }
}

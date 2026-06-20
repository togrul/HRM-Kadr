<?php

namespace App\Modules\TrainingNeeds\Application\Services;

use App\Models\TrainingNeedItem;
use App\Models\TrainingSession;
use App\Models\TrainingSessionParticipant;
use Carbon\CarbonImmutable;

class TrainingSessionParticipantService
{
    public function autoFillFromNeeds(TrainingSession $session): int
    {
        if (! $session->training_program_id) {
            return 0;
        }

        $needs = TrainingNeedItem::query()
            ->whereIn('status', ['approved', 'planned'])
            ->where('recommended_program_id', $session->training_program_id)
            ->when($session->plan, function ($query) use ($session) {
                $query->where(function ($nested) use ($session) {
                    $nested->whereYear('target_completion_date', $session->plan->plan_year)
                        ->orWhereYear('created_at', $session->plan->plan_year);
                });

                if ($session->plan->plan_quarter) {
                    $quarterStart = CarbonImmutable::create($session->plan->plan_year, (($session->plan->plan_quarter - 1) * 3) + 1, 1)->startOfDay();
                    $quarterEnd = $quarterStart->endOfQuarter()->endOfDay();

                    $query->where(function ($nested) use ($quarterStart, $quarterEnd) {
                        $nested->whereBetween('target_completion_date', [$quarterStart, $quarterEnd])
                            ->orWhere(function ($fallback) use ($quarterStart, $quarterEnd) {
                                $fallback->whereNull('target_completion_date')
                                    ->whereBetween('created_at', [$quarterStart, $quarterEnd]);
                            });
                    });
                }
            })
            ->get(['id', 'personnel_id']);

        $count = 0;

        foreach ($needs as $need) {
            TrainingSessionParticipant::query()->updateOrCreate(
                [
                    'training_session_id' => $session->id,
                    'personnel_id' => $need->personnel_id,
                ],
                [
                    'training_need_item_id' => $need->id,
                    'attendance_status' => 'planned',
                ]
            );

            $count++;
        }

        return $count;
    }
}

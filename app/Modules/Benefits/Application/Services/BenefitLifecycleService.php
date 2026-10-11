<?php

namespace App\Modules\Benefits\Application\Services;

use App\Models\BenefitEnrollment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * İşdən çıxma və tarix keçidləri.
 *
 * Xitam əmri (Orders → TerminationEffect) əməkdaşın kartında `leave_work_date`-i yazır; ləğv
 * olunanda onu təmizləyir. Bu modul Orders-ə və ya EmployeeLifecycle-ə asılılıq qoymur — Personnel
 * modelinin `updated` hadisəsində (provider-də qeydiyyat) həmin sahənin dəyişməsinə reaksiya verir
 * və gündəlik `benefits:daily` əmri buraxılmış halları (məs. birbaşa SQL ilə yazılmış tarix) tutur:
 *
 *  - xitam tarixi qoyuldu  → həmin tarixdən sonra davam edən qoşulmalar xitam günü bitir
 *    (əvvəlki bitmə tarixi yadda saxlanır); xitamdan sonra başlamalı olanlar ləğv olunur;
 *  - xitam ləğv olundu     → yalnız xitamın bitirdiyi qoşulmalar əvvəlki vəziyyətinə qayıdır.
 */
class BenefitLifecycleService
{
    public const REASON_TERMINATION = 'termination';

    public function __construct(private readonly BenefitPayrollSync $payroll) {}

    public function employmentEnded(string $tabelNo, string $leaveDate): int
    {
        $leave = CarbonImmutable::parse($leaveDate)->startOfDay();
        $today = CarbonImmutable::today();
        $changed = 0;

        $enrollments = BenefitEnrollment::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>', $leave->toDateString()))
            ->get();

        DB::transaction(function () use ($enrollments, $leave, $today, &$changed): void {
            foreach ($enrollments as $enrollment) {
                $start = CarbonImmutable::parse($enrollment->start_date);

                if ($start->gt($leave)) {
                    $enrollment->update([
                        'status' => BenefitEnrollment::STATUS_CANCELLED,
                        'end_reason' => self::REASON_TERMINATION,
                        'end_date_before_termination' => $enrollment->end_date?->toDateString(),
                    ]);
                    $this->payroll->withdrawImputed($enrollment);
                } else {
                    $enrollment->update([
                        'end_date_before_termination' => $enrollment->end_date?->toDateString(),
                        'end_date' => $leave->toDateString(),
                        'end_reason' => self::REASON_TERMINATION,
                        'status' => $leave->lt($today) ? BenefitEnrollment::STATUS_ENDED : BenefitEnrollment::STATUS_ACTIVE,
                    ]);
                }

                $changed++;
            }
        });

        if ($changed > 0) {
            $this->payroll->syncEmployee($tabelNo);
        }

        return $changed;
    }

    /**
     * Xitam ləğv olundu: həmin xitamın ($previousLeaveDate) bitirdiyi qoşulmalar bərpa olunur.
     */
    public function employmentRestored(string $tabelNo, ?string $previousLeaveDate): int
    {
        $today = CarbonImmutable::today();
        $changed = 0;

        $enrollments = BenefitEnrollment::query()
            ->where('tabel_no', $tabelNo)
            ->where('end_reason', self::REASON_TERMINATION)
            ->when($previousLeaveDate !== null, fn ($query) => $query->where(fn ($inner) => $inner
                ->whereDate('end_date', CarbonImmutable::parse((string) $previousLeaveDate)->toDateString())
                ->orWhere('status', BenefitEnrollment::STATUS_CANCELLED)))
            ->get();

        foreach ($enrollments as $enrollment) {
            $end = $enrollment->end_date_before_termination;

            $enrollment->update([
                'end_date' => $end?->toDateString(),
                'end_date_before_termination' => null,
                'end_reason' => $end !== null ? 'manual' : null,
                'status' => $end !== null && CarbonImmutable::parse($end)->lt($today) ? BenefitEnrollment::STATUS_ENDED : BenefitEnrollment::STATUS_ACTIVE,
            ]);

            $changed++;
        }

        if ($changed > 0) {
            $this->payroll->syncEmployee($tabelNo);
        }

        return $changed;
    }

    /**
     * Gündəlik keçidlər: bitmə tarixi keçmiş qoşulmalar «ended» olur; kartında xitam tarixi olan,
     * amma qoşulması hələ davam edən əməkdaşlar tutulur.
     *
     * @return array{ended: int, terminated: int, tabel_nos: list<string>}
     */
    public function applyDueTransitions(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $touched = [];

        $due = BenefitEnrollment::query()
            ->where('status', BenefitEnrollment::STATUS_ACTIVE)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today->toDateString())
            ->get();

        foreach ($due as $enrollment) {
            $enrollment->update(['status' => BenefitEnrollment::STATUS_ENDED]);
            $touched[$enrollment->tabel_no] = true;
        }

        $terminated = 0;
        $leavers = DB::table('benefit_enrollments')
            ->join('personnels', 'personnels.tabel_no', '=', 'benefit_enrollments.tabel_no')
            ->where('benefit_enrollments.status', BenefitEnrollment::STATUS_ACTIVE)
            ->whereNotNull('personnels.leave_work_date')
            ->where(fn ($query) => $query->whereNull('benefit_enrollments.end_date')
                ->orWhereColumn('benefit_enrollments.end_date', '>', 'personnels.leave_work_date'))
            ->distinct()
            ->get(['personnels.tabel_no', 'personnels.leave_work_date']);

        foreach ($leavers as $leaver) {
            $terminated += $this->employmentEnded((string) $leaver->tabel_no, (string) $leaver->leave_work_date);
            unset($touched[(string) $leaver->tabel_no]);
        }

        return ['ended' => $due->count(), 'terminated' => $terminated, 'tabel_nos' => array_map('strval', array_keys($touched))];
    }
}

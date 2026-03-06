<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceDailyStructureSummary;
use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceException;
use App\Models\AttendanceManualEntry;
use App\Models\AttendanceOvertimeRequest;
use App\Models\AttendanceRawPunch;
use Carbon\Carbon;

class AttendanceOverviewService
{
    /**
     * Foundation phase overview.
     * Real metrics will be read from attendance ledger tables in Phase B/C.
     *
     * @return array<string,int>
     */
    public function build(int $year, int $month, ?int $structureId = null, bool $useCache = true): array
    {
        $cache = app(AttendanceCacheService::class);

        if (! $useCache) {
            return $this->buildUncached($year, $month, $structureId);
        }

        return $cache->rememberOverview(
            year: $year,
            month: $month,
            structureId: $structureId,
            resolver: fn () => $this->buildUncached($year, $month, $structureId)
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function buildUncached(int $year, int $month, ?int $structureId = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $previousStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $days = (int) $start->diffInDays($end) + 1;
        $weekendDays = 0;

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekend()) {
                $weekendDays++;
            }

            $cursor->addDay();
        }

        $workdays = max(0, $days - $weekendDays);

        $summaryAgg = AttendanceDailyStructureSummary::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when(
                $structureId !== null,
                fn ($query) => $query->where('structure_id', $structureId)
            )
            ->selectRaw('COALESCE(SUM(scheduled_minutes_sum),0) as scheduled_sum')
            ->selectRaw('COALESCE(SUM(overtime_minutes_sum),0) as overtime_sum')
            ->selectRaw('COALESCE(SUM(worked_minutes_sum),0) as worked_sum')
            ->selectRaw('COALESCE(SUM(scheduled_days),0) as scheduled_days')
            ->selectRaw('COALESCE(SUM(absence_days),0) as absence_days')
            ->selectRaw('COALESCE(SUM(compliant_days),0) as compliant_days')
            ->first();

        $summaryRows = AttendanceDailyStructureSummary::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when(
                $structureId !== null,
                fn ($query) => $query->where('structure_id', $structureId)
            )
            ->count();

        $useSummary = $summaryRows > 0;

        $ledgerAgg = AttendanceDailyLedger::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COALESCE(SUM(scheduled_minutes),0) as scheduled_sum')
            ->selectRaw('COALESCE(SUM(overtime_minutes),0) as overtime_sum')
            ->selectRaw('COALESCE(SUM(worked_minutes),0) as worked_sum')
            ->when(
                $structureId !== null,
                fn ($query) => $query->whereIn(
                    'tabel_no',
                    fn ($q) => $q->from('personnels')
                        ->select('tabel_no')
                        ->where('structure_id', $structureId)
                )
            )
            ->first();

        $ledgerCounts = AttendanceDailyLedger::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('COUNT(*) as ledger_rows')
            ->selectRaw('COALESCE(SUM(CASE WHEN scheduled_minutes > 0 THEN 1 ELSE 0 END),0) as scheduled_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN attendance_status IN ("absent","manual_absence") THEN 1 ELSE 0 END),0) as absence_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN scheduled_minutes > 0 AND attendance_status NOT IN ("absent","manual_absence") AND late_minutes = 0 AND early_leave_minutes = 0 THEN 1 ELSE 0 END),0) as compliant_days')
            ->when(
                $structureId !== null,
                fn ($query) => $query->whereIn(
                    'tabel_no',
                    fn ($q) => $q->from('personnels')
                        ->select('tabel_no')
                        ->where('structure_id', $structureId)
                )
            )
            ->first();

        $summaryPreviousOvertimeMinutes = (int) AttendanceDailyStructureSummary::query()
            ->whereBetween('date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->when(
                $structureId !== null,
                fn ($query) => $query->where('structure_id', $structureId)
            )
            ->sum('overtime_minutes_sum');

        $ledgerPreviousOvertimeMinutes = (int) AttendanceDailyLedger::query()
            ->whereBetween('date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->when(
                $structureId !== null,
                fn ($query) => $query->whereIn(
                    'tabel_no',
                    fn ($q) => $q->from('personnels')
                        ->select('tabel_no')
                        ->where('structure_id', $structureId)
                )
            )
            ->sum('overtime_minutes');

        $previousOvertimeMinutes = $summaryPreviousOvertimeMinutes > 0
            ? $summaryPreviousOvertimeMinutes
            : $ledgerPreviousOvertimeMinutes;

        $manualPending = AttendanceManualEntry::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('approval_status', 'pending')
            ->when(
                $structureId !== null,
                fn ($query) => $query->whereIn(
                    'tabel_no',
                    fn ($q) => $q->from('personnels')
                        ->select('tabel_no')
                        ->where('structure_id', $structureId)
                )
            )
            ->count();

        $rawPending = AttendanceRawPunch::query()
            ->whereBetween('punched_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->where('is_processed', false)
            ->when(
                $structureId !== null,
                fn ($query) => $query->whereIn(
                    'tabel_no',
                    fn ($q) => $q->from('personnels')
                        ->select('tabel_no')
                        ->where('structure_id', $structureId)
                )
            )
            ->count();

        $openExceptions = AttendanceException::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'open')
            ->when(
                $structureId !== null,
                fn ($query) => $query->whereIn(
                    'tabel_no',
                    fn ($q) => $q->from('personnels')
                        ->select('tabel_no')
                        ->where('structure_id', $structureId)
                )
            )
            ->count();

        $pendingOvertime = AttendanceOvertimeRequest::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'pending')
            ->when(
                $structureId !== null,
                fn ($query) => $query->whereIn(
                    'tabel_no',
                    fn ($q) => $q->from('personnels')
                        ->select('tabel_no')
                        ->where('structure_id', $structureId)
                )
            )
            ->count();

        $scheduledMinutes = (int) ($useSummary ? ($summaryAgg?->scheduled_sum ?? 0) : ($ledgerAgg?->scheduled_sum ?? 0));
        $overtimeMinutes = (int) ($useSummary ? ($summaryAgg?->overtime_sum ?? 0) : ($ledgerAgg?->overtime_sum ?? 0));
        $workedMinutes = (int) ($useSummary ? ($summaryAgg?->worked_sum ?? 0) : ($ledgerAgg?->worked_sum ?? 0));
        $scheduledDays = (int) ($useSummary ? ($summaryAgg?->scheduled_days ?? 0) : ($ledgerCounts?->scheduled_days ?? 0));
        $absenceDays = (int) ($useSummary ? ($summaryAgg?->absence_days ?? 0) : ($ledgerCounts?->absence_days ?? 0));
        $compliantDays = (int) ($useSummary ? ($summaryAgg?->compliant_days ?? 0) : ($ledgerCounts?->compliant_days ?? 0));

        if ($scheduledMinutes === 0 && $workedMinutes === 0 && $overtimeMinutes === 0) {
            $scheduledMinutes = $workdays * 9 * 60;
        }

        $coveragePct = $scheduledMinutes > 0
            ? round(min(100, ($workedMinutes / $scheduledMinutes) * 100), 1)
            : 0.0;

        $absenceRatePct = $scheduledDays > 0
            ? round(min(100, ($absenceDays / $scheduledDays) * 100), 1)
            : 0.0;

        $compliancePct = $scheduledDays > 0
            ? round(min(100, ($compliantDays / $scheduledDays) * 100), 1)
            : 0.0;

        $overtimeTrendPct = 0.0;
        if ($previousOvertimeMinutes > 0) {
            $overtimeTrendPct = round((($overtimeMinutes - $previousOvertimeMinutes) / $previousOvertimeMinutes) * 100, 1);
        } elseif ($overtimeMinutes > 0) {
            $overtimeTrendPct = 100.0;
        }

        $trendDirection = $overtimeTrendPct > 0
            ? 'up'
            : ($overtimeTrendPct < 0 ? 'down' : 'flat');

        return [
            'workdays' => $workdays,
            'holidays' => $weekendDays,
            'scheduled_minutes' => $scheduledMinutes,
            'worked_minutes' => $workedMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'manual_pending_count' => $manualPending,
            'raw_pending_count' => $rawPending,
            'open_exception_count' => $openExceptions,
            'pending_overtime_count' => $pendingOvertime,
            'kpi' => [
                'coverage_pct' => $coveragePct,
                'absence_rate_pct' => $absenceRatePct,
                'compliance_pct' => $compliancePct,
                'overtime_trend_pct' => $overtimeTrendPct,
                'overtime_trend_direction' => $trendDirection,
                'overtime_previous_minutes' => $previousOvertimeMinutes,
                'scheduled_days' => $scheduledDays,
                'absence_days' => $absenceDays,
                'compliant_days' => $compliantDays,
            ],
        ];
    }
}

<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceCalendar;
use App\Models\AttendanceDailyStructureSummary;
use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceException;
use App\Models\AttendanceManualEntry;
use App\Models\AttendanceOvertimeRequest;
use App\Models\AttendanceRawPunch;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AttendanceOverviewService
{
    /**
     * Foundation phase overview.
     * Real metrics will be read from attendance ledger tables in Phase B/C.
     *
     * @return array<string,int>
     */
    public function build(
        int $year,
        int $month,
        ?int $structureId = null,
        bool $useCache = true,
        ?array $structureIds = null
    ): array
    {
        $cache = app(AttendanceCacheService::class);

        $normalizedStructureIds = $this->normalizeStructureIds($structureId, $structureIds);

        if (! $useCache) {
            return $this->buildUncached($year, $month, $structureId, $normalizedStructureIds);
        }

        return $cache->rememberOverview(
            year: $year,
            month: $month,
            structureId: $structureId,
            resolver: fn () => $this->buildUncached($year, $month, $structureId, $normalizedStructureIds)
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function buildUncached(int $year, int $month, ?int $structureId = null, array $structureIds = []): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $previousStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $previousStart->copy()->endOfMonth();

        [$workdays, $weekendDays] = $this->resolveDayCounts($start, $end, $structureId);

        $summaryQuery = $this->summaryQuery($start, $end, $structureIds);
        $summaryAgg = (clone $summaryQuery)
            ->selectRaw('COALESCE(SUM(scheduled_minutes_sum),0) as scheduled_sum')
            ->selectRaw('COALESCE(SUM(overtime_minutes_sum),0) as overtime_sum')
            ->selectRaw('COALESCE(SUM(worked_minutes_sum),0) as worked_sum')
            ->selectRaw('COALESCE(SUM(scheduled_days),0) as scheduled_days')
            ->selectRaw('COALESCE(SUM(absence_days),0) as absence_days')
            ->selectRaw('COALESCE(SUM(compliant_days),0) as compliant_days')
            ->first();

        $summaryRows = (clone $summaryQuery)->count();

        $useSummary = $summaryRows > 0;

        $ledgerAgg = null;
        $ledgerCounts = null;

        if (! $useSummary) {
            $ledgerQuery = $this->ledgerQuery($start, $end, $structureIds);

            $ledgerAgg = (clone $ledgerQuery)
                ->selectRaw('COALESCE(SUM(scheduled_minutes),0) as scheduled_sum')
                ->selectRaw('COALESCE(SUM(overtime_minutes),0) as overtime_sum')
                ->selectRaw('COALESCE(SUM(worked_minutes),0) as worked_sum')
                ->first();

            $ledgerCounts = (clone $ledgerQuery)
                ->selectRaw('COUNT(*) as ledger_rows')
                ->selectRaw('COALESCE(SUM(CASE WHEN scheduled_minutes > 0 THEN 1 ELSE 0 END),0) as scheduled_days')
                ->selectRaw('COALESCE(SUM(CASE WHEN attendance_status IN ("absent","manual_absence") THEN 1 ELSE 0 END),0) as absence_days')
                ->selectRaw('COALESCE(SUM(CASE WHEN scheduled_minutes > 0 AND attendance_status NOT IN ("absent","manual_absence") AND late_minutes = 0 AND early_leave_minutes = 0 THEN 1 ELSE 0 END),0) as compliant_days')
                ->first();
        }

        $previousSummaryQuery = $this->summaryQuery($previousStart, $previousEnd, $structureIds);
        $previousSummaryRows = (clone $previousSummaryQuery)->count();

        if ($previousSummaryRows > 0) {
            $previousOvertimeMinutes = (int) (clone $previousSummaryQuery)->sum('overtime_minutes_sum');
        } else {
            $previousOvertimeMinutes = (int) (clone $this->ledgerQuery($previousStart, $previousEnd, $structureIds))->sum('overtime_minutes');
        }

        $scopedTabelNos = $this->scopedTabelNosSubquery($structureIds);

        $manualPending = AttendanceManualEntry::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('approval_status', 'pending')
            ->when($scopedTabelNos !== null, fn ($query) => $query->whereIn('tabel_no', $scopedTabelNos))
            ->count();

        $rawPending = AttendanceRawPunch::query()
            ->whereBetween('punched_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->where('is_processed', false)
            ->when($scopedTabelNos !== null, fn ($query) => $query->whereIn('tabel_no', $scopedTabelNos))
            ->count();

        $openExceptions = AttendanceException::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'open')
            ->when($scopedTabelNos !== null, fn ($query) => $query->whereIn('tabel_no', $scopedTabelNos))
            ->count();

        $pendingOvertime = AttendanceOvertimeRequest::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('status', 'pending')
            ->when($scopedTabelNos !== null, fn ($query) => $query->whereIn('tabel_no', $scopedTabelNos))
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

    /**
     * @param  array<int,int>  $structureIds
     */
    private function summaryQuery(Carbon $start, Carbon $end, array $structureIds = []): \Illuminate\Database\Eloquent\Builder
    {
        return AttendanceDailyStructureSummary::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when(
                $structureIds !== [],
                fn ($query) => $query->whereIn('structure_id', $structureIds)
            );
    }

    /**
     * @param  array<int,int>  $structureIds
     */
    private function ledgerQuery(Carbon $start, Carbon $end, array $structureIds = []): \Illuminate\Database\Eloquent\Builder
    {
        $scopedTabelNos = $this->scopedTabelNosSubquery($structureIds);

        return AttendanceDailyLedger::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($scopedTabelNos !== null, fn ($query) => $query->whereIn('tabel_no', $scopedTabelNos));
    }

    /**
     * @param  array<int,int>  $structureIds
     */
    private function scopedTabelNosSubquery(array $structureIds = []): ?Builder
    {
        if ($structureIds === []) {
            return null;
        }

        return DB::table('personnels')
            ->select('tabel_no')
            ->whereIn('structure_id', $structureIds);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveDayCounts(Carbon $start, Carbon $end, ?int $structureId = null): array
    {
        $calendarRows = AttendanceCalendar::query()
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->where(function ($query) use ($structureId): void {
                $query->where('scope_type', 'global');

                if ($structureId !== null) {
                    $query->orWhere(function ($q) use ($structureId): void {
                        $q->where('scope_type', 'structure')
                            ->where('scope_id', $structureId);
                    });
                }
            })
            ->get(['date', 'day_type', 'scope_type', 'scope_id']);

        $globalMap = [];
        $structureMap = [];

        foreach ($calendarRows as $row) {
            $dateKey = $row->date?->toDateString();
            if (! $dateKey) {
                continue;
            }

            if ($row->scope_type === 'structure' && $row->scope_id !== null) {
                $structureMap[$dateKey] = (string) $row->day_type;
                continue;
            }

            $globalMap[$dateKey] = (string) $row->day_type;
        }

        $workdays = 0;
        $nonWorkdays = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $dateKey = $cursor->toDateString();
            $dayType = $structureMap[$dateKey] ?? $globalMap[$dateKey] ?? ($cursor->isWeekend() ? 'weekend' : 'workday');

            if ($dayType === 'workday') {
                $workdays++;
            } else {
                $nonWorkdays++;
            }

            $cursor->addDay();
        }

        return [$workdays, $nonWorkdays];
    }

    /**
     * @param  array<int,int>|null  $structureIds
     * @return array<int,int>
     */
    private function normalizeStructureIds(?int $structureId, ?array $structureIds): array
    {
        $normalized = collect($structureIds ?? ($structureId !== null ? [$structureId] : []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $normalized;
    }
}

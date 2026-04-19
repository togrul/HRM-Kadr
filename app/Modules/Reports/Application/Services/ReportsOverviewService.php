<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\PerformanceForm;
use App\Models\PerformanceTrainingNeedLink;
use App\Models\Personnel;
use App\Models\Structure;
use App\Models\TrainingDeliveryRecord;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportsOverviewService
{
    public function __construct(
        protected ReportsStructureScopeService $structureScope
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function build(int $year, int $month, ?int $structureId = null, int $trendWindow = 6): array
    {
        $reportDate = CarbonImmutable::create($year, $month, 1)->endOfMonth();
        $yearStart = $reportDate->startOfYear();
        $structureIds = $this->structureScope->resolveIds($structureId);
        $personnelSnapshot = $this->personnelSnapshot($yearStart->toDateString(), $reportDate->toDateString(), $structureIds);
        $activePersonnelCount = (int) ($personnelSnapshot?->active_personnel_count ?? 0);
        $structuresCovered = (int) ($personnelSnapshot?->structures_covered ?? 0);
        $newHires = (int) ($personnelSnapshot?->new_hires ?? 0);
        $exits = (int) ($personnelSnapshot?->exits ?? 0);

        $attendance = $this->attendanceReportKpis($year, $month, $structureIds);
        $training = $this->trainingKpis($year, $structureIds);
        $performance = $this->performanceKpis($structureIds);

        $topStructures = Structure::query()
            ->selectRaw('structures.id, structures.name, COUNT(personnels.id) as personnel_count')
            ->join('personnels', 'personnels.structure_id', '=', 'structures.id')
            ->where('personnels.is_pending', false)
            ->whereNull('personnels.deleted_at')
            ->whereDate('personnels.join_work_date', '<=', $reportDate->toDateString())
            ->where(function (Builder $query) use ($reportDate): void {
                $query->whereNull('personnels.leave_work_date')
                    ->orWhereDate('personnels.leave_work_date', '>=', $reportDate->toDateString());
            })
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structures.id', $structureIds))
            ->groupBy('structures.id', 'structures.name')
            ->orderByDesc('personnel_count')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->name,
                'value' => (int) $row->personnel_count,
            ]);

        return [
            'kpis' => [
                'active_personnel_count' => $activePersonnelCount,
                'structures_covered' => $structuresCovered,
                'new_hires' => $newHires,
                'exits' => $exits,
                'attendance_coverage_pct' => (float) data_get($attendance, 'kpi.coverage_pct', 0),
                'attendance_absence_rate_pct' => (float) data_get($attendance, 'kpi.absence_rate_pct', 0),
                'delivered_trainings_count' => (int) ($training['delivered_trainings_count'] ?? 0),
                'training_attended_hours' => (float) ($training['attended_hours'] ?? 0),
                'performance_forms_count' => (int) ($performance->forms_count ?? 0),
                'performance_weak_links_count' => (int) ($performance->weak_links_count ?? 0),
            ],
            'headcount_trend' => $this->headcountTrend($reportDate, $trendWindow, $structureIds),
            'top_structures' => $topStructures,
            'movement_snapshot' => [
                ['label' => __('reports::dashboard.overview.cards.new_hires'), 'value' => $newHires],
                ['label' => __('reports::dashboard.overview.cards.exits'), 'value' => $exits],
            ],
        ];
    }

    protected function activePersonnelQueryAt(\DateTimeInterface|string $date, array $structureIds = []): Builder
    {
        return Personnel::query()
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->whereDate('join_work_date', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('leave_work_date')
                    ->orWhereDate('leave_work_date', '>=', $date);
            })
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds));
    }

    protected function personnelSnapshot(string $yearStart, string $reportDate, array $structureIds = []): object
    {
        $activeCondition = 'join_work_date <= ? AND (leave_work_date IS NULL OR leave_work_date >= ?)';

        return Personnel::query()
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->selectRaw("SUM(CASE WHEN {$activeCondition} THEN 1 ELSE 0 END) as active_personnel_count", [$reportDate, $reportDate])
            ->selectRaw("COUNT(DISTINCT CASE WHEN {$activeCondition} THEN structure_id END) as structures_covered", [$reportDate, $reportDate])
            ->selectRaw('SUM(CASE WHEN join_work_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_hires', [$yearStart, $reportDate])
            ->selectRaw('SUM(CASE WHEN leave_work_date IS NOT NULL AND leave_work_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as exits', [$yearStart, $reportDate])
            ->first();
    }

    /**
     * @return array<int,array{label:string,value:int}>
     */
    protected function headcountTrend(CarbonImmutable $reportDate, int $window, array $structureIds = []): array
    {
        $window = in_array($window, [6, 12], true) ? $window : 6;
        $startMonth = $reportDate->startOfMonth()->subMonthsNoOverflow($window - 1)->startOfMonth();
        $baselineDate = $startMonth->subDay();
        $months = collect(range(0, $window - 1))
            ->map(fn (int $offset) => $startMonth->addMonthsNoOverflow($offset))
            ->values();

        $baseline = $this->activePersonnelQueryAt($baselineDate->toDateString(), $structureIds)
            ->selectRaw('? as metric_year, ? as metric_month, COUNT(*) as total', [
                (int) $baselineDate->year,
                (int) $baselineDate->month,
            ])
            ->selectRaw('? as movement_type', ['baseline']);

        $joins = Personnel::query()
            ->selectRaw($this->yearMonthSelect('join_work_date').', COUNT(*) as total')
            ->selectRaw('? as movement_type', ['join'])
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->whereBetween('join_work_date', [$startMonth->toDateString(), $reportDate->toDateString()])
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->groupBy('metric_year', 'metric_month');

        $exits = Personnel::query()
            ->selectRaw($this->yearMonthSelect('leave_work_date').', COUNT(*) as total')
            ->selectRaw('? as movement_type', ['exit'])
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->whereNotNull('leave_work_date')
            ->whereBetween('leave_work_date', [$startMonth->toDateString(), $reportDate->toDateString()])
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->groupBy('metric_year', 'metric_month');

        $movements = DB::query()
            ->fromSub($baseline->unionAll($joins)->unionAll($exits), 'personnel_movements')
            ->get()
            ->groupBy('movement_type');

        $baselineCount = (int) data_get($movements->get('baseline', collect())->first(), 'total', 0);

        $joinsByMonth = $movements
            ->get('join', collect())
            ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', $row->metric_year, $row->metric_month) => (int) $row->total]);

        $exitsByMonth = $movements
            ->get('exit', collect())
            ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', $row->metric_year, $row->metric_month) => (int) $row->total]);

        $running = $baselineCount;

        return $months
            ->map(function (CarbonImmutable $date) use (&$running, $joinsByMonth, $exitsByMonth): array {
                $key = $date->format('Y-m');
                $running += (int) ($joinsByMonth[$key] ?? 0);
                $running -= (int) ($exitsByMonth[$key] ?? 0);

                return [
                    'label' => $this->shortMonthLabel((int) $date->month),
                    'value' => max(0, $running),
                ];
            })
            ->all();
    }

    /**
     * @return array{coverage_pct:float,absence_rate_pct:float}
     */
    protected function attendanceReportKpis(int $year, int $month, array $structureIds = []): array
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();

        $summary = DB::table('attendance_daily_structure_summaries')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($structureIds !== [], fn ($query) => $query->whereIn('structure_id', $structureIds))
            ->selectRaw('COUNT(*) as summary_rows')
            ->selectRaw('COALESCE(SUM(scheduled_minutes_sum),0) as scheduled_sum')
            ->selectRaw('COALESCE(SUM(worked_minutes_sum),0) as worked_sum')
            ->selectRaw('COALESCE(SUM(scheduled_days),0) as scheduled_days')
            ->selectRaw('COALESCE(SUM(absence_days),0) as absence_days')
            ->first();

        if ((int) ($summary?->summary_rows ?? 0) > 0) {
            return $this->attendanceKpiPayload(
                (int) ($summary?->scheduled_sum ?? 0),
                (int) ($summary?->worked_sum ?? 0),
                (int) ($summary?->scheduled_days ?? 0),
                (int) ($summary?->absence_days ?? 0)
            );
        }

        $ledger = DB::table('attendance_daily_ledgers')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($structureIds !== [], function ($query) use ($structureIds): void {
                $query->whereIn('tabel_no', DB::table('personnels')->select('tabel_no')->whereIn('structure_id', $structureIds));
            })
            ->selectRaw('COALESCE(SUM(scheduled_minutes),0) as scheduled_sum')
            ->selectRaw('COALESCE(SUM(worked_minutes),0) as worked_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN scheduled_minutes > 0 THEN 1 ELSE 0 END),0) as scheduled_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN attendance_status IN ("absent","manual_absence") THEN 1 ELSE 0 END),0) as absence_days')
            ->first();

        return $this->attendanceKpiPayload(
            (int) ($ledger?->scheduled_sum ?? 0),
            (int) ($ledger?->worked_sum ?? 0),
            (int) ($ledger?->scheduled_days ?? 0),
            (int) ($ledger?->absence_days ?? 0)
        );
    }

    /**
     * @return array{coverage_pct:float,absence_rate_pct:float}
     */
    protected function attendanceKpiPayload(int $scheduledMinutes, int $workedMinutes, int $scheduledDays, int $absenceDays): array
    {
        return [
            'coverage_pct' => $scheduledMinutes > 0
                ? round(min(100, ($workedMinutes / $scheduledMinutes) * 100), 1)
                : 0.0,
            'absence_rate_pct' => $scheduledDays > 0
                ? round(min(100, ($absenceDays / $scheduledDays) * 100), 1)
                : 0.0,
        ];
    }

    protected function shortMonthLabel(int $month): string
    {
        return (string) __("reports::dashboard.months_short.{$month}");
    }

    protected function yearMonthSelect(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER) as metric_year, CAST(strftime('%m', {$column}) AS INTEGER) as metric_month",
            default => "YEAR({$column}) as metric_year, MONTH({$column}) as metric_month",
        };
    }

    /**
     * @return array{delivered_trainings_count:int,attended_hours:float}
     */
    protected function trainingKpis(int $year, array $structureIds = []): array
    {
        $query = TrainingDeliveryRecord::query()
            ->join('personnels', 'personnels.id', '=', 'training_delivery_records.personnel_id')
            ->whereYear('training_delivery_records.completed_at', $year)
            ->when($structureIds !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $structureIds));

        $row = $query
            ->selectRaw('COUNT(DISTINCT training_delivery_records.training_session_id) as delivered_trainings_count')
            ->selectRaw('COALESCE(SUM(training_delivery_records.attended_hours), 0) as attended_hours')
            ->first();

        return [
            'delivered_trainings_count' => (int) ($row?->delivered_trainings_count ?? 0),
            'attended_hours' => round((float) ($row?->attended_hours ?? 0), 1),
        ];
    }

    protected function performanceKpis(array $structureIds = []): object
    {
        $weakLinks = PerformanceTrainingNeedLink::query()
            ->join('performance_forms', 'performance_forms.id', '=', 'performance_training_need_links.performance_form_id')
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->when($structureIds !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $structureIds))
            ->selectRaw('COUNT(*)');

        return PerformanceForm::query()
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->when($structureIds !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $structureIds))
            ->selectRaw('COUNT(*) as forms_count')
            ->selectSub($weakLinks, 'weak_links_count')
            ->first();
    }
}

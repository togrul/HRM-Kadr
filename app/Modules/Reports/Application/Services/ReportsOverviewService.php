<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\PerformanceForm;
use App\Models\PerformanceTrainingNeedLink;
use App\Models\Personnel;
use App\Models\Structure;
use App\Models\TrainingDeliveryRecord;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReportsOverviewService
{
    public function __construct(
        protected ReportsStructureScopeService $structureScope,
        protected AttendanceOverviewService $attendanceOverview
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
        $basePersonnel = $this->activePersonnelQueryAt($reportDate->toDateString(), $structureIds);

        $activePersonnelCount = (clone $basePersonnel)->count();
        $structuresCovered = (clone $basePersonnel)->distinct('structure_id')->count('structure_id');
        $newHires = Personnel::query()
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->whereBetween('join_work_date', [$yearStart->toDateString(), $reportDate->toDateString()])
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->count();
        $exits = Personnel::query()
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->whereNotNull('leave_work_date')
            ->whereBetween('leave_work_date', [$yearStart->toDateString(), $reportDate->toDateString()])
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->count();

        $attendance = $this->attendanceOverview->build($year, $month, $structureId, true, $structureIds);
        $training = $this->trainingKpis($year, $structureIds);

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
                'performance_forms_count' => $this->performanceFormsCount($structureIds),
                'performance_weak_links_count' => $this->performanceWeakLinksCount($structureIds),
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

        $baselineCount = $this->activePersonnelQueryAt($baselineDate->toDateString(), $structureIds)->count();

        $joins = Personnel::query()
            ->selectRaw($this->yearMonthSelect('join_work_date').', COUNT(*) as total')
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->whereBetween('join_work_date', [$startMonth->toDateString(), $reportDate->toDateString()])
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->groupBy('metric_year', 'metric_month')
            ->get()
            ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', $row->metric_year, $row->metric_month) => (int) $row->total]);

        $exits = Personnel::query()
            ->selectRaw($this->yearMonthSelect('leave_work_date').', COUNT(*) as total')
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->whereNotNull('leave_work_date')
            ->whereBetween('leave_work_date', [$startMonth->toDateString(), $reportDate->toDateString()])
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->groupBy('metric_year', 'metric_month')
            ->get()
            ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', $row->metric_year, $row->metric_month) => (int) $row->total]);

        $running = $baselineCount;

        return $months
            ->map(function (CarbonImmutable $date) use (&$running, $joins, $exits): array {
                $key = $date->format('Y-m');
                $running += (int) ($joins[$key] ?? 0);
                $running -= (int) ($exits[$key] ?? 0);

                return [
                    'label' => $this->shortMonthLabel((int) $date->month),
                    'value' => max(0, $running),
                ];
            })
            ->all();
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

        return [
            'delivered_trainings_count' => (clone $query)->distinct('training_delivery_records.training_session_id')->count('training_delivery_records.training_session_id'),
            'attended_hours' => round((float) ((clone $query)->sum('training_delivery_records.attended_hours') ?: 0), 1),
        ];
    }

    protected function performanceFormsCount(array $structureIds = []): int
    {
        return PerformanceForm::query()
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->when($structureIds !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $structureIds))
            ->count();
    }

    protected function performanceWeakLinksCount(array $structureIds = []): int
    {
        return PerformanceTrainingNeedLink::query()
            ->join('performance_forms', 'performance_forms.id', '=', 'performance_training_need_links.performance_form_id')
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->when($structureIds !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $structureIds))
            ->count();
    }
}

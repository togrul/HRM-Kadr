<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\PerformanceForm;
use App\Models\Personnel;
use App\Models\TrainingDeliveryRecord;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ComparativeReportService
{
    public function __construct(
        protected ReportsStructureScopeService $structureScope,
        protected AttendanceOverviewService $attendanceOverview
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function build(int $year, int $month, ?int $structureId = null): array
    {
        $structureIds = $this->structureScope->resolveIds($structureId);

        return [
            'headcount_years' => $this->headcountYearComparison($year, $structureIds),
            'attendance_months' => $this->attendanceMonthComparison($year, $month, $structureId, $structureIds),
            'training_years' => $this->trainingYearComparison($year, $structureIds),
            'performance_distribution' => $this->performanceDistribution($year, $month, $structureIds),
        ];
    }

    protected function headcountYearComparison(int $year, array $structureIds = []): array
    {
        $currentDate = Carbon::create($year, 12, 31)->endOfDay()->toDateString();
        $previousDate = Carbon::create($year - 1, 12, 31)->endOfDay()->toDateString();
        $snapshot = Personnel::query()
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->selectRaw('SUM(CASE WHEN join_work_date <= ? AND (leave_work_date IS NULL OR leave_work_date >= ?) THEN 1 ELSE 0 END) as current_count', [$currentDate, $currentDate])
            ->selectRaw('SUM(CASE WHEN join_work_date <= ? AND (leave_work_date IS NULL OR leave_work_date >= ?) THEN 1 ELSE 0 END) as previous_count', [$previousDate, $previousDate])
            ->first();

        return [
            ['label' => (string) ($year - 1), 'value' => (int) ($snapshot?->previous_count ?? 0)],
            ['label' => (string) $year, 'value' => (int) ($snapshot?->current_count ?? 0)],
        ];
    }

    protected function attendanceMonthComparison(int $year, int $month, ?int $structureId, array $structureIds = []): array
    {
        $current = $this->attendanceOverview->build($year, $month, $structureId, true, $structureIds);
        $previousMonth = Carbon::createFromDate($year, $month, 1)->subMonthNoOverflow();
        $previous = $this->attendanceOverview->build((int) $previousMonth->year, (int) $previousMonth->month, $structureId, true, $structureIds);

        return [
            [
                'label' => $previousMonth->translatedFormat('F Y'),
                'coverage_pct' => (float) data_get($previous, 'kpi.coverage_pct', 0),
                'absence_rate_pct' => (float) data_get($previous, 'kpi.absence_rate_pct', 0),
            ],
            [
                'label' => Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y'),
                'coverage_pct' => (float) data_get($current, 'kpi.coverage_pct', 0),
                'absence_rate_pct' => (float) data_get($current, 'kpi.absence_rate_pct', 0),
            ],
        ];
    }

    protected function trainingYearComparison(int $year, array $structureIds = []): array
    {
        return TrainingDeliveryRecord::query()
            ->join('personnels', 'personnels.id', '=', 'training_delivery_records.personnel_id')
            ->selectRaw($this->reportYearSelect('training_delivery_records.completed_at'))
            ->selectRaw('COUNT(DISTINCT training_delivery_records.training_session_id) as sessions_count')
            ->selectRaw('ROUND(COALESCE(SUM(training_delivery_records.attended_hours), 0), 1) as attended_hours')
            ->whereYear('training_delivery_records.completed_at', '<=', $year)
            ->whereYear('training_delivery_records.completed_at', '>=', $year - 3)
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('personnels.structure_id', $structureIds))
            ->groupBy('report_year')
            ->orderBy('report_year')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->report_year,
                'sessions_count' => (int) $row->sessions_count,
                'attended_hours' => round((float) $row->attended_hours, 1),
            ])
            ->values()
            ->all();
    }

    protected function performanceDistribution(int $year, int $month, array $structureIds = []): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return PerformanceForm::query()
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
            ->selectRaw('COALESCE(final_category, "unknown") as final_category')
            ->selectRaw('COUNT(*) as forms_count')
            ->where(function (Builder $query) use ($periodStart, $periodEnd): void {
                $query
                    ->where(function (Builder $cycleQuery) use ($periodStart, $periodEnd): void {
                        $cycleQuery->whereNotNull('performance_cycles.id')
                            ->whereDate('performance_cycles.period_start', '<=', $periodEnd->toDateString())
                            ->whereDate('performance_cycles.period_end', '>=', $periodStart->toDateString());
                    })
                    ->orWhere(function (Builder $fallbackQuery) use ($periodStart, $periodEnd): void {
                        $fallbackQuery->whereNull('performance_cycles.id')
                            ->whereBetween('performance_forms.created_at', [$periodStart->startOfDay(), $periodEnd->endOfDay()]);
                    });
            })
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('personnels.structure_id', $structureIds))
            ->groupBy('final_category')
            ->orderByDesc('forms_count')
            ->get()
            ->map(fn ($row) => [
                'label' => __('reports::dashboard.dynamic.performance_categories.'.$row->final_category),
                'value' => (int) $row->forms_count,
            ])
            ->values()
            ->all();
    }

    protected function reportYearSelect(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', {$column}) AS INTEGER) as report_year",
            default => "YEAR({$column}) as report_year",
        };
    }
}

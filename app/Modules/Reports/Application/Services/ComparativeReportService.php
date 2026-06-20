<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\PerformanceForm;
use App\Models\Personnel;
use App\Models\TrainingDeliveryRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ComparativeReportService
{
    public function __construct(
        protected ReportsStructureScopeService $structureScope
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
        $currentMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $previousMonth = Carbon::createFromDate($year, $month, 1)->subMonthNoOverflow();
        $kpis = $this->attendanceKpisForMonths($previousMonth, $currentMonth, $structureIds);

        return [
            [
                'label' => $previousMonth->translatedFormat('F Y'),
                'coverage_pct' => (float) data_get($kpis, $previousMonth->format('Y-m').'.coverage_pct', 0),
                'absence_rate_pct' => (float) data_get($kpis, $previousMonth->format('Y-m').'.absence_rate_pct', 0),
            ],
            [
                'label' => $currentMonth->translatedFormat('F Y'),
                'coverage_pct' => (float) data_get($kpis, $currentMonth->format('Y-m').'.coverage_pct', 0),
                'absence_rate_pct' => (float) data_get($kpis, $currentMonth->format('Y-m').'.absence_rate_pct', 0),
            ],
        ];
    }

    /**
     * @param  array<int,int>  $structureIds
     * @return array<string,array{coverage_pct:float,absence_rate_pct:float}>
     */
    protected function attendanceKpisForMonths(Carbon $previousMonth, Carbon $currentMonth, array $structureIds = []): array
    {
        $from = $previousMonth->copy()->startOfMonth();
        $to = $currentMonth->copy()->endOfMonth();
        $monthSelect = $this->reportMonthSelect('date');

        $summaryRows = DB::table('attendance_daily_structure_summaries')
            ->selectRaw("{$monthSelect} as report_month")
            ->selectRaw('COUNT(*) as source_rows')
            ->selectRaw('COALESCE(SUM(scheduled_minutes_sum), 0) as scheduled_minutes')
            ->selectRaw('COALESCE(SUM(worked_minutes_sum), 0) as worked_minutes')
            ->selectRaw('COALESCE(SUM(scheduled_days), 0) as scheduled_days')
            ->selectRaw('COALESCE(SUM(absence_days), 0) as absence_days')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($structureIds !== [], fn ($query) => $query->whereIn('structure_id', $structureIds))
            ->groupBy('report_month')
            ->get()
            ->keyBy('report_month');

        if ($summaryRows->isEmpty()) {
            $ledgerRows = DB::table('attendance_daily_ledgers')
                ->selectRaw("{$monthSelect} as report_month")
                ->selectRaw('COUNT(*) as source_rows')
                ->selectRaw('COALESCE(SUM(scheduled_minutes), 0) as scheduled_minutes')
                ->selectRaw('COALESCE(SUM(worked_minutes), 0) as worked_minutes')
                ->selectRaw('COALESCE(SUM(CASE WHEN scheduled_minutes > 0 THEN 1 ELSE 0 END), 0) as scheduled_days')
                ->selectRaw('COALESCE(SUM(CASE WHEN attendance_status IN ("absent", "manual_absence") THEN 1 ELSE 0 END), 0) as absence_days')
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->when($structureIds !== [], function ($query) use ($structureIds): void {
                    $query->whereIn('tabel_no', DB::table('personnels')
                        ->select('tabel_no')
                        ->whereIn('structure_id', $structureIds));
                })
                ->groupBy('report_month')
                ->get()
                ->keyBy('report_month');
        } else {
            $ledgerRows = collect();
        }

        $rows = $summaryRows->isNotEmpty() ? $summaryRows : $ledgerRows;

        return collect([$previousMonth, $currentMonth])
            ->mapWithKeys(function (Carbon $month) use ($rows): array {
                $key = $month->format('Y-m');
                $row = $rows->get($key);
                $scheduledMinutes = (int) ($row?->scheduled_minutes ?? 0);
                $workedMinutes = (int) ($row?->worked_minutes ?? 0);
                $scheduledDays = (int) ($row?->scheduled_days ?? 0);
                $absenceDays = (int) ($row?->absence_days ?? 0);

                return [$key => [
                    'coverage_pct' => $scheduledMinutes > 0
                        ? round(min(100, ($workedMinutes / $scheduledMinutes) * 100), 1)
                        : 0.0,
                    'absence_rate_pct' => $scheduledDays > 0
                        ? round(min(100, ($absenceDays / $scheduledDays) * 100), 1)
                        : 0.0,
                ]];
            })
            ->all();
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

    protected function reportMonthSelect(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}

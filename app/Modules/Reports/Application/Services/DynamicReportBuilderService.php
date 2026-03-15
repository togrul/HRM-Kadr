<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\AttendanceDailyStructureSummary;
use App\Models\PerformanceForm;
use App\Models\Personnel;
use App\Models\TrainingDeliveryRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DynamicReportBuilderService
{
    public function __construct(
        protected ReportsStructureScopeService $structureScope,
        protected ReportsSqlDialectService $sql
    ) {
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    public function build(string $source, string $groupBy, string $metric, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        return match ($source) {
            'attendance' => $this->attendance($groupBy, $metric, $normalized),
            'training' => $this->training($groupBy, $metric, $normalized),
            'performance' => $this->performance($groupBy, $metric, $normalized),
            default => $this->personnel($groupBy, $metric, $normalized),
        };
    }

    /**
     * @return array<int,array{key:string,label:string}>
     */
    public function sourceOptions(): array
    {
        return [
            ['key' => 'personnel', 'label' => __('reports::dashboard.dynamic.sources.personnel')],
            ['key' => 'attendance', 'label' => __('reports::dashboard.dynamic.sources.attendance')],
            ['key' => 'training', 'label' => __('reports::dashboard.dynamic.sources.training')],
            ['key' => 'performance', 'label' => __('reports::dashboard.dynamic.sources.performance')],
        ];
    }

    /**
     * @return array<int,array{key:string,label:string}>
     */
    public function groupOptions(string $source): array
    {
        return match ($source) {
            'attendance' => [
                ['key' => 'structure', 'label' => __('reports::dashboard.dynamic.groups.structure')],
                ['key' => 'month', 'label' => __('reports::dashboard.dynamic.groups.month')],
            ],
            'training' => [
                ['key' => 'quarter', 'label' => __('reports::dashboard.dynamic.groups.quarter')],
                ['key' => 'delivery_type', 'label' => __('reports::dashboard.dynamic.groups.delivery_type')],
            ],
            'performance' => [
                ['key' => 'cycle', 'label' => __('reports::dashboard.dynamic.groups.cycle')],
                ['key' => 'template', 'label' => __('reports::dashboard.dynamic.groups.template')],
                ['key' => 'category', 'label' => __('reports::dashboard.dynamic.groups.category')],
            ],
            default => [
                ['key' => 'structure', 'label' => __('reports::dashboard.dynamic.groups.structure')],
                ['key' => 'position', 'label' => __('reports::dashboard.dynamic.groups.position')],
                ['key' => 'gender', 'label' => __('reports::dashboard.dynamic.groups.gender')],
                ['key' => 'status', 'label' => __('reports::dashboard.dynamic.groups.status')],
            ],
        };
    }

    /**
     * @return array<int,array{key:string,label:string}>
     */
    public function metricOptions(string $source): array
    {
        return match ($source) {
            'attendance' => [
                ['key' => 'worked_hours', 'label' => __('reports::dashboard.dynamic.metrics.worked_hours')],
                ['key' => 'overtime_hours', 'label' => __('reports::dashboard.dynamic.metrics.overtime_hours')],
                ['key' => 'absence_days', 'label' => __('reports::dashboard.dynamic.metrics.absence_days')],
            ],
            'training' => [
                ['key' => 'sessions_count', 'label' => __('reports::dashboard.dynamic.metrics.sessions_count')],
                ['key' => 'participants_count', 'label' => __('reports::dashboard.dynamic.metrics.participants_count')],
                ['key' => 'attended_hours', 'label' => __('reports::dashboard.dynamic.metrics.attended_hours')],
            ],
            'performance' => [
                ['key' => 'forms_count', 'label' => __('reports::dashboard.dynamic.metrics.forms_count')],
                ['key' => 'average_score', 'label' => __('reports::dashboard.dynamic.metrics.average_score')],
            ],
            default => [
                ['key' => 'count', 'label' => __('reports::dashboard.dynamic.metrics.count')],
            ],
        };
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function personnel(string $groupBy, string $metric, array $filters): array
    {
        $reportDate = $this->reportDate($filters);
        $query = ($groupBy === 'status'
            ? $this->personnelStatusQueryAt($reportDate->toDateString(), $filters['structure_ids'])
            : $this->activePersonnelQueryAt($reportDate->toDateString(), $filters['structure_ids']))
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->when($groupBy !== 'status', fn (Builder $builder) => $builder->where('personnels.is_pending', false));

        [$groupExpr, $label] = match ($groupBy) {
            'position' => ['COALESCE(positions.name, "—")', __('reports::dashboard.fields.position')],
            'gender' => ['CASE WHEN personnels.gender = 2 THEN "'.__('reports::dashboard.labels.female').'" ELSE "'.__('reports::dashboard.labels.male').'" END', __('reports::dashboard.fields.gender')],
            'status' => ['CASE WHEN personnels.leave_work_date IS NULL OR personnels.leave_work_date >= "'.$reportDate->toDateString().'" THEN "'.__('reports::dashboard.labels.active').'" ELSE "'.__('reports::dashboard.labels.terminated').'" END', __('reports::dashboard.fields.status')],
            default => ['COALESCE(structures.name, "'.__('reports::dashboard.labels.unassigned').'")', __('reports::dashboard.fields.structure')],
        };

        $rows = $query
            ->selectRaw("{$groupExpr} as group_label")
            ->selectRaw('COUNT(*) as metric_value')
            ->groupBy('group_label')
            ->orderByDesc('metric_value')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'group_label' => (string) $row->group_label,
                'metric_value' => (int) $row->metric_value,
            ]);

        return $this->payload(
            __('reports::dashboard.dynamic.sources.personnel'),
            $label,
            __('reports::dashboard.dynamic.metrics.count'),
            $rows
        );
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function attendance(string $groupBy, string $metric, array $filters): array
    {
        $month = (int) $filters['month'];
        $year = (int) $filters['year'];

        $query = AttendanceDailyStructureSummary::query()
            ->leftJoin('structures', 'structures.id', '=', 'attendance_daily_structure_summaries.structure_id')
            ->when($filters['structure_ids'] !== [], fn ($builder) => $builder->whereIn('attendance_daily_structure_summaries.structure_id', $filters['structure_ids']));

        if ($groupBy === 'month') {
            $groupExpr = $this->sql->monthExpression('attendance_daily_structure_summaries.date');
            $groupLabel = __('reports::dashboard.fields.month');
            $query->whereYear('attendance_daily_structure_summaries.date', $year);
        } else {
            $groupExpr = 'COALESCE(structures.name, "'.__('reports::dashboard.labels.unassigned').'")';
            $groupLabel = __('reports::dashboard.fields.structure');
            $query->whereYear('attendance_daily_structure_summaries.date', $year)
                ->whereMonth('attendance_daily_structure_summaries.date', $month);
        }

        $metricExpr = match ($metric) {
            'overtime_hours' => 'ROUND(SUM(overtime_minutes_sum) / 60, 1)',
            'absence_days' => 'SUM(absence_days)',
            default => 'ROUND(SUM(worked_minutes_sum) / 60, 1)',
        };

        $metricLabel = __('reports::dashboard.dynamic.metrics.'.$metric);

        $rows = $query
            ->selectRaw("{$groupExpr} as group_label")
            ->selectRaw("{$metricExpr} as metric_value")
            ->groupBy('group_label')
            ->orderByDesc('metric_value')
            ->limit(15)
            ->get()
            ->map(function ($row) use ($groupBy) {
                $label = $groupBy === 'month'
                    ? Carbon::create()->month((int) $row->group_label)->translatedFormat('F')
                    : (string) $row->group_label;

                return [
                    'group_label' => $label,
                    'metric_value' => (float) $row->metric_value,
                ];
            });

        return $this->payload(__('reports::dashboard.dynamic.sources.attendance'), $groupLabel, $metricLabel, $rows);
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function training(string $groupBy, string $metric, array $filters): array
    {
        if ($groupBy === 'delivery_type') {
            $metricExpr = match ($metric) {
                'participants_count' => 'COUNT(*)',
                'attended_hours' => 'ROUND(COALESCE(SUM(training_delivery_records.attended_hours), 0), 1)',
                default => 'COUNT(DISTINCT training_delivery_records.training_session_id)',
            };

            $rows = TrainingDeliveryRecord::query()
                ->join('personnels', 'personnels.id', '=', 'training_delivery_records.personnel_id')
                ->leftJoin('training_programs', 'training_programs.id', '=', 'training_delivery_records.training_program_id')
                ->selectRaw('COALESCE(training_programs.delivery_type, "internal") as group_label')
                ->selectRaw("{$metricExpr} as metric_value")
                ->whereYear('training_delivery_records.completed_at', (int) $filters['year'])
                ->when($filters['structure_ids'] !== [], fn (Builder $query) => $query->whereIn('personnels.structure_id', $filters['structure_ids']))
                ->groupBy('group_label')
                ->orderByDesc('metric_value')
                ->get()
                ->map(fn ($row) => [
                    'group_label' => __('reports::dashboard.dynamic.training_delivery_types.'.$row->group_label),
                    'metric_value' => $metric === 'attended_hours' ? (float) $row->metric_value : (int) $row->metric_value,
                ]);

            return $this->payload(
                __('reports::dashboard.dynamic.sources.training'),
                __('reports::dashboard.dynamic.groups.delivery_type'),
                __('reports::dashboard.dynamic.metrics.'.$metric),
                $rows
            );
        }

        $quarterExpr = $this->sql->quarterExpression('training_delivery_records.completed_at');
        $metricExpr = match ($metric) {
            'participants_count' => 'COUNT(*)',
            'attended_hours' => 'ROUND(COALESCE(SUM(training_delivery_records.attended_hours), 0), 1)',
            default => 'COUNT(DISTINCT training_delivery_records.training_session_id)',
        };

        $rows = TrainingDeliveryRecord::query()
            ->join('personnels', 'personnels.id', '=', 'training_delivery_records.personnel_id')
            ->selectRaw("{$quarterExpr} as quarter_no")
            ->selectRaw("{$metricExpr} as metric_value")
            ->whereYear('training_delivery_records.completed_at', (int) $filters['year'])
            ->when($filters['structure_ids'] !== [], fn (Builder $query) => $query->whereIn('personnels.structure_id', $filters['structure_ids']))
            ->groupBy('quarter_no')
            ->orderBy('quarter_no')
            ->get()
            ->map(fn ($row) => [
                'group_label' => __('reports::dashboard.labels.quarter_label', ['quarter' => $row->quarter_no]),
                'metric_value' => $metric === 'attended_hours' ? (float) $row->metric_value : (int) $row->metric_value,
            ]);

        return $this->payload(
            __('reports::dashboard.dynamic.sources.training'),
            __('reports::dashboard.dynamic.groups.quarter'),
            __('reports::dashboard.dynamic.metrics.'.$metric),
            $rows
        );
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function performance(string $groupBy, string $metric, array $filters): array
    {
        [$periodStart, $periodEnd] = $this->reportPeriod($filters);

        if ($groupBy === 'category') {
            $rows = PerformanceForm::query()
                ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
                ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
                ->selectRaw('COALESCE(final_category, "unknown") as group_label')
                ->selectRaw($metric === 'average_score' ? 'ROUND(AVG(final_score), 2) as metric_value' : 'COUNT(*) as metric_value')
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
                ->when($filters['structure_ids'] !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $filters['structure_ids']))
                ->groupBy('group_label')
                ->orderByDesc('metric_value')
                ->get()
                ->map(fn ($row) => [
                    'group_label' => __('reports::dashboard.dynamic.performance_categories.'.$row->group_label),
                    'metric_value' => $metric === 'average_score' ? (float) $row->metric_value : (int) $row->metric_value,
                ]);

            return $this->payload(
                __('reports::dashboard.dynamic.sources.performance'),
                __('reports::dashboard.dynamic.groups.category'),
                __('reports::dashboard.dynamic.metrics.'.$metric),
                $rows
            );
        }

        $groupColumn = $groupBy === 'template' ? 'performance_form_templates.name' : 'performance_cycles.name';

        $rows = PerformanceForm::query()
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
            ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_forms.performance_form_template_id')
            ->selectRaw("COALESCE({$groupColumn}, '—') as group_label")
            ->selectRaw($metric === 'average_score' ? 'ROUND(AVG(performance_forms.final_score), 2) as metric_value' : 'COUNT(*) as metric_value')
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
            ->when($filters['structure_ids'] !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $filters['structure_ids']))
            ->groupBy('group_label')
            ->orderByDesc('metric_value')
            ->limit(15)
            ->get()
            ->map(fn ($row) => [
                'group_label' => (string) $row->group_label,
                'metric_value' => $metric === 'average_score' ? (float) $row->metric_value : (int) $row->metric_value,
            ]);

        return $this->payload(
            __('reports::dashboard.dynamic.sources.performance'),
            __('reports::dashboard.dynamic.groups.'.$groupBy),
            __('reports::dashboard.dynamic.metrics.'.$metric),
            $rows
        );
    }

    /**
     * @param  array<int,array{group_label:string,metric_value:int|float}>|\Illuminate\Support\Collection<int,array{group_label:string,metric_value:int|float}>  $rows
     * @return array<string,mixed>
     */
    protected function payload(string $title, string $groupLabel, string $metricLabel, $rows): array
    {
        $rows = collect($rows)->values();

        return [
            'title' => __('reports::dashboard.dynamic.title'),
            'description' => __('reports::dashboard.dynamic.description'),
            'columns' => [
                ['key' => 'group_label', 'label' => $groupLabel],
                ['key' => 'metric_value', 'label' => $metricLabel],
            ],
            'rows' => $rows->all(),
            'summary' => [
                ['label' => __('reports::dashboard.cards.dynamic_rows'), 'value' => $rows->count()],
                ['label' => __('reports::dashboard.cards.dynamic_total'), 'value' => round((float) $rows->sum('metric_value'), 2)],
            ],
            'chart' => $rows->take(12)->map(fn ($row) => ['label' => $row['group_label'], 'value' => $row['metric_value']])->all(),
            'resolved_source' => $title,
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function normalizeFilters(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);
        $structureId = filled($filters['structure_id'] ?? null) ? (int) $filters['structure_id'] : null;

        return [
            'year' => $year,
            'month' => max(1, min(12, $month)),
            'structure_id' => $structureId,
            'structure_ids' => $this->structureScope->resolveIds($structureId),
        ];
    }

    protected function reportDate(array $filters): Carbon
    {
        return Carbon::createFromDate((int) $filters['year'], (int) $filters['month'], 1)->endOfMonth();
    }

    /**
     * @return array{0:Carbon,1:Carbon}
     */
    protected function reportPeriod(array $filters): array
    {
        $start = Carbon::createFromDate((int) $filters['year'], (int) $filters['month'], 1)->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    protected function activePersonnelQueryAt(string $date, array $structureIds = []): Builder
    {
        return Personnel::query()
            ->where('personnels.is_pending', false)
            ->whereNull('personnels.deleted_at')
            ->whereDate('personnels.join_work_date', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('personnels.leave_work_date')
                    ->orWhereDate('personnels.leave_work_date', '>=', $date);
            })
            ->when($structureIds !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $structureIds));
    }

    protected function personnelStatusQueryAt(string $date, array $structureIds = []): Builder
    {
        return Personnel::query()
            ->where('personnels.is_pending', false)
            ->whereNull('personnels.deleted_at')
            ->whereDate('personnels.join_work_date', '<=', $date)
            ->when($structureIds !== [], fn (Builder $builder) => $builder->whereIn('personnels.structure_id', $structureIds));
    }
}

<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\AttendanceDailyStructureSummary;
use App\Models\PerformanceForm;
use App\Models\Personnel;
use App\Models\PersonnelLaborActivity;
use App\Models\TrainingDeliveryRecord;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StandardReportService
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
    public function build(string $report, array $filters = []): array
    {
        $normalized = $this->normalizeFilters($filters);

        return match ($report) {
            'demographics' => $this->demographics($normalized),
            'movements' => $this->movements($normalized),
            'attendance' => $this->attendance($normalized),
            'training' => $this->training($normalized),
            'performance' => $this->performance($normalized),
            default => $this->headcount($normalized),
        };
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function headcount(array $filters): array
    {
        $rows = $this->activePersonnelQueryAt($this->reportDate($filters)->toDateString(), $filters['structure_ids'])
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->selectRaw('COALESCE(structures.name, ?) as structure_name', [__('reports::dashboard.labels.unassigned')])
            ->selectRaw('COUNT(*) as personnel_count')
            ->selectRaw('SUM(CASE WHEN personnels.gender = 2 THEN 1 ELSE 0 END) as female_count')
            ->selectRaw('SUM(CASE WHEN personnels.gender = 1 THEN 1 ELSE 0 END) as male_count')
            ->groupBy('structures.name')
            ->orderByDesc('personnel_count')
            ->get()
            ->map(fn ($row) => [
                'structure_name' => (string) $row->structure_name,
                'personnel_count' => (int) $row->personnel_count,
                'female_count' => (int) $row->female_count,
                'male_count' => (int) $row->male_count,
            ]);

        $total = $rows->sum('personnel_count');
        $female = $rows->sum('female_count');

        return [
            'title' => __('reports::dashboard.standard.types.headcount'),
            'description' => __('reports::dashboard.standard.descriptions.headcount'),
            'columns' => [
                ['key' => 'structure_name', 'label' => __('reports::dashboard.fields.structure')],
                ['key' => 'personnel_count', 'label' => __('reports::dashboard.fields.personnel_count')],
                ['key' => 'female_count', 'label' => __('reports::dashboard.fields.female_count')],
                ['key' => 'male_count', 'label' => __('reports::dashboard.fields.male_count')],
            ],
            'rows' => $rows->values()->all(),
            'summary' => [
                ['label' => __('reports::dashboard.cards.active_personnel'), 'value' => $total],
                ['label' => __('reports::dashboard.cards.structures_covered'), 'value' => $rows->count()],
                ['label' => __('reports::dashboard.cards.female_share'), 'value' => $total > 0 ? round(($female / $total) * 100, 1).'%' : '0%'],
            ],
            'chart' => $rows->take(8)->map(fn ($row) => ['label' => $row['structure_name'], 'value' => $row['personnel_count']])->values()->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function demographics(array $filters): array
    {
        $base = $this->activePersonnelQueryAt($this->reportDate($filters)->toDateString(), $filters['structure_ids'])->toBase();
        $age = $this->sql->ageYearsExpression('birthdate');
        $tenure = $this->sql->tenureYearsExpression('join_work_date');

        $genderRows = DB::query()
            ->fromSub(clone $base, 'personnel_gender')
            ->selectRaw('? as dimension', [__('reports::dashboard.fields.gender_distribution')])
            ->selectRaw("CASE WHEN gender = 2 THEN ? ELSE ? END as bucket", [__('reports::dashboard.labels.female'), __('reports::dashboard.labels.male')])
            ->selectRaw('COUNT(*) as employee_count')
            ->groupBy('bucket')
            ->get();

        $ageRows = DB::query()
            ->fromSub(clone $base, 'personnel_age')
            ->selectRaw('? as dimension', [__('reports::dashboard.fields.age_distribution')])
            ->selectRaw("
                CASE
                    WHEN {$age} BETWEEN 18 AND 25 THEN '18-25'
                    WHEN {$age} BETWEEN 26 AND 35 THEN '26-35'
                    WHEN {$age} BETWEEN 36 AND 45 THEN '36-45'
                    WHEN {$age} BETWEEN 46 AND 55 THEN '46-55'
                    ELSE '56+'
                END as bucket
            ")
            ->selectRaw('COUNT(*) as employee_count')
            ->groupBy('bucket')
            ->get();

        $tenureRows = DB::query()
            ->fromSub(clone $base, 'personnel_tenure')
            ->selectRaw('? as dimension', [__('reports::dashboard.fields.experience_distribution')])
            ->selectRaw("
                CASE
                    WHEN {$tenure} <= 1 THEN '0-1'
                    WHEN {$tenure} BETWEEN 2 AND 5 THEN '2-5'
                    WHEN {$tenure} BETWEEN 6 AND 10 THEN '6-10'
                    ELSE '11+'
                END as bucket
            ")
            ->selectRaw('COUNT(*) as employee_count')
            ->groupBy('bucket')
            ->get();

        $rows = collect()
            ->merge($genderRows)
            ->merge($ageRows)
            ->merge($tenureRows)
            ->map(fn ($row) => [
                'dimension' => (string) $row->dimension,
                'bucket' => (string) $row->bucket,
                'employee_count' => (int) $row->employee_count,
            ])
            ->values();

        return [
            'title' => __('reports::dashboard.standard.types.demographics'),
            'description' => __('reports::dashboard.standard.descriptions.demographics'),
            'columns' => [
                ['key' => 'dimension', 'label' => __('reports::dashboard.fields.dimension')],
                ['key' => 'bucket', 'label' => __('reports::dashboard.fields.bucket')],
                ['key' => 'employee_count', 'label' => __('reports::dashboard.fields.personnel_count')],
            ],
            'rows' => $rows->all(),
            'summary' => [
                ['label' => __('reports::dashboard.cards.total_buckets'), 'value' => $rows->count()],
                ['label' => __('reports::dashboard.cards.active_personnel'), 'value' => $rows->sum('employee_count')],
            ],
            'chart' => $rows->where('dimension', __('reports::dashboard.fields.gender_distribution'))
                ->map(fn ($row) => ['label' => $row['bucket'], 'value' => $row['employee_count']])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function movements(array $filters): array
    {
        $year = (int) $filters['year'];
        $structureIds = $filters['structure_ids'];
        $monthExpr = $this->sql->monthExpression('join_work_date');
        $leaveMonthExpr = $this->sql->monthExpression('leave_work_date');
        $orderMonthExpr = $this->sql->monthExpression('order_date');

        $hireRows = Personnel::query()
            ->selectRaw("{$monthExpr} as month_no")
            ->selectRaw('COUNT(*) as joined_count')
            ->whereYear('join_work_date', $year)
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->groupBy('month_no')
            ->pluck('joined_count', 'month_no');

        $exitRows = Personnel::query()
            ->selectRaw("{$leaveMonthExpr} as month_no")
            ->selectRaw('COUNT(*) as exits_count')
            ->whereYear('leave_work_date', $year)
            ->whereNotNull('leave_work_date')
            ->where('is_pending', false)
            ->whereNull('deleted_at')
            ->when($structureIds !== [], fn (Builder $query) => $query->whereIn('structure_id', $structureIds))
            ->groupBy('month_no')
            ->pluck('exits_count', 'month_no');

        $movementRows = PersonnelLaborActivity::query()
            ->join('personnels', 'personnels.tabel_no', '=', 'personnel_labor_activities.tabel_no')
            ->selectRaw("{$orderMonthExpr} as month_no")
            ->selectRaw('COUNT(*) as movement_count')
            ->whereYear('order_date', $year)
            ->when($structureIds !== [], fn ($query) => $query->whereIn('personnels.structure_id', $structureIds))
            ->groupBy('month_no')
            ->pluck('movement_count', 'month_no');

        $rows = collect(range(1, 12))->map(function (int $month) use ($hireRows, $exitRows, $movementRows) {
            return [
                'month' => Carbon::create()->month($month)->translatedFormat('F'),
                'joined_count' => (int) ($hireRows[$month] ?? 0),
                'exits_count' => (int) ($exitRows[$month] ?? 0),
                'movement_count' => (int) ($movementRows[$month] ?? 0),
            ];
        });

        return [
            'title' => __('reports::dashboard.standard.types.movements'),
            'description' => __('reports::dashboard.standard.descriptions.movements'),
            'columns' => [
                ['key' => 'month', 'label' => __('reports::dashboard.fields.month')],
                ['key' => 'joined_count', 'label' => __('reports::dashboard.fields.joined_count')],
                ['key' => 'exits_count', 'label' => __('reports::dashboard.fields.exits_count')],
                ['key' => 'movement_count', 'label' => __('reports::dashboard.fields.position_changes_count')],
            ],
            'rows' => $rows->all(),
            'summary' => [
                ['label' => __('reports::dashboard.cards.new_hires'), 'value' => $rows->sum('joined_count')],
                ['label' => __('reports::dashboard.cards.exits'), 'value' => $rows->sum('exits_count')],
                ['label' => __('reports::dashboard.cards.position_changes'), 'value' => $rows->sum('movement_count')],
            ],
            'chart' => $rows->map(fn ($row) => ['label' => $row['month'], 'value' => $row['joined_count']])->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function attendance(array $filters): array
    {
        $start = Carbon::createFromDate($filters['year'], $filters['month'], 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = AttendanceDailyStructureSummary::query()
            ->join('structures', 'structures.id', '=', 'attendance_daily_structure_summaries.structure_id')
            ->selectRaw('structures.name as structure_name')
            ->selectRaw('SUM(ledger_rows) as personnel_count')
            ->selectRaw('SUM(absence_days) as absence_days')
            ->selectRaw('SUM(worked_minutes_sum) as worked_minutes_sum')
            ->selectRaw('SUM(overtime_minutes_sum) as overtime_minutes_sum')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when(
                $filters['structure_ids'] !== [],
                fn ($query) => $query->whereIn('structure_id', $filters['structure_ids'])
            )
            ->groupBy('structures.name')
            ->orderByDesc('worked_minutes_sum')
            ->get()
            ->map(fn ($row) => [
                'structure_name' => (string) $row->structure_name,
                'personnel_count' => (int) $row->personnel_count,
                'absence_days' => (int) $row->absence_days,
                'worked_hours' => round(((int) $row->worked_minutes_sum) / 60, 1),
                'overtime_hours' => round(((int) $row->overtime_minutes_sum) / 60, 1),
            ]);

        return [
            'title' => __('reports::dashboard.standard.types.attendance'),
            'description' => __('reports::dashboard.standard.descriptions.attendance'),
            'columns' => [
                ['key' => 'structure_name', 'label' => __('reports::dashboard.fields.structure')],
                ['key' => 'personnel_count', 'label' => __('reports::dashboard.fields.ledger_rows')],
                ['key' => 'absence_days', 'label' => __('reports::dashboard.fields.absence_days')],
                ['key' => 'worked_hours', 'label' => __('reports::dashboard.fields.worked_hours')],
                ['key' => 'overtime_hours', 'label' => __('reports::dashboard.fields.overtime_hours')],
            ],
            'rows' => $rows->all(),
            'summary' => [
                ['label' => __('reports::dashboard.cards.absence_days'), 'value' => $rows->sum('absence_days')],
                ['label' => __('reports::dashboard.cards.worked_hours'), 'value' => round($rows->sum('worked_hours'), 1)],
                ['label' => __('reports::dashboard.cards.overtime_hours'), 'value' => round($rows->sum('overtime_hours'), 1)],
            ],
            'chart' => $rows->take(8)->map(fn ($row) => ['label' => $row['structure_name'], 'value' => $row['worked_hours']])->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function training(array $filters): array
    {
        $year = (int) $filters['year'];
        $quarterExpr = $this->sql->quarterExpression('training_delivery_records.completed_at');
        $feedbackAgg = DB::table('training_feedback_responses')
            ->selectRaw('training_session_id, ROUND(COALESCE(AVG(overall_score), 0), 2) as average_feedback_score')
            ->groupBy('training_session_id');

        $rows = TrainingDeliveryRecord::query()
            ->join('personnels', 'personnels.id', '=', 'training_delivery_records.personnel_id')
            ->leftJoinSub($feedbackAgg, 'session_feedback', function ($join): void {
                $join->on('session_feedback.training_session_id', '=', 'training_delivery_records.training_session_id');
            })
            ->selectRaw("{$quarterExpr} as quarter_no")
            ->selectRaw('COUNT(DISTINCT training_delivery_records.training_session_id) as sessions_count')
            ->selectRaw('COUNT(training_delivery_records.id) as participants_count')
            ->selectRaw('ROUND(COALESCE(SUM(training_delivery_records.attended_hours), 0), 1) as attended_hours')
            ->selectRaw('ROUND(COALESCE(AVG(session_feedback.average_feedback_score), 0), 2) as average_feedback_score')
            ->whereYear('training_delivery_records.completed_at', $year)
            ->when($filters['structure_ids'] !== [], fn (Builder $query) => $query->whereIn('personnels.structure_id', $filters['structure_ids']))
            ->groupBy('quarter_no')
            ->orderBy('quarter_no')
            ->get()
            ->map(fn ($row) => [
                'period' => __('reports::dashboard.labels.quarter_label', ['quarter' => $row->quarter_no]),
                'sessions_count' => (int) $row->sessions_count,
                'participants_count' => (int) $row->participants_count,
                'attended_hours' => round((float) $row->attended_hours, 1),
                'average_feedback_score' => round((float) $row->average_feedback_score, 2),
            ]);

        $summary = [
            'delivered_trainings_count' => $rows->sum('sessions_count'),
            'attended_hours' => round((float) $rows->sum('attended_hours'), 1),
            'attendance_rate' => $rows->sum('participants_count') > 0 ? 100 : 0,
        ];

        return [
            'title' => __('reports::dashboard.standard.types.training'),
            'description' => __('reports::dashboard.standard.descriptions.training'),
            'columns' => [
                ['key' => 'period', 'label' => __('reports::dashboard.fields.period')],
                ['key' => 'sessions_count', 'label' => __('reports::dashboard.fields.sessions_count')],
                ['key' => 'participants_count', 'label' => __('reports::dashboard.fields.participants_count')],
                ['key' => 'attended_hours', 'label' => __('reports::dashboard.fields.attended_hours')],
                ['key' => 'average_feedback_score', 'label' => __('reports::dashboard.fields.average_feedback_score')],
            ],
            'rows' => $rows->all(),
            'summary' => [
                ['label' => __('reports::dashboard.cards.delivered_trainings'), 'value' => (int) ($summary['delivered_trainings_count'] ?? 0)],
                ['label' => __('reports::dashboard.cards.attended_hours'), 'value' => (float) ($summary['attended_hours'] ?? 0)],
                ['label' => __('reports::dashboard.cards.attendance_rate'), 'value' => ($summary['attendance_rate'] ?? 0).'%' ],
            ],
            'chart' => $rows->map(fn ($row) => ['label' => $row['period'], 'value' => $row['sessions_count']])->all(),
        ];
    }

    /**
     * @param  array<string,mixed>  $filters
     * @return array<string,mixed>
     */
    protected function performance(array $filters): array
    {
        [$periodStart, $periodEnd] = $this->reportPeriod($filters);

        $rows = PerformanceForm::query()
            ->join('personnels', 'personnels.id', '=', 'performance_forms.personnel_id')
            ->leftJoin('performance_cycles', 'performance_cycles.id', '=', 'performance_forms.performance_cycle_id')
            ->leftJoin('performance_form_templates', 'performance_form_templates.id', '=', 'performance_forms.performance_form_template_id')
            ->selectRaw('COALESCE(performance_cycles.name, ?) as cycle_name', ['—'])
            ->selectRaw('COALESCE(performance_form_templates.name, ?) as template_name', ['—'])
            ->selectRaw('COUNT(*) as forms_count')
            ->selectRaw('ROUND(COALESCE(AVG(performance_forms.final_score), 0), 2) as average_score')
            ->selectRaw('SUM(CASE WHEN performance_forms.final_category = "high" THEN 1 ELSE 0 END) as high_count')
            ->selectRaw('SUM(CASE WHEN performance_forms.final_category = "medium" THEN 1 ELSE 0 END) as medium_count')
            ->selectRaw('SUM(CASE WHEN performance_forms.final_category = "weak" THEN 1 ELSE 0 END) as weak_count')
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
            ->when($filters['structure_ids'] !== [], fn (Builder $query) => $query->whereIn('personnels.structure_id', $filters['structure_ids']))
            ->groupBy('cycle_name', 'template_name')
            ->orderByDesc('forms_count')
            ->get()
            ->map(fn ($row) => [
                'cycle_name' => (string) $row->cycle_name,
                'template_name' => (string) $row->template_name,
                'forms_count' => (int) $row->forms_count,
                'average_score' => round((float) $row->average_score, 2),
                'high_count' => (int) $row->high_count,
                'medium_count' => (int) $row->medium_count,
                'weak_count' => (int) $row->weak_count,
            ]);

        return [
            'title' => __('reports::dashboard.standard.types.performance'),
            'description' => __('reports::dashboard.standard.descriptions.performance'),
            'columns' => [
                ['key' => 'cycle_name', 'label' => __('reports::dashboard.fields.cycle')],
                ['key' => 'template_name', 'label' => __('reports::dashboard.fields.template')],
                ['key' => 'forms_count', 'label' => __('reports::dashboard.fields.forms_count')],
                ['key' => 'average_score', 'label' => __('reports::dashboard.fields.average_score')],
                ['key' => 'high_count', 'label' => __('reports::dashboard.fields.high_count')],
                ['key' => 'medium_count', 'label' => __('reports::dashboard.fields.medium_count')],
                ['key' => 'weak_count', 'label' => __('reports::dashboard.fields.weak_count')],
            ],
            'rows' => $rows->all(),
            'summary' => [
                ['label' => __('reports::dashboard.cards.forms_total'), 'value' => $rows->sum('forms_count')],
                ['label' => __('reports::dashboard.cards.high_performers'), 'value' => $rows->sum('high_count')],
                ['label' => __('reports::dashboard.cards.weak_performers'), 'value' => $rows->sum('weak_count')],
            ],
            'chart' => $rows->take(8)->map(fn ($row) => ['label' => $row['template_name'], 'value' => $row['average_score']])->all(),
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

    protected function activePersonnelQueryAt(string $date, array $structureIds = []): Builder
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
}

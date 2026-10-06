<?php

namespace App\Modules\EmployeeLifecycle\Application\Services;

use App\Support\Database\InstalledTables;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LifecycleDashboardReadService
{
    public const PER_PAGE = 15;

    /** The overdue-task queue is a scrolling side list; its header shows the full count. */
    private const OVERDUE_TASK_LIMIT = 50;

    /** Rows a probation/movement/offboarding queue card shows per "show more" step. */
    public const QUEUE_PAGE = 20;

    /** Options a completion-panel select loads per search. */
    public const OPTION_LIMIT = 50;

    private const CLOSED_STATUSES = ['completed', 'cancelled'];

    /**
     * Localized event titles: key => [legacy stored title, source_type, event type].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const EVENT_TITLE_KEYS = [
        'probation_review' => ['Probation review', 'employee_lifecycle_probation_review', 'probation'],
        'internal_movement' => ['Internal movement', 'employee_lifecycle_movement', 'movement'],
        'offboarding_case' => ['Offboarding case', 'employee_lifecycle_offboarding_case', 'offboarding'],
    ];

    /**
     * @param  array{probation?: int, movement?: int, offboarding?: int}  $queueLimits  rows each queue card shows
     */
    public function dashboard(array $filters = [], int $perPage = self::PER_PAGE, array $queueLimits = []): array
    {
        $templates = $this->planTemplates();
        $totals = $this->eventTotals();
        $queues = $this->queueCounts();
        $overdueTasks = $this->overdueTasks();

        return [
            'summary' => [
                'active_templates' => $templates->where('is_active', true)->count(),
                'active_events' => $totals['active'],
                'overdue_tasks' => $overdueTasks['total'],
                'probation_queue' => $queues['probation_open'] + $totals['probation_unreviewed'],
                'movement_queue' => $queues['movement_open'],
                'offboarding_queue' => $queues['offboarding_open'] ?: $totals['offboarding'],
            ],
            'queueTotals' => [
                'probation' => $queues['probation_total'],
                'movement' => $queues['movement_total'],
                'offboarding' => $queues['offboarding_total'],
            ],
            'events' => $this->events($filters, $perPage),
            'overdueTasks' => $overdueTasks['rows'],
            // Facet counts drop their own filter and keep the others, so a selected facet
            // can always be clicked back out of.
            'typeCounts' => $this->facetCounts(['search' => $filters['search'] ?? '', 'status' => $filters['status'] ?? ''], 'type'),
            'statusCounts' => $this->facetCounts(['search' => $filters['search'] ?? '', 'type' => $filters['type'] ?? ''], 'status'),
            'planTemplates' => $templates,
            'probationReviews' => $this->probationReviews($queueLimits['probation'] ?? self::QUEUE_PAGE),
            'movements' => $this->movements($queueLimits['movement'] ?? self::QUEUE_PAGE),
            'offboardingCases' => $this->offboardingCases($queueLimits['offboarding'] ?? self::QUEUE_PAGE),
        ];
    }

    /**
     * One page of events, filtered and sorted in SQL: no deadline last, then deadline asc, then newest.
     */
    public function events(array $filters = [], int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        if (! InstalledTables::has('employee_lifecycle_events')) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return $this->filteredEvents($filters, true)
            ->select([
                'employee_lifecycle_events.id',
                'employee_lifecycle_events.type',
                'employee_lifecycle_events.status',
                'employee_lifecycle_events.title',
                'employee_lifecycle_events.description',
                'employee_lifecycle_events.effective_date',
                'employee_lifecycle_events.deadline_at',
                'employee_lifecycle_events.completed_at',
                'employee_lifecycle_events.source_type',
                'employee_lifecycle_events.source_id',
                'employee_lifecycle_events.meta',
                ...$this->personnelColumns(),
                'structures.name as structure_name',
                'positions.name as position_name',
                'owners.name as owner_name',
                'plan_templates.name as template_name',
            ])
            ->orderByRaw('case when employee_lifecycle_events.deadline_at is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_events.deadline_at')
            ->orderByDesc('employee_lifecycle_events.id')
            ->paginate($perPage)
            ->through(fn ($row): array => $this->eventRow($row));
    }

    public function planTemplates(): Collection
    {
        if (! InstalledTables::has('employee_lifecycle_plan_templates')) {
            return collect();
        }

        return DB::table('employee_lifecycle_plan_templates')
            ->leftJoin('employee_lifecycle_task_templates', 'employee_lifecycle_task_templates.plan_template_id', '=', 'employee_lifecycle_plan_templates.id')
            ->leftJoin('employee_lifecycle_events', 'employee_lifecycle_events.plan_template_id', '=', 'employee_lifecycle_plan_templates.id')
            ->select([
                'employee_lifecycle_plan_templates.id',
                'employee_lifecycle_plan_templates.name',
                'employee_lifecycle_plan_templates.type',
                'employee_lifecycle_plan_templates.description',
                'employee_lifecycle_plan_templates.default_duration_days',
                'employee_lifecycle_plan_templates.is_active',
            ])
            ->selectRaw('COUNT(DISTINCT employee_lifecycle_task_templates.id) as tasks_count')
            ->selectRaw('COUNT(DISTINCT employee_lifecycle_events.id) as events_count')
            ->groupBy([
                'employee_lifecycle_plan_templates.id',
                'employee_lifecycle_plan_templates.name',
                'employee_lifecycle_plan_templates.type',
                'employee_lifecycle_plan_templates.description',
                'employee_lifecycle_plan_templates.default_duration_days',
                'employee_lifecycle_plan_templates.is_active',
            ])
            ->orderByDesc('employee_lifecycle_plan_templates.is_active')
            ->orderBy('employee_lifecycle_plan_templates.type')
            ->orderBy('employee_lifecycle_plan_templates.name')
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'type' => (string) $row->type,
                'type_label' => __('employee-lifecycle::dashboard.types.'.$row->type),
                'description' => $row->description,
                'default_duration_days' => (int) $row->default_duration_days,
                'is_active' => (bool) $row->is_active,
                'tasks_count' => (int) $row->tasks_count,
                'events_count' => (int) $row->events_count,
            ]);
    }

    public function probationReviews(?int $limit = null): Collection
    {
        if (! InstalledTables::has('employee_lifecycle_probation_reviews')) {
            return collect();
        }

        return $this->joinPersonnel(DB::table('employee_lifecycle_probation_reviews'), 'employee_lifecycle_probation_reviews')
            ->leftJoin('users as managers', 'managers.id', '=', 'employee_lifecycle_probation_reviews.manager_user_id')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'employee_lifecycle_probation_reviews.hr_reviewer_user_id')
            ->select([
                'employee_lifecycle_probation_reviews.id',
                'employee_lifecycle_probation_reviews.event_id',
                'employee_lifecycle_probation_reviews.status',
                'employee_lifecycle_probation_reviews.decision',
                'employee_lifecycle_probation_reviews.score',
                'employee_lifecycle_probation_reviews.review_due_at',
                ...$this->personnelColumns(),
                'managers.name as manager_name',
                'reviewers.name as reviewer_name',
            ])
            ->orderByRaw('case when employee_lifecycle_probation_reviews.review_due_at is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_probation_reviews.review_due_at')
            ->orderByDesc('employee_lifecycle_probation_reviews.id')
            ->when($limit !== null, fn (Builder $query) => $query->limit($limit))
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'event_id' => (int) $row->event_id,
                'status' => (string) $row->status,
                'decision' => $row->decision,
                'score' => $row->score !== null ? (int) $row->score : null,
                'review_due_at' => $row->review_due_at,
                'is_overdue' => $row->status === 'pending' && $row->review_due_at < today()->toDateString(),
                'employee_name' => $this->personnelName($row),
                'tabel_no' => (string) $row->tabel_no,
                'manager_name' => $row->manager_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
                'reviewer_name' => $row->reviewer_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            ]);
    }

    public function movements(?int $limit = null): Collection
    {
        if (! InstalledTables::has('employee_lifecycle_movements')) {
            return collect();
        }

        return $this->joinPersonnel(DB::table('employee_lifecycle_movements'), 'employee_lifecycle_movements')
            ->leftJoin('structures as current_structures', 'current_structures.id', '=', 'employee_lifecycle_movements.current_structure_id')
            ->leftJoin('positions as current_positions', 'current_positions.id', '=', 'employee_lifecycle_movements.current_position_id')
            ->leftJoin('structures as target_structures', 'target_structures.id', '=', 'employee_lifecycle_movements.target_structure_id')
            ->leftJoin('positions as target_positions', 'target_positions.id', '=', 'employee_lifecycle_movements.target_position_id')
            ->leftJoin('users as approvers', 'approvers.id', '=', 'employee_lifecycle_movements.approved_by')
            ->select([
                'employee_lifecycle_movements.id',
                'employee_lifecycle_movements.event_id',
                'employee_lifecycle_movements.movement_type',
                'employee_lifecycle_movements.effective_date',
                'employee_lifecycle_movements.status',
                'employee_lifecycle_movements.reason',
                ...$this->personnelColumns(),
                'current_structures.name as current_structure_name',
                'current_positions.name as current_position_name',
                'target_structures.name as target_structure_name',
                'target_positions.name as target_position_name',
                'approvers.name as approver_name',
            ])
            ->orderByRaw('case when employee_lifecycle_movements.effective_date is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_movements.effective_date')
            ->orderByDesc('employee_lifecycle_movements.id')
            ->when($limit !== null, fn (Builder $query) => $query->limit($limit))
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'event_id' => (int) $row->event_id,
                'movement_type' => (string) $row->movement_type,
                'movement_type_label' => __('employee-lifecycle::dashboard.movement_types.'.$row->movement_type),
                'effective_date' => $row->effective_date,
                'status' => (string) $row->status,
                'reason' => $row->reason,
                'is_overdue' => $row->status !== 'completed' && $row->effective_date < today()->toDateString(),
                'employee_name' => $this->personnelName($row),
                'tabel_no' => (string) $row->tabel_no,
                'current_structure_name' => $row->current_structure_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
                'current_position_name' => $row->current_position_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
                'target_structure_name' => $row->target_structure_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
                'target_position_name' => $row->target_position_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
                'approver_name' => $row->approver_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            ]);
    }

    public function offboardingCases(?int $limit = null): Collection
    {
        if (! InstalledTables::has('employee_lifecycle_offboarding_cases')) {
            return collect();
        }

        return $this->joinPersonnel(DB::table('employee_lifecycle_offboarding_cases'), 'employee_lifecycle_offboarding_cases')
            ->leftJoin('structures', 'structures.id', '=', DB::raw('COALESCE(lp.structure_id, lpt.structure_id)'))
            ->leftJoin('positions', 'positions.id', '=', DB::raw('COALESCE(lp.position_id, lpt.position_id)'))
            ->leftJoin('users as owners', 'owners.id', '=', 'employee_lifecycle_offboarding_cases.owner_user_id')
            ->select([
                'employee_lifecycle_offboarding_cases.id',
                'employee_lifecycle_offboarding_cases.event_id',
                'employee_lifecycle_offboarding_cases.last_working_date',
                'employee_lifecycle_offboarding_cases.status',
                'employee_lifecycle_offboarding_cases.reason',
                'employee_lifecycle_offboarding_cases.exit_interview_completed_at',
                ...$this->personnelColumns(),
                'structures.name as structure_name',
                'positions.name as position_name',
                'owners.name as owner_name',
            ])
            ->orderByRaw('case when employee_lifecycle_offboarding_cases.last_working_date is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_offboarding_cases.last_working_date')
            ->orderByDesc('employee_lifecycle_offboarding_cases.id')
            ->when($limit !== null, fn (Builder $query) => $query->limit($limit))
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'event_id' => (int) $row->event_id,
                'last_working_date' => $row->last_working_date,
                'status' => (string) $row->status,
                'reason' => $row->reason,
                'is_overdue' => $row->status !== 'completed' && $row->last_working_date < today()->toDateString(),
                'exit_interview_done' => $row->exit_interview_completed_at !== null,
                'employee_name' => $this->personnelName($row),
                'tabel_no' => (string) $row->tabel_no,
                'structure_name' => $row->structure_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
                'position_name' => $row->position_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
                'owner_name' => $row->owner_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            ]);
    }

    /**
     * Events matching the search/type/status filters. The personnel/structure/owner joins are
     * only added when a caller selects from them or the search has to look into them.
     */
    private function filteredEvents(array $filters, bool $withContext = false): Builder
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $type = trim((string) ($filters['type'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        $query = DB::table('employee_lifecycle_events');

        if ($withContext || $search !== '') {
            $this->joinPersonnel($query, 'employee_lifecycle_events')
                ->leftJoin('structures', 'structures.id', '=', DB::raw('COALESCE(lp.structure_id, lpt.structure_id)'))
                ->leftJoin('positions', 'positions.id', '=', DB::raw('COALESCE(lp.position_id, lpt.position_id)'))
                ->leftJoin('users as owners', 'owners.id', '=', 'employee_lifecycle_events.owner_user_id')
                ->leftJoin('employee_lifecycle_plan_templates as plan_templates', 'plan_templates.id', '=', 'employee_lifecycle_events.plan_template_id');
        }

        return $query
            ->when($search !== '', fn (Builder $query) => $this->applySearch($query, $search))
            ->when($type !== '', fn (Builder $query) => $query->where('employee_lifecycle_events.type', $type))
            ->when($status !== '', fn (Builder $query) => $query->where('employee_lifecycle_events.status', $status));
    }

    /**
     * Mirrors the row's visible text: title, employee, tabel no, structure, position, owner,
     * template, plus the localized titles and the order-source label built in eventRow().
     */
    private function applySearch(Builder $query, string $search): Builder
    {
        $like = '%'.$search.'%';

        return $query->where(function (Builder $query) use ($search, $like): void {
            $query->whereRaw(
                "concat_ws(' ', employee_lifecycle_events.title, COALESCE(lp.surname, lpt.surname), COALESCE(lp.name, lpt.name), COALESCE(lp.patronymic, lpt.patronymic), COALESCE(lp.tabel_no, lpt.tabel_no), structures.name, positions.name, owners.name, plan_templates.name) like ?",
                [$like]
            );

            foreach (self::EVENT_TITLE_KEYS as $key => [$legacyTitle, $sourceType, $type]) {
                if (! str_contains(mb_strtolower(__('employee-lifecycle::dashboard.event_titles.'.$key)), $search)) {
                    continue;
                }

                $query->orWhere('employee_lifecycle_events.title', $legacyTitle)
                    ->orWhere('employee_lifecycle_events.source_type', $sourceType)
                    ->orWhere(fn (Builder $query) => $query->where('employee_lifecycle_events.title', '')->where('employee_lifecycle_events.type', $type));
            }

            $labelMatches = str_contains(mb_strtolower(__('employee-lifecycle::dashboard.labels.order_source_with_no', ['order' => ''])), $search);

            $query->orWhere(fn (Builder $query) => $query
                ->where('employee_lifecycle_events.source_type', 'like', '%order%')
                ->when(! $labelMatches, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->where('employee_lifecycle_events.meta->order_no', 'like', $like)
                    ->orWhereRaw("concat_ws('', '#', employee_lifecycle_events.source_id) like ?", [$like]))));
        });
    }

    /**
     * Replaces `personnels.id = personnel_id OR personnels.tabel_no = tabel_no`, which no index
     * can serve: join by id, and by tabel_no only when the id finds nobody.
     */
    private function joinPersonnel(Builder $query, string $table): Builder
    {
        return $query
            ->leftJoin('personnels as lp', 'lp.id', '=', $table.'.personnel_id')
            ->leftJoin('personnels as lpt', function (JoinClause $join) use ($table): void {
                $join->on('lpt.tabel_no', '=', $table.'.tabel_no')->whereNull('lp.id');
            });
    }

    /**
     * @return array<int, Expression>
     */
    private function personnelColumns(): array
    {
        return [
            DB::raw('COALESCE(lp.tabel_no, lpt.tabel_no) as tabel_no'),
            DB::raw('COALESCE(lp.surname, lpt.surname) as surname'),
            DB::raw('COALESCE(lp.name, lpt.name) as name'),
            DB::raw('COALESCE(lp.patronymic, lpt.patronymic) as patronymic'),
        ];
    }

    /**
     * Header counters in one aggregate query instead of loading every event.
     *
     * @return array{active: int, offboarding: int, probation_unreviewed: int}
     */
    private function eventTotals(): array
    {
        if (! InstalledTables::has('employee_lifecycle_events')) {
            return ['active' => 0, 'offboarding' => 0, 'probation_unreviewed' => 0];
        }

        $open = "e.status not in ('completed', 'cancelled')";
        $unreviewed = InstalledTables::has('employee_lifecycle_probation_reviews')
            ? ' and not exists (select 1 from employee_lifecycle_probation_reviews r where r.event_id = e.id)'
            : '';

        $row = DB::table('employee_lifecycle_events as e')
            ->selectRaw("coalesce(sum(case when {$open} then 1 else 0 end), 0) as active")
            ->selectRaw("coalesce(sum(case when e.type = 'offboarding' and {$open} then 1 else 0 end), 0) as offboarding")
            ->selectRaw("coalesce(sum(case when e.type = 'probation' and {$open}{$unreviewed} then 1 else 0 end), 0) as probation_unreviewed")
            ->first();

        return [
            'active' => (int) ($row->active ?? 0),
            'offboarding' => (int) ($row->offboarding ?? 0),
            'probation_unreviewed' => (int) ($row->probation_unreviewed ?? 0),
        ];
    }

    /**
     * Queue-card counters in one query of sub-selects: all rows (for "show more") and the
     * open ones — pending reviews, movements/cases not completed or cancelled.
     *
     * @return array{probation_total: int, probation_open: int, movement_total: int, movement_open: int, offboarding_total: int, offboarding_open: int}
     */
    private function queueCounts(): array
    {
        $queues = [
            'probation' => ['employee_lifecycle_probation_reviews', fn (Builder $query) => $query->where('status', 'pending')],
            'movement' => ['employee_lifecycle_movements', fn (Builder $query) => $query->whereNotIn('status', self::CLOSED_STATUSES)],
            'offboarding' => ['employee_lifecycle_offboarding_cases', fn (Builder $query) => $query->whereNotIn('status', self::CLOSED_STATUSES)],
        ];

        $counts = [];
        $query = DB::query();
        foreach ($queues as $key => [$table, $open]) {
            $counts[$key.'_total'] = 0;
            $counts[$key.'_open'] = 0;

            if (InstalledTables::has($table)) {
                $query->selectSub(DB::table($table)->selectRaw('count(*)'), $key.'_total')
                    ->selectSub($open(DB::table($table))->selectRaw('count(*)'), $key.'_open');
            }
        }

        if ($query->columns === null) {
            return $counts;
        }

        foreach ((array) $query->first() as $column => $count) {
            $counts[$column] = (int) $count;
        }

        return $counts;
    }

    /**
     * Completion-panel options for probation reviews: "employee · due date", searched in SQL,
     * limited, with the selected review always present.
     *
     * @return array<int, array{id: int, label: string}>
     */
    public function probationReviewOptions(string $search = '', ?int $selectedId = null, int $limit = self::OPTION_LIMIT): array
    {
        return $this->completionOptions(
            'employee_lifecycle_probation_reviews',
            'review_due_at',
            fn (object $row): string => $this->personnelName($row).' · '.$row->review_due_at,
            $search,
            $selectedId,
            $limit,
        );
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    public function movementOptions(string $search = '', ?int $selectedId = null, int $limit = self::OPTION_LIMIT): array
    {
        $typeMatches = collect(['transfer', 'promotion', 'role_change'])
            ->filter(fn (string $type): bool => $search !== '' && str_contains(mb_strtolower(__('employee-lifecycle::dashboard.movement_types.'.$type)), mb_strtolower(trim($search))))
            ->values()
            ->all();

        return $this->completionOptions(
            'employee_lifecycle_movements',
            'effective_date',
            fn (object $row): string => $this->personnelName($row).' · '.__('employee-lifecycle::dashboard.movement_types.'.$row->movement_type),
            $search,
            $selectedId,
            $limit,
            ['movement_type'],
            fn (Builder $query) => $query->orWhereIn('employee_lifecycle_movements.movement_type', $typeMatches),
        );
    }

    /**
     * @return array<int, array{id: int, label: string}>
     */
    public function offboardingCaseOptions(string $search = '', ?int $selectedId = null, int $limit = self::OPTION_LIMIT): array
    {
        return $this->completionOptions(
            'employee_lifecycle_offboarding_cases',
            'last_working_date',
            fn (object $row): string => $this->personnelName($row).' · '.$row->last_working_date,
            $search,
            $selectedId,
            $limit,
        );
    }

    /**
     * Same rows and order as the queue list; the search looks at the option label's text
     * (employee name, and the date — or whatever $orSearch adds).
     *
     * @param  callable(object): string  $label
     * @param  array<int, string>  $extraColumns
     * @param  (callable(Builder): mixed)|null  $orSearch
     * @return array<int, array{id: int, label: string}>
     */
    private function completionOptions(
        string $table,
        string $dateColumn,
        callable $label,
        string $search,
        ?int $selectedId,
        int $limit,
        array $extraColumns = [],
        ?callable $orSearch = null,
    ): array {
        if (! InstalledTables::has($table)) {
            return [];
        }

        $search = mb_strtolower(trim($search));
        $base = fn (): Builder => $this->joinPersonnel(DB::table($table), $table)
            ->select([
                $table.'.id',
                $table.'.'.$dateColumn,
                ...array_map(fn (string $column): string => $table.'.'.$column, $extraColumns),
                ...$this->personnelColumns(),
            ]);

        $rows = $base()
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($table, $dateColumn, $search, $orSearch): void {
                $like = '%'.$search.'%';
                $query->whereRaw("concat_ws(' ', COALESCE(lp.surname, lpt.surname), COALESCE(lp.name, lpt.name), COALESCE(lp.patronymic, lpt.patronymic)) like ?", [$like])
                    ->orWhere($table.'.'.$dateColumn, 'like', $like);

                if ($orSearch !== null) {
                    $orSearch($query);
                }
            }))
            ->orderByRaw("case when {$table}.{$dateColumn} is null then 1 else 0 end")
            ->orderBy($table.'.'.$dateColumn)
            ->orderByDesc($table.'.id')
            ->limit($limit)
            ->get();

        if ($selectedId !== null && ! $rows->contains(fn (object $row): bool => (int) $row->id === $selectedId)) {
            $rows = $rows->concat($base()->where($table.'.id', $selectedId)->get());
        }

        return $rows
            ->map(fn (object $row): array => ['id' => (int) $row->id, 'label' => $label($row)])
            ->values()
            ->all();
    }

    /**
     * Open tasks past their due date: the full count, and the first rows for the side list.
     *
     * @return array{total: int, rows: Collection}
     */
    private function overdueTasks(): array
    {
        if (! InstalledTables::has('employee_lifecycle_tasks')) {
            return ['total' => 0, 'rows' => collect()];
        }

        $query = DB::table('employee_lifecycle_tasks')
            ->join('employee_lifecycle_events', 'employee_lifecycle_events.id', '=', 'employee_lifecycle_tasks.event_id')
            ->whereNotIn('employee_lifecycle_tasks.status', self::CLOSED_STATUSES)
            ->where('employee_lifecycle_tasks.due_at', '<', today()->toDateString());

        $total = (clone $query)->count();

        if ($total === 0) {
            return ['total' => 0, 'rows' => collect()];
        }

        $rows = $this->joinPersonnel($query, 'employee_lifecycle_events')
            ->leftJoin('users as owners', 'owners.id', '=', 'employee_lifecycle_tasks.owner_user_id')
            ->select([
                'employee_lifecycle_tasks.id',
                'employee_lifecycle_tasks.title',
                'employee_lifecycle_tasks.owner_type',
                'employee_lifecycle_tasks.status',
                'employee_lifecycle_tasks.due_at',
                'employee_lifecycle_events.type as event_type',
                'employee_lifecycle_events.title as event_title',
                ...$this->personnelColumns(),
                'owners.name as owner_name',
            ])
            ->orderBy('employee_lifecycle_tasks.due_at')
            ->orderBy('employee_lifecycle_tasks.id')
            ->limit(self::OVERDUE_TASK_LIMIT)
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'owner_type' => (string) $row->owner_type,
                'owner_label' => __('employee-lifecycle::dashboard.owner_types.'.($row->owner_type ?: 'hr')),
                'status' => (string) $row->status,
                'due_at' => $row->due_at,
                'is_overdue' => true,
                'event_type' => (string) $row->event_type,
                'event_title' => $this->localizedEventTitle((string) $row->event_title, (string) $row->event_type),
                'employee_name' => $this->personnelName($row),
                'tabel_no' => (string) $row->tabel_no,
                'owner_name' => $row->owner_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            ]);

        return ['total' => $total, 'rows' => $rows];
    }

    /**
     * Counts per facet value, plus the total under '' — one GROUP BY query.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<string, int>
     */
    private function facetCounts(array $filters, string $column): Collection
    {
        if (! InstalledTables::has('employee_lifecycle_events')) {
            return collect(['' => 0]);
        }

        $counts = $this->filteredEvents($filters)
            ->selectRaw("employee_lifecycle_events.{$column} as facet, count(*) as aggregate")
            ->groupBy('employee_lifecycle_events.'.$column)
            ->pluck('aggregate', 'facet')
            ->map(fn ($count): int => (int) $count);

        return $counts->put('', $counts->sum());
    }

    private function eventRow(object $row): array
    {
        $meta = $this->decodeMeta($row->meta ?? null);
        $sourceType = (string) ($row->source_type ?? '');
        $sourceId = $row->source_id !== null ? (int) $row->source_id : null;
        $isOrderSource = str_contains($sourceType, 'order');
        $orderNo = $meta['order_no'] ?? null;

        return [
            'id' => (int) $row->id,
            'type' => (string) $row->type,
            'status' => (string) $row->status,
            'title' => $this->localizedEventTitle((string) $row->title, (string) $row->type, $sourceType),
            'description' => $row->description,
            'effective_date' => $row->effective_date,
            'deadline_at' => $row->deadline_at,
            'is_overdue' => $row->deadline_at !== null
                && $row->deadline_at < today()->toDateString()
                && ! in_array($row->status, ['completed', 'cancelled'], true),
            'employee_name' => $this->personnelName($row),
            'tabel_no' => (string) $row->tabel_no,
            'structure_name' => $row->structure_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            'position_name' => $row->position_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            'owner_name' => $row->owner_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            'template_name' => $row->template_name,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_is_order' => $isOrderSource,
            'source_label' => $isOrderSource
                ? __('employee-lifecycle::dashboard.labels.order_source_with_no', ['order' => $orderNo ?: '#'.$sourceId])
                : null,
        ];
    }

    private function localizedEventTitle(string $title, string $type, string $sourceType = ''): string
    {
        foreach ([0 => $title, 1 => $sourceType] as $position => $value) {
            foreach (self::EVENT_TITLE_KEYS as $key => $match) {
                if ($value !== '' && $match[$position] === $value) {
                    return __('employee-lifecycle::dashboard.event_titles.'.$key);
                }
            }
        }

        if ($title === '') {
            foreach (self::EVENT_TITLE_KEYS as $key => $match) {
                if ($match[2] === $type) {
                    return __('employee-lifecycle::dashboard.event_titles.'.$key);
                }
            }
        }

        return $title;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMeta(mixed $meta): array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (! is_string($meta) || trim($meta) === '') {
            return [];
        }

        $decoded = json_decode($meta, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function personnelName(object $row): string
    {
        $name = trim(implode(' ', array_filter([$row->surname, $row->name, $row->patronymic])));

        return $name !== '' ? $name : __('employee-lifecycle::dashboard.labels.unassigned');
    }
}

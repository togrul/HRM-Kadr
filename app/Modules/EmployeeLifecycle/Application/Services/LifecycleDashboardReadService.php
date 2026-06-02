<?php

namespace App\Modules\EmployeeLifecycle\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LifecycleDashboardReadService
{
    public function dashboard(array $filters = []): array
    {
        $allEvents = $this->allEvents();
        $events = $this->applyEventFilters($allEvents, $filters);
        $tasks = $this->tasks();
        $templates = $this->planTemplates();
        $probationReviews = $this->probationReviews();
        $movements = $this->movements();
        $offboardingCases = $this->offboardingCases();
        $reviewEventIds = $probationReviews->pluck('event_id')->filter()->unique();
        $probationQueue = $probationReviews->where('status', 'pending')->count()
            + $allEvents
                ->where('type', 'probation')
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->reject(fn (array $event): bool => $reviewEventIds->contains($event['id']))
                ->count();

        return [
            'summary' => [
                'active_templates' => $templates->where('is_active', true)->count(),
                'active_events' => $allEvents->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'overdue_tasks' => $tasks->where('is_overdue', true)->count(),
                'probation_queue' => $probationQueue,
                'movement_queue' => $movements->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'offboarding_queue' => $offboardingCases->whereNotIn('status', ['completed', 'cancelled'])->count()
                    ?: $allEvents->where('type', 'offboarding')->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ],
            'events' => $events,
            'overdueTasks' => $tasks->where('is_overdue', true)->values(),
            'typeBreakdown' => $this->typeBreakdown($allEvents),
            'planTemplates' => $templates,
            'probationReviews' => $probationReviews,
            'movements' => $movements,
            'offboardingCases' => $offboardingCases,
        ];
    }

    public function events(array $filters = []): Collection
    {
        return $this->applyEventFilters($this->allEvents(), $filters);
    }

    private function allEvents(): Collection
    {
        if (! Schema::hasTable('employee_lifecycle_events')) {
            return collect();
        }

        return DB::table('employee_lifecycle_events')
            ->leftJoin('personnels', function ($join): void {
                $join->on('personnels.id', '=', 'employee_lifecycle_events.personnel_id')
                    ->orOn('personnels.tabel_no', '=', 'employee_lifecycle_events.tabel_no');
            })
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'employee_lifecycle_events.owner_user_id')
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
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'structures.name as structure_name',
                'positions.name as position_name',
                'owners.name as owner_name',
            ])
            ->orderByRaw('case when employee_lifecycle_events.deadline_at is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_events.deadline_at')
            ->orderByDesc('employee_lifecycle_events.id')
            ->get()
            ->map(fn ($row): array => $this->eventRow($row))
            ->values();
    }

    public function planTemplates(): Collection
    {
        if (! Schema::hasTable('employee_lifecycle_plan_templates')) {
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

    public function probationReviews(): Collection
    {
        if (! Schema::hasTable('employee_lifecycle_probation_reviews')) {
            return collect();
        }

        return DB::table('employee_lifecycle_probation_reviews')
            ->leftJoin('personnels', function ($join): void {
                $join->on('personnels.id', '=', 'employee_lifecycle_probation_reviews.personnel_id')
                    ->orOn('personnels.tabel_no', '=', 'employee_lifecycle_probation_reviews.tabel_no');
            })
            ->leftJoin('users as managers', 'managers.id', '=', 'employee_lifecycle_probation_reviews.manager_user_id')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'employee_lifecycle_probation_reviews.hr_reviewer_user_id')
            ->select([
                'employee_lifecycle_probation_reviews.id',
                'employee_lifecycle_probation_reviews.event_id',
                'employee_lifecycle_probation_reviews.status',
                'employee_lifecycle_probation_reviews.decision',
                'employee_lifecycle_probation_reviews.score',
                'employee_lifecycle_probation_reviews.review_due_at',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'managers.name as manager_name',
                'reviewers.name as reviewer_name',
            ])
            ->orderByRaw('case when employee_lifecycle_probation_reviews.review_due_at is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_probation_reviews.review_due_at')
            ->orderByDesc('employee_lifecycle_probation_reviews.id')
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

    public function movements(): Collection
    {
        if (! Schema::hasTable('employee_lifecycle_movements')) {
            return collect();
        }

        return DB::table('employee_lifecycle_movements')
            ->leftJoin('personnels', function ($join): void {
                $join->on('personnels.id', '=', 'employee_lifecycle_movements.personnel_id')
                    ->orOn('personnels.tabel_no', '=', 'employee_lifecycle_movements.tabel_no');
            })
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
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'current_structures.name as current_structure_name',
                'current_positions.name as current_position_name',
                'target_structures.name as target_structure_name',
                'target_positions.name as target_position_name',
                'approvers.name as approver_name',
            ])
            ->orderByRaw('case when employee_lifecycle_movements.effective_date is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_movements.effective_date')
            ->orderByDesc('employee_lifecycle_movements.id')
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

    public function offboardingCases(): Collection
    {
        if (! Schema::hasTable('employee_lifecycle_offboarding_cases')) {
            return collect();
        }

        return DB::table('employee_lifecycle_offboarding_cases')
            ->leftJoin('personnels', function ($join): void {
                $join->on('personnels.id', '=', 'employee_lifecycle_offboarding_cases.personnel_id')
                    ->orOn('personnels.tabel_no', '=', 'employee_lifecycle_offboarding_cases.tabel_no');
            })
            ->leftJoin('structures', 'structures.id', '=', 'personnels.structure_id')
            ->leftJoin('positions', 'positions.id', '=', 'personnels.position_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'employee_lifecycle_offboarding_cases.owner_user_id')
            ->select([
                'employee_lifecycle_offboarding_cases.id',
                'employee_lifecycle_offboarding_cases.event_id',
                'employee_lifecycle_offboarding_cases.last_working_date',
                'employee_lifecycle_offboarding_cases.status',
                'employee_lifecycle_offboarding_cases.reason',
                'employee_lifecycle_offboarding_cases.exit_interview_completed_at',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'structures.name as structure_name',
                'positions.name as position_name',
                'owners.name as owner_name',
            ])
            ->orderByRaw('case when employee_lifecycle_offboarding_cases.last_working_date is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_offboarding_cases.last_working_date')
            ->orderByDesc('employee_lifecycle_offboarding_cases.id')
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

    private function applyEventFilters(Collection $events, array $filters = []): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['search'] ?? '')));
        $type = trim((string) ($filters['type'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        return $events
            ->when($search !== '', fn (Collection $rows) => $rows->filter(fn (array $row): bool => str_contains(mb_strtolower(implode(' ', [
                $row['title'],
                $row['employee_name'],
                $row['tabel_no'],
                $row['structure_name'],
                $row['position_name'],
                $row['owner_name'],
                $row['source_label'] ?? '',
            ])), $search)))
            ->when($type !== '', fn (Collection $rows) => $rows->where('type', $type))
            ->when($status !== '', fn (Collection $rows) => $rows->where('status', $status))
            ->values();
    }

    private function tasks(): Collection
    {
        if (! Schema::hasTable('employee_lifecycle_tasks')) {
            return collect();
        }

        return DB::table('employee_lifecycle_tasks')
            ->join('employee_lifecycle_events', 'employee_lifecycle_events.id', '=', 'employee_lifecycle_tasks.event_id')
            ->leftJoin('personnels', function ($join): void {
                $join->on('personnels.id', '=', 'employee_lifecycle_events.personnel_id')
                    ->orOn('personnels.tabel_no', '=', 'employee_lifecycle_events.tabel_no');
            })
            ->leftJoin('users as owners', 'owners.id', '=', 'employee_lifecycle_tasks.owner_user_id')
            ->whereNotIn('employee_lifecycle_tasks.status', ['completed', 'cancelled'])
            ->select([
                'employee_lifecycle_tasks.id',
                'employee_lifecycle_tasks.title',
                'employee_lifecycle_tasks.owner_type',
                'employee_lifecycle_tasks.status',
                'employee_lifecycle_tasks.due_at',
                'employee_lifecycle_events.type as event_type',
                'employee_lifecycle_events.title as event_title',
                'personnels.tabel_no',
                'personnels.surname',
                'personnels.name',
                'personnels.patronymic',
                'owners.name as owner_name',
            ])
            ->orderByRaw('case when employee_lifecycle_tasks.due_at is null then 1 else 0 end')
            ->orderBy('employee_lifecycle_tasks.due_at')
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'owner_type' => (string) $row->owner_type,
                'owner_label' => __('employee-lifecycle::dashboard.owner_types.'.($row->owner_type ?: 'hr')),
                'status' => (string) $row->status,
                'due_at' => $row->due_at,
                'is_overdue' => $row->due_at !== null && $row->due_at < today()->toDateString(),
                'event_type' => (string) $row->event_type,
                'event_title' => $this->localizedEventTitle((string) $row->event_title, (string) $row->event_type),
                'employee_name' => $this->personnelName($row),
                'tabel_no' => (string) $row->tabel_no,
                'owner_name' => $row->owner_name ?: __('employee-lifecycle::dashboard.labels.unassigned'),
            ]);
    }

    private function typeBreakdown(Collection $events): Collection
    {
        return $events
            ->groupBy('type')
            ->map(fn (Collection $rows, string $type): array => [
                'type' => $type,
                'label' => __('employee-lifecycle::dashboard.types.'.$type),
                'count' => $rows->count(),
                'overdue' => $rows->where('is_overdue', true)->count(),
            ])
            ->sortByDesc('count')
            ->values();
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
        $legacyTitles = [
            'Probation review' => 'probation_review',
            'Internal movement' => 'internal_movement',
            'Offboarding case' => 'offboarding_case',
        ];

        if (isset($legacyTitles[$title])) {
            return __('employee-lifecycle::dashboard.event_titles.'.$legacyTitles[$title]);
        }

        $sourceKeys = [
            'employee_lifecycle_probation_review' => 'probation_review',
            'employee_lifecycle_movement' => 'internal_movement',
            'employee_lifecycle_offboarding_case' => 'offboarding_case',
        ];

        if ($sourceType !== '' && isset($sourceKeys[$sourceType])) {
            return __('employee-lifecycle::dashboard.event_titles.'.$sourceKeys[$sourceType]);
        }

        $typeKeys = [
            'probation' => 'probation_review',
            'movement' => 'internal_movement',
            'offboarding' => 'offboarding_case',
        ];

        if ($title === '' && isset($typeKeys[$type])) {
            return __('employee-lifecycle::dashboard.event_titles.'.$typeKeys[$type]);
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

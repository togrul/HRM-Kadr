<?php

namespace App\Modules\EmployeeLifecycle\Application\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LifecyclePlanTemplateService
{
    /**
     * @param  array<int, array{title: string, owner_type?: string, due_offset_days?: int, is_required?: bool}>  $tasks
     */
    public function createTemplate(array $attributes, array $tasks): int
    {
        return DB::transaction(function () use ($attributes, $tasks): int {
            $templateId = DB::table('employee_lifecycle_plan_templates')->insertGetId([
                'name' => (string) $attributes['name'],
                'type' => (string) ($attributes['type'] ?? 'onboarding'),
                'description' => $attributes['description'] ?? null,
                'default_duration_days' => (int) ($attributes['default_duration_days'] ?? 14),
                'is_active' => (bool) ($attributes['is_active'] ?? true),
                'created_by' => $attributes['created_by'] ?? auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (array_values($tasks) as $index => $task) {
                DB::table('employee_lifecycle_task_templates')->insert([
                    'plan_template_id' => $templateId,
                    'title' => (string) $task['title'],
                    'owner_type' => (string) ($task['owner_type'] ?? 'hr'),
                    'due_offset_days' => (int) ($task['due_offset_days'] ?? 0),
                    'is_required' => (bool) ($task['is_required'] ?? true),
                    'sort_order' => (int) ($task['sort_order'] ?? $index),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $templateId;
        });
    }

    /**
     * @param  array<int, array{title: string, owner_type?: string, due_offset_days?: int, is_required?: bool, sort_order?: int}>  $tasks
     */
    public function updateTemplate(int $templateId, array $attributes, array $tasks): void
    {
        DB::transaction(function () use ($templateId, $attributes, $tasks): void {
            $exists = DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->exists();

            if (! $exists) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.template_not_found'));
            }

            DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->update([
                'name' => (string) $attributes['name'],
                'type' => (string) ($attributes['type'] ?? 'onboarding'),
                'description' => $attributes['description'] ?? null,
                'default_duration_days' => (int) ($attributes['default_duration_days'] ?? 14),
                'is_active' => (bool) ($attributes['is_active'] ?? true),
                'updated_at' => now(),
            ]);

            DB::table('employee_lifecycle_task_templates')->where('plan_template_id', $templateId)->delete();

            foreach (array_values($tasks) as $index => $task) {
                DB::table('employee_lifecycle_task_templates')->insert([
                    'plan_template_id' => $templateId,
                    'title' => (string) $task['title'],
                    'owner_type' => (string) ($task['owner_type'] ?? 'hr'),
                    'due_offset_days' => (int) ($task['due_offset_days'] ?? 0),
                    'is_required' => (bool) ($task['is_required'] ?? true),
                    'sort_order' => (int) ($task['sort_order'] ?? $index),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function setTemplateActive(int $templateId, bool $active): void
    {
        $updated = DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->update([
            'is_active' => $active,
            'updated_at' => now(),
        ]);

        if ($updated === 0) {
            throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.template_not_found'));
        }
    }

    public function deleteOrArchiveTemplate(int $templateId): string
    {
        return DB::transaction(function () use ($templateId): string {
            $template = DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->first();

            if (! $template) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.template_not_found'));
            }

            $isUsed = DB::table('employee_lifecycle_events')->where('plan_template_id', $templateId)->exists();

            if ($isUsed) {
                DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->update([
                    'is_active' => false,
                    'updated_at' => now(),
                ]);

                return 'archived';
            }

            DB::table('employee_lifecycle_task_templates')->where('plan_template_id', $templateId)->delete();
            DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->delete();

            return 'deleted';
        });
    }

    public function launchForPersonnel(int $templateId, int $personnelId, CarbonInterface|string|null $startDate = null, ?int $ownerUserId = null, ?int $createdBy = null): int
    {
        $start = $startDate instanceof CarbonInterface ? $startDate->copy() : Carbon::parse($startDate ?: today());

        return DB::transaction(function () use ($templateId, $personnelId, $start, $ownerUserId, $createdBy): int {
            $template = DB::table('employee_lifecycle_plan_templates')->where('id', $templateId)->first();

            if (! $template) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.template_not_found'));
            }

            $personnel = DB::table('personnels')->where('id', $personnelId)->first(['id', 'tabel_no', 'surname', 'name']);

            if (! $personnel) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.personnel_not_found'));
            }

            $deadline = $start->copy()->addDays((int) $template->default_duration_days);
            $eventId = DB::table('employee_lifecycle_events')->insertGetId([
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'type' => $template->type,
                'status' => 'in_progress',
                'title' => $template->name,
                'description' => $template->description,
                'effective_date' => $start->toDateString(),
                'deadline_at' => $deadline->toDateString(),
                'owner_user_id' => $ownerUserId,
                'plan_template_id' => $templateId,
                'source_type' => 'employee_lifecycle_plan_template',
                'source_id' => $templateId,
                'meta' => json_encode([
                    'template_name' => $template->name,
                    'launched_from_template' => true,
                ]),
                'created_by' => $createdBy ?? auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_lifecycle_task_templates')
                ->where('plan_template_id', $templateId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->each(function ($taskTemplate) use ($eventId, $start, $ownerUserId): void {
                    DB::table('employee_lifecycle_tasks')->insert([
                        'event_id' => $eventId,
                        'task_template_id' => $taskTemplate->id,
                        'title' => $taskTemplate->title,
                        'owner_type' => $taskTemplate->owner_type,
                        'owner_user_id' => $ownerUserId,
                        'due_at' => $start->copy()->addDays((int) $taskTemplate->due_offset_days)->toDateString(),
                        'status' => 'open',
                        'sort_order' => $taskTemplate->sort_order,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });

            return $eventId;
        });
    }

    public function scheduleProbationReview(int $personnelId, CarbonInterface|string $reviewDueAt, ?int $managerUserId = null, ?int $hrReviewerUserId = null, ?int $createdBy = null): int
    {
        $dueAt = $reviewDueAt instanceof CarbonInterface ? $reviewDueAt->copy() : Carbon::parse($reviewDueAt);

        return DB::transaction(function () use ($personnelId, $dueAt, $managerUserId, $hrReviewerUserId, $createdBy): int {
            $personnel = DB::table('personnels')->where('id', $personnelId)->first(['id', 'tabel_no', 'surname', 'name']);

            if (! $personnel) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.personnel_not_found'));
            }

            $eventId = DB::table('employee_lifecycle_events')->insertGetId([
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'type' => 'probation',
                'status' => 'planned',
                'title' => __('employee-lifecycle::dashboard.event_titles.probation_review'),
                'description' => null,
                'effective_date' => $dueAt->toDateString(),
                'deadline_at' => $dueAt->toDateString(),
                'owner_user_id' => $hrReviewerUserId ?: $managerUserId,
                'source_type' => 'employee_lifecycle_probation_review',
                'created_by' => $createdBy ?? auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $reviewId = DB::table('employee_lifecycle_probation_reviews')->insertGetId([
                'event_id' => $eventId,
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'manager_user_id' => $managerUserId,
                'hr_reviewer_user_id' => $hrReviewerUserId,
                'review_due_at' => $dueAt->toDateString(),
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_lifecycle_events')->where('id', $eventId)->update([
                'source_id' => $reviewId,
                'updated_at' => now(),
            ]);

            return $reviewId;
        });
    }

    public function completeProbationReview(int $reviewId, string $decision, ?int $score = null, ?string $hrNote = null, ?int $reviewedBy = null): void
    {
        DB::transaction(function () use ($reviewId, $decision, $score, $hrNote, $reviewedBy): void {
            $review = DB::table('employee_lifecycle_probation_reviews')->where('id', $reviewId)->first();

            if (! $review) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.probation_not_found'));
            }

            DB::table('employee_lifecycle_probation_reviews')->where('id', $reviewId)->update([
                'status' => 'completed',
                'decision' => $decision,
                'score' => $score,
                'hr_note' => $hrNote,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewedBy ?? auth()->id(),
                'updated_at' => now(),
            ]);

            DB::table('employee_lifecycle_events')->where('id', $review->event_id)->update([
                'status' => $decision === 'extend' ? 'in_progress' : 'completed',
                'completed_at' => $decision === 'extend' ? null : now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function scheduleMovement(
        int $personnelId,
        string $movementType,
        ?int $targetStructureId,
        ?int $targetPositionId,
        CarbonInterface|string $effectiveDate,
        ?string $reason = null,
        ?int $ownerUserId = null,
        ?int $createdBy = null
    ): int {
        $type = trim($movementType);
        $effective = $effectiveDate instanceof CarbonInterface ? $effectiveDate->copy() : Carbon::parse($effectiveDate);

        if (! in_array($type, ['transfer', 'promotion', 'role_change'], true)) {
            throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.movement_type_not_supported'));
        }

        if ($targetStructureId === null && $targetPositionId === null) {
            throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.movement_target_required'));
        }

        return DB::transaction(function () use ($personnelId, $type, $targetStructureId, $targetPositionId, $effective, $reason, $ownerUserId, $createdBy): int {
            $personnel = DB::table('personnels')->where('id', $personnelId)->first([
                'id',
                'tabel_no',
                'surname',
                'name',
                'structure_id',
                'position_id',
            ]);

            if (! $personnel) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.personnel_not_found'));
            }

            $eventId = DB::table('employee_lifecycle_events')->insertGetId([
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'type' => 'movement',
                'status' => 'planned',
                'title' => __('employee-lifecycle::dashboard.event_titles.internal_movement'),
                'description' => $reason,
                'effective_date' => $effective->toDateString(),
                'deadline_at' => $effective->toDateString(),
                'owner_user_id' => $ownerUserId,
                'source_type' => 'employee_lifecycle_movement',
                'meta' => json_encode([
                    'movement_type' => $type,
                    'current_structure_id' => $personnel->structure_id,
                    'current_position_id' => $personnel->position_id,
                    'target_structure_id' => $targetStructureId,
                    'target_position_id' => $targetPositionId,
                ]),
                'created_by' => $createdBy ?? auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $movementId = DB::table('employee_lifecycle_movements')->insertGetId([
                'event_id' => $eventId,
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'movement_type' => $type,
                'current_structure_id' => $personnel->structure_id,
                'current_position_id' => $personnel->position_id,
                'target_structure_id' => $targetStructureId,
                'target_position_id' => $targetPositionId,
                'effective_date' => $effective->toDateString(),
                'status' => 'planned',
                'reason' => $reason,
                'created_by' => $createdBy ?? auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_lifecycle_events')->where('id', $eventId)->update([
                'source_id' => $movementId,
                'updated_at' => now(),
            ]);

            return $movementId;
        });
    }

    public function completeMovement(int $movementId, ?int $approvedBy = null, bool $applyToPersonnel = true): void
    {
        DB::transaction(function () use ($movementId, $approvedBy, $applyToPersonnel): void {
            $movement = DB::table('employee_lifecycle_movements')->where('id', $movementId)->first();

            if (! $movement) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.movement_not_found'));
            }

            DB::table('employee_lifecycle_movements')->where('id', $movementId)->update([
                'status' => 'completed',
                'approved_by' => $approvedBy ?? auth()->id(),
                'approved_at' => now(),
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

            if ($applyToPersonnel && $movement->personnel_id !== null) {
                $personnelUpdates = array_filter([
                    'structure_id' => $movement->target_structure_id,
                    'position_id' => $movement->target_position_id,
                ], fn ($value): bool => $value !== null);

                if ($personnelUpdates !== []) {
                    $personnelUpdates['updated_at'] = now();

                    DB::table('personnels')->where('id', $movement->personnel_id)->update($personnelUpdates);
                }
            }

            DB::table('employee_lifecycle_events')->where('id', $movement->event_id)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * @param  array<int, array{title: string, owner_type?: string, due_offset_days?: int, sort_order?: int}>  $checklistTasks
     */
    public function openOffboardingCase(
        int $personnelId,
        CarbonInterface|string $lastWorkingDate,
        ?string $reason = null,
        ?int $ownerUserId = null,
        ?int $createdBy = null,
        array $checklistTasks = []
    ): int {
        $lastDay = $lastWorkingDate instanceof CarbonInterface ? $lastWorkingDate->copy() : Carbon::parse($lastWorkingDate);
        $tasks = $checklistTasks !== [] ? $checklistTasks : $this->defaultOffboardingChecklist();

        return DB::transaction(function () use ($personnelId, $lastDay, $reason, $ownerUserId, $createdBy, $tasks): int {
            $personnel = DB::table('personnels')->where('id', $personnelId)->first([
                'id',
                'tabel_no',
                'surname',
                'name',
            ]);

            if (! $personnel) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.personnel_not_found'));
            }

            $eventId = DB::table('employee_lifecycle_events')->insertGetId([
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'type' => 'offboarding',
                'status' => 'in_progress',
                'title' => __('employee-lifecycle::dashboard.event_titles.offboarding_case'),
                'description' => $reason,
                'effective_date' => $lastDay->toDateString(),
                'deadline_at' => $lastDay->toDateString(),
                'owner_user_id' => $ownerUserId,
                'source_type' => 'employee_lifecycle_offboarding_case',
                'created_by' => $createdBy ?? auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $caseId = DB::table('employee_lifecycle_offboarding_cases')->insertGetId([
                'event_id' => $eventId,
                'personnel_id' => $personnel->id,
                'tabel_no' => $personnel->tabel_no,
                'last_working_date' => $lastDay->toDateString(),
                'status' => 'open',
                'reason' => $reason,
                'owner_user_id' => $ownerUserId,
                'created_by' => $createdBy ?? auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('employee_lifecycle_events')->where('id', $eventId)->update([
                'source_id' => $caseId,
                'updated_at' => now(),
            ]);

            foreach (array_values($tasks) as $index => $task) {
                DB::table('employee_lifecycle_tasks')->insert([
                    'event_id' => $eventId,
                    'title' => (string) $task['title'],
                    'owner_type' => (string) ($task['owner_type'] ?? 'hr'),
                    'owner_user_id' => $ownerUserId,
                    'due_at' => $lastDay->copy()->addDays((int) ($task['due_offset_days'] ?? 0))->toDateString(),
                    'status' => 'open',
                    'sort_order' => (int) ($task['sort_order'] ?? $index),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $caseId;
        });
    }

    public function completeOffboardingCase(int $caseId, ?string $exitInterviewSummary = null, ?int $completedBy = null): void
    {
        DB::transaction(function () use ($caseId, $exitInterviewSummary, $completedBy): void {
            $case = DB::table('employee_lifecycle_offboarding_cases')->where('id', $caseId)->first();

            if (! $case) {
                throw new InvalidArgumentException(__('employee-lifecycle::dashboard.errors.offboarding_not_found'));
            }

            DB::table('employee_lifecycle_offboarding_cases')->where('id', $caseId)->update([
                'status' => 'completed',
                'exit_interview_summary' => $exitInterviewSummary,
                'exit_interview_completed_at' => $exitInterviewSummary !== null ? now() : $case->exit_interview_completed_at,
                'completed_at' => now(),
                'completed_by' => $completedBy ?? auth()->id(),
                'updated_at' => now(),
            ]);

            DB::table('employee_lifecycle_events')->where('id', $case->event_id)->update([
                'status' => 'completed',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * @return array<int, array{title: string, owner_type: string, due_offset_days: int}>
     */
    private function defaultOffboardingChecklist(): array
    {
        return [
            ['title' => __('employee-lifecycle::dashboard.checklist.confirm_last_day_documents'), 'owner_type' => 'hr', 'due_offset_days' => 0],
            ['title' => __('employee-lifecycle::dashboard.checklist.close_system_access'), 'owner_type' => 'it', 'due_offset_days' => 0],
            ['title' => __('employee-lifecycle::dashboard.checklist.verify_handover_assets'), 'owner_type' => 'manager', 'due_offset_days' => 0],
            ['title' => __('employee-lifecycle::dashboard.checklist.complete_exit_interview'), 'owner_type' => 'hr', 'due_offset_days' => 0],
        ];
    }
}

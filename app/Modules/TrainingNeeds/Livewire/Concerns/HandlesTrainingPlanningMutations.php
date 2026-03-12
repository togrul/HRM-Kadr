<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingPlanItem;
use App\Models\TrainingProgram;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedPlanningService;
use App\Modules\TrainingNeeds\Application\Services\TrainingSessionParticipantService;

trait HandlesTrainingPlanningMutations
{
    public function selectVisibleSessionProposals(): void
    {
        $this->bulkProposalPlanItemIds = collect($this->sessionProposals)
            ->pluck('plan_item_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function clearSelectedSessionProposals(): void
    {
        $this->bulkProposalPlanItemIds = [];
    }

    public function createSelectedSessionProposals(): void
    {
        $this->authorizeTrainingNeedsManage();

        $validated = $this->validate([
            'bulkProposalPlanItemIds' => 'required|array|min:1',
            'bulkProposalPlanItemIds.*' => 'integer|exists:training_plan_items,id',
        ]);

        $created = 0;

        foreach ($validated['bulkProposalPlanItemIds'] as $planItemId) {
            $proposal = app(\App\Modules\TrainingNeeds\Application\Services\TrainingSessionProposalService::class)
                ->proposals()
                ->firstWhere('plan_item_id', (int) $planItemId);

            if ($proposal === null) {
                continue;
            }

            $session = TrainingSession::query()->create([
                'training_plan_item_id' => $proposal['plan_item_id'],
                'training_annual_plan_id' => $proposal['training_annual_plan_id'],
                'training_program_id' => $proposal['training_program_id'],
                'title' => $proposal['title'],
                'scheduled_start_at' => $proposal['scheduled_start_at'],
                'scheduled_end_at' => $proposal['scheduled_end_at'],
                'location' => $proposal['location'],
                'trainer_name' => $proposal['trainer_name'],
                'capacity' => $proposal['capacity'],
                'planned_budget' => $proposal['planned_budget'],
                'auto_fill_participants' => (bool) $proposal['auto_fill_participants'],
                'status' => $proposal['status'],
                'notes' => $proposal['notes'],
            ]);

            if ($session->auto_fill_participants) {
                app(TrainingSessionParticipantService::class)->autoFillFromNeeds($session->load('plan'));
            }

            $created++;
        }

        $this->bulkProposalPlanItemIds = [];
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.bulk_session_proposals_created', ['count' => $created]));
    }

    public function storePlan(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'planForm.title' => 'required|string|min:2|max:160',
            'planForm.plan_year' => 'required|integer|min:2020|max:2100',
            'planForm.plan_quarter' => 'nullable|integer|min:1|max:4',
            'planForm.status' => 'required|in:draft,review,approved,published',
            'planForm.notes' => 'nullable|string|max:2000',
            'planForm.auto_generate' => 'nullable|boolean',
        ], attributes: [
            'planForm.title' => __('training_needs::dashboard.fields.plan_title'),
            'planForm.plan_year' => __('training_needs::dashboard.fields.plan_year'),
            'planForm.plan_quarter' => __('training_needs::dashboard.fields.plan_quarter'),
            'planForm.status' => __('training_needs::dashboard.fields.status'),
        ]);

        $plan = TrainingAnnualPlan::query()->create([
            'title' => trim((string) data_get($validated, 'planForm.title')),
            'plan_year' => (int) data_get($validated, 'planForm.plan_year'),
            'plan_quarter' => data_get($validated, 'planForm.plan_quarter'),
            'status' => (string) data_get($validated, 'planForm.status'),
            'notes' => data_get($validated, 'planForm.notes'),
            'auto_generated' => (bool) (data_get($validated, 'planForm.auto_generate') ?? true),
        ]);

        if ((bool) (data_get($validated, 'planForm.auto_generate') ?? true)) {
            app(TrainingNeedPlanningService::class)->generatePlanItems($plan);
        } else {
            app(TrainingNeedPlanningService::class)->syncPlanStatus($plan);
        }

        $this->reset('planForm');
        $this->planForm = $this->planDefaults();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.plan_saved'));
    }

    public function savePlanItemReview(string $status): void
    {
        $this->authorizeTrainingNeedsReview();
        abort_unless(in_array($status, ['hr_adjusted', 'approved'], true), 404);

        $validated = $this->validate([
            'selectedPlanItemId' => 'required|exists:training_plan_items,id',
            'planItemReviewForm.participant_count' => 'required|integer|min:1|max:10000',
            'planItemReviewForm.estimated_budget' => 'nullable|numeric|min:0|max:99999999.99',
            'planItemReviewForm.priority' => 'required|in:low,medium,high',
            'planItemReviewForm.review_note' => 'nullable|string|max:2000',
        ], attributes: [
            'selectedPlanItemId' => __('training_needs::dashboard.fields.plan_item'),
            'planItemReviewForm.participant_count' => __('training_needs::dashboard.fields.participant_count'),
            'planItemReviewForm.estimated_budget' => __('training_needs::dashboard.fields.planned_budget'),
            'planItemReviewForm.priority' => __('training_needs::dashboard.fields.priority'),
            'planItemReviewForm.review_note' => __('training_needs::dashboard.fields.review_note'),
        ]);

        $item = TrainingPlanItem::query()->findOrFail((int) $validated['selectedPlanItemId']);
        $item->update([
            'participant_count' => (int) data_get($validated, 'planItemReviewForm.participant_count'),
            'estimated_budget' => data_get($validated, 'planItemReviewForm.estimated_budget'),
            'priority' => (string) data_get($validated, 'planItemReviewForm.priority'),
            'review_note' => data_get($validated, 'planItemReviewForm.review_note'),
            'review_status' => $status,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        app(TrainingNeedPlanningService::class)->syncPlanStatus($item->plan()->firstOrFail());

        $this->selectedPlanItemId = $item->id;
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.plan_item_review_saved', [
            'status' => __('training_needs::dashboard.review_statuses.'.$status),
        ]));
    }

    public function storeSession(): void
    {
        $this->authorizeTrainingNeedsManage();
        $validated = $this->validate([
            'sessionForm.training_annual_plan_id' => 'nullable|exists:training_annual_plans,id',
            'sessionForm.training_program_id' => 'nullable|exists:training_programs,id',
            'sessionForm.title' => 'nullable|string|min:2|max:160',
            'sessionForm.scheduled_start_at' => 'required|date',
            'sessionForm.scheduled_end_at' => 'nullable|date|after_or_equal:sessionForm.scheduled_start_at',
            'sessionForm.location' => 'nullable|string|max:160',
            'sessionForm.trainer_name' => 'nullable|string|max:160',
            'sessionForm.capacity' => 'nullable|integer|min:1|max:5000',
            'sessionForm.planned_budget' => 'nullable|numeric|min:0|max:9999999.99',
            'sessionForm.auto_fill_participants' => 'nullable|boolean',
            'sessionForm.status' => 'required|in:draft,scheduled,in_progress,completed,cancelled',
            'sessionForm.notes' => 'nullable|string|max:2000',
        ], attributes: [
            'sessionForm.training_annual_plan_id' => __('training_needs::dashboard.fields.plan'),
            'sessionForm.training_program_id' => __('training_needs::dashboard.fields.program'),
            'sessionForm.title' => __('training_needs::dashboard.fields.session_title'),
            'sessionForm.scheduled_start_at' => __('training_needs::dashboard.fields.scheduled_start_at'),
            'sessionForm.scheduled_end_at' => __('training_needs::dashboard.fields.scheduled_end_at'),
            'sessionForm.location' => __('training_needs::dashboard.fields.location'),
            'sessionForm.trainer_name' => __('training_needs::dashboard.fields.trainer_name'),
            'sessionForm.capacity' => __('training_needs::dashboard.fields.capacity'),
            'sessionForm.planned_budget' => __('training_needs::dashboard.fields.planned_budget'),
            'sessionForm.auto_fill_participants' => __('training_needs::dashboard.fields.auto_fill_participants'),
            'sessionForm.status' => __('training_needs::dashboard.fields.status'),
        ]);

        $program = null;
        if (data_get($validated, 'sessionForm.training_program_id')) {
            $program = TrainingProgram::query()->find(data_get($validated, 'sessionForm.training_program_id'));
        }

        $session = TrainingSession::query()->create([
            'training_plan_item_id' => $this->selectedSessionProposalPlanItemId,
            'training_annual_plan_id' => data_get($validated, 'sessionForm.training_annual_plan_id'),
            'training_program_id' => data_get($validated, 'sessionForm.training_program_id'),
            'title' => trim((string) (data_get($validated, 'sessionForm.title') ?: ($program?->title ?: __('training_needs::dashboard.labels.default_session_title')))),
            'scheduled_start_at' => data_get($validated, 'sessionForm.scheduled_start_at'),
            'scheduled_end_at' => data_get($validated, 'sessionForm.scheduled_end_at'),
            'location' => blank(data_get($validated, 'sessionForm.location')) ? null : trim((string) data_get($validated, 'sessionForm.location')),
            'trainer_name' => blank(data_get($validated, 'sessionForm.trainer_name')) ? null : trim((string) data_get($validated, 'sessionForm.trainer_name')),
            'capacity' => data_get($validated, 'sessionForm.capacity'),
            'planned_budget' => data_get($validated, 'sessionForm.planned_budget'),
            'auto_fill_participants' => (bool) (data_get($validated, 'sessionForm.auto_fill_participants') ?? true),
            'status' => (string) data_get($validated, 'sessionForm.status'),
            'notes' => data_get($validated, 'sessionForm.notes'),
        ]);

        $filledParticipants = 0;
        if ($session->auto_fill_participants) {
            $filledParticipants = app(TrainingSessionParticipantService::class)->autoFillFromNeeds($session->load('plan'));
        }

        if ($session->training_annual_plan_id) {
            app(TrainingNeedPlanningService::class)->syncPlanStatus($session->plan()->first());
        }

        $this->reset('sessionForm', 'searchSessionPlan', 'searchTrainingProgram');
        $this->sessionForm = $this->sessionDefaults();
        $this->selectedSessionProposalPlanItemId = null;
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.session_saved', [
            'count' => $filledParticipants,
        ]));
    }
}

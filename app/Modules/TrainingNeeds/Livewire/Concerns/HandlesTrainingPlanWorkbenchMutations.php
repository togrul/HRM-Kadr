<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingPlanItem;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedPlanningService;

trait HandlesTrainingPlanWorkbenchMutations
{
    public function editPlan(int $id): void
    {
        $this->authorizeTrainingNeedsManage();

        $plan = TrainingAnnualPlan::query()->findOrFail($id);

        $this->editingPlanId = $plan->id;
        $this->planForm = [
            'title' => (string) $plan->title,
            'plan_year' => (int) $plan->plan_year,
            'plan_quarter' => $plan->plan_quarter,
            'status' => (string) $plan->status,
            'notes' => (string) ($plan->notes ?? ''),
            'auto_generate' => false,
        ];
    }

    public function cancelPlanEdit(): void
    {
        $this->editingPlanId = null;
        $this->planForm = $this->planDefaults();
        $this->resetValidation();
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

        $payload = [
            'title' => trim((string) data_get($validated, 'planForm.title')),
            'plan_year' => (int) data_get($validated, 'planForm.plan_year'),
            'plan_quarter' => data_get($validated, 'planForm.plan_quarter'),
            'status' => (string) data_get($validated, 'planForm.status'),
            'notes' => data_get($validated, 'planForm.notes'),
        ];
        $shouldGenerate = (bool) (data_get($validated, 'planForm.auto_generate') ?? true);

        if ($this->editingPlanId) {
            $plan = TrainingAnnualPlan::query()->findOrFail($this->editingPlanId);
            $plan->update([
                ...$payload,
                'auto_generated' => $shouldGenerate ? true : (bool) $plan->auto_generated,
            ]);
        } else {
            $plan = TrainingAnnualPlan::query()->create([
                ...$payload,
                'auto_generated' => $shouldGenerate,
            ]);
        }

        if ($shouldGenerate) {
            app(TrainingNeedPlanningService::class)->generatePlanItems($plan->fresh());
        } else {
            app(TrainingNeedPlanningService::class)->syncPlanStatus($plan->fresh());
        }

        $this->cancelPlanEdit();
        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.plan_saved'));
    }

    public function deletePlan(int $planId): void
    {
        $this->authorizeTrainingNeedsManage();

        $plan = TrainingAnnualPlan::query()->findOrFail($planId);
        $selectedPlanItemBelongsToPlan = $this->selectedPlanItemId
            ? TrainingPlanItem::query()
                ->where('id', $this->selectedPlanItemId)
                ->where('training_annual_plan_id', $planId)
                ->exists()
            : false;
        $plan->delete();

        if ($this->editingPlanId === $planId) {
            $this->cancelPlanEdit();
        }

        if ($selectedPlanItemBelongsToPlan) {
            $this->cancelPlanItemReview();
        }

        if ((int) data_get($this->sessionForm, 'training_annual_plan_id') === $planId) {
            $this->sessionForm['training_annual_plan_id'] = null;
        }

        $this->refreshRuntimeCaches();
        $this->dispatch('trainingNeedsSaved', __('training_needs::dashboard.messages.plan_deleted'));
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
}

<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Services\HrPolicies\HrPolicyPackService;
use Livewire\Attributes\Computed;

trait InteractsWithTrainingNeedsAccess
{
    protected function authorizeTrainingNeedsView(): void
    {
        $user = auth()->user();
        $policies = app(HrPolicyPackService::class);

        abort_unless($policies->permissionEnabled('training_needs.view') && $user && $user->canAny([
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ]), 403);
    }

    /**
     * The views gate their forms and buttons on the same checks the actions enforce.
     */
    #[Computed]
    public function canManageTrainingNeeds(): bool
    {
        return $this->trainingNeedsAllows('training_needs.manage', 'manage-training-needs');
    }

    #[Computed]
    public function canReviewTrainingNeeds(): bool
    {
        return $this->trainingNeedsAllows('training_needs.review', 'review-training-needs');
    }

    #[Computed]
    public function canExportTrainingNeeds(): bool
    {
        return $this->trainingNeedsAllows('training_needs.export', 'export-training-needs');
    }

    protected function authorizeTrainingNeedsManage(): void
    {
        abort_unless($this->trainingNeedsAllows('training_needs.manage', 'manage-training-needs'), 403);
    }

    protected function authorizeTrainingNeedsReview(): void
    {
        abort_unless($this->trainingNeedsAllows('training_needs.review', 'review-training-needs'), 403);
    }

    protected function authorizeTrainingNeedsExport(): void
    {
        abort_unless($this->trainingNeedsAllows('training_needs.export', 'export-training-needs'), 403);
    }

    private function trainingNeedsAllows(string $policyKey, string $permission): bool
    {
        return app(HrPolicyPackService::class)->permissionEnabled($policyKey)
            && (bool) auth()->user()?->can($permission);
    }
}

<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Services\HrPolicies\HrPolicyPackService;

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

    protected function authorizeTrainingNeedsManage(): void
    {
        abort_unless(
            app(HrPolicyPackService::class)->permissionEnabled('training_needs.manage')
            && auth()->user()?->can('manage-training-needs'),
            403
        );
    }

    protected function authorizeTrainingNeedsReview(): void
    {
        abort_unless(
            app(HrPolicyPackService::class)->permissionEnabled('training_needs.review')
            && auth()->user()?->can('review-training-needs'),
            403
        );
    }

    protected function authorizeTrainingNeedsExport(): void
    {
        abort_unless(
            app(HrPolicyPackService::class)->permissionEnabled('training_needs.export')
            && auth()->user()?->can('export-training-needs'),
            403
        );
    }
}

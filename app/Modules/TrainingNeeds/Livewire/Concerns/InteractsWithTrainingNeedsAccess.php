<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait InteractsWithTrainingNeedsAccess
{
    protected function authorizeTrainingNeedsView(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->canAny([
            'show-training-needs',
            'manage-training-needs',
            'review-training-needs',
            'export-training-needs',
        ]), 403);
    }

    protected function authorizeTrainingNeedsManage(): void
    {
        abort_unless(auth()->user()?->can('manage-training-needs'), 403);
    }

    protected function authorizeTrainingNeedsReview(): void
    {
        abort_unless(auth()->user()?->can('review-training-needs'), 403);
    }

    protected function authorizeTrainingNeedsExport(): void
    {
        abort_unless(auth()->user()?->can('export-training-needs'), 403);
    }
}

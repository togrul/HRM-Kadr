<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

trait InteractsWithPerformanceEvaluationAccess
{
    protected function authorizePerformanceEvaluationView(): void
    {
        $user = auth()->user();

        abort_unless($user && $user->canAny([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
            'export-performance-evaluation',
        ]), 403);
    }

    protected function authorizePerformanceEvaluationManage(): void
    {
        abort_unless(auth()->user()?->can('manage-performance-evaluation'), 403);
    }

    protected function authorizePerformanceEvaluationReview(): void
    {
        abort_unless(auth()->user()?->canAny(['review-performance-evaluation', 'manage-performance-evaluation']), 403);
    }

    protected function authorizePerformanceEvaluationExport(): void
    {
        abort_unless(auth()->user()?->canAny(['export-performance-evaluation', 'manage-performance-evaluation']), 403);
    }
}

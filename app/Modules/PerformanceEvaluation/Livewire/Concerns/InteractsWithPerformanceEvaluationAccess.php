<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

use App\Services\HrPolicies\HrPolicyPackService;

trait InteractsWithPerformanceEvaluationAccess
{
    protected function authorizePerformanceEvaluationView(): void
    {
        $user = auth()->user();
        $policies = app(HrPolicyPackService::class);

        abort_unless($policies->permissionEnabled('performance_evaluation.view') && $user && $user->canAny([
            'show-performance-evaluation',
            'manage-performance-evaluation',
            'review-performance-evaluation',
            'export-performance-evaluation',
        ]), 403);
    }

    protected function authorizePerformanceEvaluationManage(): void
    {
        abort_unless(
            app(HrPolicyPackService::class)->permissionEnabled('performance_evaluation.manage')
            && auth()->user()?->can('manage-performance-evaluation'),
            403
        );
    }

    protected function authorizePerformanceEvaluationReview(): void
    {
        abort_unless(
            app(HrPolicyPackService::class)->permissionEnabled('performance_evaluation.review')
            && auth()->user()?->canAny(['review-performance-evaluation', 'manage-performance-evaluation']),
            403
        );
    }

    protected function authorizePerformanceEvaluationExport(): void
    {
        abort_unless(
            app(HrPolicyPackService::class)->permissionEnabled('performance_evaluation.export')
            && auth()->user()?->canAny(['export-performance-evaluation', 'manage-performance-evaluation']),
            403
        );
    }
}

<?php

use App\Modules\PerformanceEvaluation\Livewire\Dashboard;
use App\Modules\PerformanceEvaluation\Livewire\EvaluatorWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\TestWorkspace;
use App\Modules\PerformanceEvaluation\Livewire\UserPersonnelLinks;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceEvaluationReportingService;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceTestTranscriptService;
use App\Models\PerformanceTestAttempt;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/performance-evaluation', Dashboard::class)
        ->name('performance-evaluation');
    Route::get('/performance-evaluation/evaluator', EvaluatorWorkspace::class)
        ->name('performance-evaluation.evaluator');
    Route::get('/performance-evaluation/tests/take', TestWorkspace::class)
        ->name('performance-evaluation.test-workspace');
    Route::get('/performance-evaluation/user-personnel-links', UserPersonnelLinks::class)
        ->name('performance-evaluation.user-personnel-links');
    Route::get('/performance-evaluation/tests/attempts/{attempt}/print', function (PerformanceTestAttempt $attempt, PerformanceTestTranscriptService $transcriptService) {
        $loadedAttempt = $transcriptService->loadVisibleAttempt(auth()->user(), $attempt->id);
        $analytics = $transcriptService->buildAttemptAnalytics($loadedAttempt);

        return response()->view('performance-evaluation::print.test-transcript', [
            'attempt' => $loadedAttempt,
            'analytics' => $analytics,
        ]);
    })->name('performance-evaluation.test-transcript');
    Route::get('/performance-evaluation/print-summary', function (PerformanceEvaluationReportingService $reporting) {
        abort_unless(auth()->user()?->canAny(['show-performance-evaluation', 'manage-performance-evaluation', 'export-performance-evaluation']), 403);

        return response()->view('performance-evaluation::print.summary', [
            'summary' => $reporting->formSummaryRows(),
            'weakPivot' => $reporting->weakLinkPivotRows(),
        ]);
    })->name('performance-evaluation.print-summary');
});

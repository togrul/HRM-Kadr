<?php

use App\Modules\TrainingNeeds\Livewire\Dashboard;
use App\Modules\TrainingNeeds\Application\Services\TrainingNeedReportingService;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/training-needs', Dashboard::class)
        ->name('training-needs');
    Route::get('/training-needs/print-summary', function (TrainingNeedReportingService $reporting) {
        abort_unless(auth()->user()?->canAny(['show-training-needs', 'manage-training-needs', 'export-training-needs']), 403);

        return response()->view('training-needs::print.summary', [
            'deliverySummary' => $reporting->deliverySummaryRows(),
            'deliveryPivot' => $reporting->deliveryPivotRows(),
            'feedbackSummary' => $reporting->feedbackSessionSummaries(),
        ]);
    })->name('training-needs.print-summary');
});

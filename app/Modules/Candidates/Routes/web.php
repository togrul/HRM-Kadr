<?php

use App\Modules\Candidates\Http\Controllers\CandidateDocumentDownloadController;
use App\Modules\Candidates\Livewire\CandidateList;
use App\Modules\Candidates\Livewire\ApplicationPipeline;
use App\Modules\Candidates\Livewire\ApplicationDetail;
use App\Modules\Candidates\Livewire\RecruitmentAnalytics;
use App\Modules\Candidates\Livewire\OpeningDetail;
use App\Modules\Candidates\Livewire\OpeningList;
use App\Modules\Candidates\Livewire\RequisitionDetail;
use App\Modules\Candidates\Livewire\RequisitionList;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/candidates', CandidateList::class)->name('candidates');
    Route::get('/candidates/requisitions', RequisitionList::class)->name('candidates.requisitions');
    Route::get('/candidates/requisitions/{requisition}', RequisitionDetail::class)->name('candidates.requisitions.show');
    Route::get('/candidates/openings', OpeningList::class)->name('candidates.openings');
    Route::get('/candidates/openings/{opening}', OpeningDetail::class)->name('candidates.openings.show');
    Route::get('/candidates/applications', ApplicationPipeline::class)->name('candidates.applications');
    Route::get('/candidates/applications/{application}', ApplicationDetail::class)->name('candidates.applications.show');
    Route::get('/candidates/analytics', RecruitmentAnalytics::class)->name('candidates.analytics');
    Route::get('/candidates/documents/{document}/download', CandidateDocumentDownloadController::class)
        ->name('candidates.documents.download');
});

<?php

use App\Modules\Candidates\Http\Controllers\CandidateDocumentDownloadController;
use App\Modules\Candidates\Livewire\CandidateList;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/candidates', CandidateList::class)->name('candidates');
    Route::get('/candidates/documents/{document}/download', CandidateDocumentDownloadController::class)
        ->name('candidates.documents.download');
});

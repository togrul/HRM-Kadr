<?php

use App\Modules\Candidates\Livewire\CandidateList;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/candidates', CandidateList::class)->name('candidates');
});

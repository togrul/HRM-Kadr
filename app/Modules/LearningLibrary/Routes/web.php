<?php

use App\Modules\LearningLibrary\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/learning-library', Dashboard::class)->name('learning-library');
});

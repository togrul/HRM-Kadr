<?php

use App\Modules\OnboardingLibrary\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/onboarding-library', Dashboard::class)->name('onboarding-library');
});

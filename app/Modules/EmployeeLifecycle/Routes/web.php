<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:show-employee-lifecycle'])->group(function (): void {
    Route::get('/employee-lifecycle', fn () => view('employee-lifecycle::pages.dashboard'))
        ->name('employee-lifecycle');
});

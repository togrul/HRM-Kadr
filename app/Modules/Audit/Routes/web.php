<?php

use App\Modules\Audit\Http\Controllers\ActivityLogExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:show-audit-logs'])->group(function () {
    Route::get('/audit-logs', fn () => view('audit::pages.activity-logs'))
        ->name('audit.logs');
    Route::get('/audit-logs/export', ActivityLogExportController::class)
        ->name('audit.logs.export');
});

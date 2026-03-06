<?php

use App\Modules\Attendance\Http\Controllers\AttendancePunchIngestController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')
    ->prefix('api/attendance')
    ->group(function () {
        Route::post('/punches/ingest', AttendancePunchIngestController::class)
            ->name('attendance.api.punches.ingest');
    });


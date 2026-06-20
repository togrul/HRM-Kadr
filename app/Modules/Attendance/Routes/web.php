<?php

use App\Modules\Attendance\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/attendance', Dashboard::class)->name('attendance');
    Route::redirect('/attendance/manual-entries', '/attendance?tab=manual', 301)
        ->name('attendance.manual-entries');
});

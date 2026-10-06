<?php

use App\Modules\Compensation\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/compensation', Dashboard::class)->name('compensation');
});

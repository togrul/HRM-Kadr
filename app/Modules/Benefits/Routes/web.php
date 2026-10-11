<?php

use App\Modules\Benefits\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/benefits', Dashboard::class)->name('benefits');
});

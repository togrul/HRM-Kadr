<?php

use App\Modules\Vacation\Livewire\VacationNorms;
use App\Modules\Vacation\Livewire\Vacations;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/vacations', Vacations::class)->name('vacations.list');
});

Route::middleware(['web', 'auth', 'can:access-admin'])->prefix('/admin')->group(function () {
    Route::get('/vacation-norms', VacationNorms::class)->name('admin.vacation-norms');
});

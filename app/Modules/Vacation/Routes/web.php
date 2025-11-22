<?php

use App\Modules\Vacation\Livewire\Vacations;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/vacations', Vacations::class)->name('vacations.list');
});

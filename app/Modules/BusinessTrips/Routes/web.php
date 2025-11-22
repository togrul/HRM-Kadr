<?php

use App\Modules\BusinessTrips\Livewire\BusinessTrips;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/business-trips', BusinessTrips::class)->name('business-trips.list');
});

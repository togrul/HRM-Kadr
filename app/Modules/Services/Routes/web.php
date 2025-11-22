<?php

use App\Modules\Services\Livewire\Service;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/services', Service::class)->name('services');
});

<?php

use App\Modules\Staff\Livewire\Staffs;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/staffs', Staffs::class)->name('staffs');
});

<?php

use App\Modules\Personnel\Livewire\AllPersonnel;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', AllPersonnel::class)->name('home');
});

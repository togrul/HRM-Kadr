<?php

use App\Modules\Leaves\Livewire\Leaves;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/leaves', Leaves::class)->name('leaves');
});

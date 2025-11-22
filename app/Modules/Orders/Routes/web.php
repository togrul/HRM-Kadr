<?php

use App\Modules\Orders\Livewire\AllOrders;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/orders', AllOrders::class)->name('orders');
});

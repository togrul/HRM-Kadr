<?php

use App\Modules\Orders\Livewire\AllOrders;
use App\Modules\Orders\Livewire\OrderComposer;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/orders', AllOrders::class)->name('orders');
    Route::get('/orders/composer/{personnelId?}', OrderComposer::class)->name('orders.composer');
});

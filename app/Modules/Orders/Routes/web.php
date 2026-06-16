<?php

use App\Modules\Orders\Livewire\AllOrders;
use App\Modules\Orders\Livewire\OrderComposer;
use App\Modules\Orders\Livewire\OrderTemplateDesigner;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/orders', AllOrders::class)->name('orders');
    Route::get('/orders/composer/edit/{orderId}', OrderComposer::class)->name('orders.composer.edit');
    Route::get('/orders/composer/{personnelId?}', OrderComposer::class)->name('orders.composer');
    Route::get('/orders/designer/{code?}', OrderTemplateDesigner::class)->name('orders.designer');
});

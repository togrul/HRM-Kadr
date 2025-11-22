<?php

use App\Modules\Notifications\Livewire\NotificationList;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/notifications', NotificationList::class)->name('notifications');
});

<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/notifications', fn () => view('notification::pages.notifications-index'))
        ->name('notifications');
});

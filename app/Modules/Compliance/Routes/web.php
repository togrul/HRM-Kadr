<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:show-document-compliance'])->group(function (): void {
    Route::get('/document-compliance', fn () => view('compliance::pages.document-expiry'))
        ->name('document-compliance');
});

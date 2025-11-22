<?php

use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('print')
    ->group(function () {
        Route::get('personnel/{id?}', [PrintController::class, 'personnel_service_book'])->name('print.personnel');
        Route::get('page/{model?}', [PrintController::class, 'print_page'])->name('print.page');
    });

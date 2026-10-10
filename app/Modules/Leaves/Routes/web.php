<?php

use App\Modules\Leaves\Http\Controllers\LeaveDocumentController;
use App\Modules\Leaves\Livewire\Leaves;
use App\Modules\Leaves\Livewire\SickCertificates\SickCertificates;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/leaves', Leaves::class)->name('leaves');
    Route::get('/leaves/sick-certificates', SickCertificates::class)->name('leaves.sick-certificates');
    Route::get('/leaves/{leave}/document', LeaveDocumentController::class)
        ->whereNumber('leave')
        ->name('leaves.document');
});

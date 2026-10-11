<?php

use App\Modules\Compensation\Http\Controllers\TaxReliefDocumentController;
use App\Modules\Compensation\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/compensation', Dashboard::class)->name('compensation');
    Route::get('/compensation/tax-relief/{relief}/document', TaxReliefDocumentController::class)->name('compensation.tax-relief.document');
});

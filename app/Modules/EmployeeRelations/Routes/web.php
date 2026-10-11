<?php

use App\Modules\EmployeeRelations\Http\Controllers\EvidenceDownloadController;
use App\Modules\EmployeeRelations\Livewire\CaseDetail;
use App\Modules\EmployeeRelations\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/employee-relations', Dashboard::class)->name('employee-relations');
    Route::get('/employee-relations/cases/{case}', CaseDetail::class)
        ->whereNumber('case')
        ->name('employee-relations.cases.show');
    Route::get('/employee-relations/evidence/{evidence}', EvidenceDownloadController::class)
        ->whereNumber('evidence')
        ->middleware('throttle:60,1')
        ->name('employee-relations.evidence.download');
});

<?php

use App\Modules\Payroll\Http\Controllers\PayslipPrintController;
use App\Modules\Payroll\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/payroll', Dashboard::class)->name('payroll');
    Route::get('/payroll/payslip/{payslip}/print', PayslipPrintController::class)->name('payroll.payslip.print');
});

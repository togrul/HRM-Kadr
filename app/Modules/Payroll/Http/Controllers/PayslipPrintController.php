<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Models\Payslip;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class PayslipPrintController
{
    /**
     * Print-friendly payslip (browser "Save as PDF"). Accessible to the payslip owner,
     * or an admin who may view compensation amounts. Only finalised (locked) payslips.
     */
    public function __invoke(Payslip $payslip): View
    {
        $user = Auth::user();
        $ownsIt = $user?->personnel && $user->personnel->tabel_no === $payslip->tabel_no;

        abort_unless($ownsIt || ($user?->can('show-payroll') && $user->can('view-compensation-amounts')), 403);
        abort_unless($payslip->status === 'locked', 404);

        $payslip->load(['lines', 'personnel:tabel_no,surname,name', 'run.period']);

        return view('payroll::payslip-print', ['payslip' => $payslip]);
    }
}

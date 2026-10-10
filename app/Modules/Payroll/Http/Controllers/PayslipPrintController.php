<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Models\Payslip;
use App\Models\Personnel;
use App\Services\StructureService;
use App\Services\UserPersonnelLinkResolver;
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
        $personnelId = app(UserPersonnelLinkResolver::class)->resolve($user);
        $ownTabelNo = $personnelId ? Personnel::query()->whereKey($personnelId)->value('tabel_no') : null;
        $ownsIt = $ownTabelNo !== null && (string) $ownTabelNo === (string) $payslip->tabel_no;

        // Admin yolu: icazə + işçinin strukturu istifadəçinin görünürlüyündə olmalıdır.
        $adminMayView = $user?->can('show-payroll')
            && $user->can('view-compensation-amounts')
            && app(StructureService::class)->allowsPersonnel($user, $payslip->personnel()->first(['id', 'tabel_no', 'structure_id']));

        abort_unless($ownsIt || $adminMayView, 403);
        abort_unless($payslip->status === 'locked', 404);

        $payslip->load(['lines', 'personnel:tabel_no,surname,name', 'run.period']);

        return view('payroll::payslip-print', ['payslip' => $payslip]);
    }
}

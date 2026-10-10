<?php

namespace App\Modules\Leaves\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

/**
 * The certificate register as an Excel sheet. The rows come from SickCertificateRegister,
 * which never selects the diagnosis, and the sheet has no diagnosis column either.
 */
class SickCertificateExport implements FromView
{
    public function __construct(public iterable $rows) {}

    public function view(): View
    {
        return view('leaves::exports.sick-certificates', ['rows' => $this->rows]);
    }
}

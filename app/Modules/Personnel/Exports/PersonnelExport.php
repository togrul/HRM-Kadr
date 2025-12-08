<?php

namespace App\Modules\Personnel\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PersonnelExport implements FromView
{
    public iterable $report;

    public function __construct(iterable $report)
    {
        $this->report = $report;
    }

    public function view(): View
    {
        return view('personnel::exports.personnel', ['report' => $this->report]);
    }
}

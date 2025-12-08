<?php

namespace App\Modules\Vacation\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class VacationExport implements FromView
{
    public iterable $report;

    public function __construct(iterable $report)
    {
        $this->report = $report;
    }

    public function view(): View
    {
        return view('vacation::exports.vacations', ['report' => $this->report]);
    }
}

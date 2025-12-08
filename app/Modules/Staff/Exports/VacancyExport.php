<?php

namespace App\Modules\Staff\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class VacancyExport implements FromView
{
    public iterable $report;

    public function __construct(iterable $report)
    {
        $this->report = $report;
    }

    public function view(): View
    {
        return view('staff::exports.vacancies', ['report' => $this->report]);
    }
}

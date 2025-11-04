<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CandidateExport implements FromView
{
    public iterable $report;

    public function __construct(iterable $report)
    {
        $this->report = $report;
    }

    public function view(): View
    {
        return view('exports.candidate', ['report' => $this->report]);
    }
}

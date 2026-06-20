<?php

namespace App\Modules\Candidates\Exports;

use App\Modules\Candidates\Support\CandidateModeResolver;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CandidateExport implements FromView
{
    public iterable $report;
    public string $candidateMode;

    public function __construct(iterable $report, string $candidateMode = CandidateModeResolver::MILITARY)
    {
        $this->report = $report;
        $this->candidateMode = $candidateMode;
    }

    public function view(): View
    {
        return view('candidates::exports.candidate', [
            'report' => $this->report,
            'candidateMode' => $this->candidateMode,
        ]);
    }
}

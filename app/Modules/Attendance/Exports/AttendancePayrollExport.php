<?php

namespace App\Modules\Attendance\Exports;

use App\Modules\Attendance\Application\Contracts\AttendancePayrollExportContract;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class AttendancePayrollExport implements FromView
{
    public function __construct(
        public Collection $rows,
        public int $year,
        public int $month,
        public AttendancePayrollExportContract $contract
    ) {
    }

    public function view(): View
    {
        return view('attendance::exports.payroll-monthly', [
            'rows' => $this->rows,
            'year' => $this->year,
            'month' => $this->month,
            'headers' => $this->contract->headers(),
            'contract' => $this->contract,
        ]);
    }
}

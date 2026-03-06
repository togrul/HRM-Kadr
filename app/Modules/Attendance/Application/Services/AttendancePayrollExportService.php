<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceMonthlySummary;
use Illuminate\Support\Collection;

class AttendancePayrollExportService
{
    /**
     * @return Collection<int,AttendanceMonthlySummary>
     */
    public function rows(int $year, int $month): Collection
    {
        return AttendanceMonthlySummary::query()
            ->with(['personnel:tabel_no,surname,name,patronymic'])
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('tabel_no')
            ->get();
    }
}


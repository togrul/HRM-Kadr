<?php

namespace App\Modules\Attendance\Domain\Contracts;

interface PayrollAttendanceReadRepository
{
    /**
     * Monthly working/absence day counts for an employee, or null when no summary exists.
     *
     * @return array{working_days:int,absence_days:int}|null
     */
    public function monthlyAbsence(string $tabelNo, int $year, int $month): ?array;
}

<?php

namespace App\Modules\Payroll\Application\Services;

use App\Modules\Attendance\Domain\Contracts\PayrollAttendanceReadRepository;

class ProrationService
{
    public function __construct(private readonly PayrollAttendanceReadRepository $attendance) {}

    /**
     * Proration factor (0..1) for an employee in a month: paid days / working days.
     * Returns 1.0 when there is no attendance summary (employee not tracked → full pay).
     */
    public function factorFor(string $tabelNo, int $year, int $month): float
    {
        $data = $this->attendance->monthlyAbsence($tabelNo, $year, $month);

        if (! $data || $data['working_days'] <= 0) {
            return 1.0;
        }

        $paidDays = max(0, $data['working_days'] - $data['absence_days']);

        return round($paidDays / $data['working_days'], 4);
    }
}

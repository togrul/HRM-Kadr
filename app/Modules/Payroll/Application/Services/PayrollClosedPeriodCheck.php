<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\FinancePeriodState;
use App\Models\PayrollPeriod;
use App\Modules\Attendance\Contracts\AttendanceMonthLock;
use App\Modules\Integration\Domain\Contracts\PayrollOwnership;
use App\Modules\Payroll\Contracts\ClosedPeriodCheck;
use App\Support\Database\InstalledTables;
use Carbon\CarbonInterface;

/**
 * Resolves ClosedPeriodCheck. Each source is read only when its table exists, so an
 * install without the payroll, integration or attendance module simply has nothing
 * closed by that source.
 */
class PayrollClosedPeriodCheck implements ClosedPeriodCheck
{
    public function __construct(
        private readonly PayrollOwnership $ownership,
        private readonly AttendanceMonthLock $attendance,
    ) {}

    public function closedBy(CarbonInterface $date): ?string
    {
        $year = (int) $date->year;
        $month = (int) $date->month;

        if ($this->ownership->isOurs()) {
            if ($this->payrollPeriodClosed($year, $month)) {
                return self::PAYROLL;
            }
        } elseif ($this->financePeriodClosed($year, $month)) {
            return self::FINANCE;
        }

        return $this->attendance->isMonthLocked($year, $month) ? self::ATTENDANCE : null;
    }

    private function payrollPeriodClosed(int $year, int $month): bool
    {
        return InstalledTables::has('payroll_periods')
            && PayrollPeriod::query()
                ->where('year', $year)
                ->where('month', $month)
                ->where('status', 'closed')
                ->exists();
    }

    private function financePeriodClosed(int $year, int $month): bool
    {
        return InstalledTables::has('finance_period_states') && FinancePeriodState::isClosed($year, $month);
    }
}

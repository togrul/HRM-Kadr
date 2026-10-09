<?php

namespace App\Modules\Payroll\Contracts;

use Carbon\CarbonInterface;

/**
 * Sanctioned cross-module surface for "is this month already closed for pay?".
 *
 * Whoever owns payroll decides: our own payroll periods when payroll is computed here,
 * the finance system's mirrored accounting period when it is not; a locked attendance
 * month closes it either way. Consumers (Orders, before undoing an approved order)
 * depend on THIS interface and never read those tables themselves.
 *
 * @see \App\Modules\Payroll\Application\Services\PayrollClosedPeriodCheck
 */
interface ClosedPeriodCheck
{
    /** Our payroll period for the month is closed. */
    public const PAYROLL = 'payroll';

    /** The finance system has closed its accounting period for the month. */
    public const FINANCE = 'finance';

    /** Attendance for the month is locked. */
    public const ATTENDANCE = 'attendance';

    /**
     * What closed the month containing $date (one of the constants above), or null when
     * it is still open.
     */
    public function closedBy(CarbonInterface $date): ?string;
}

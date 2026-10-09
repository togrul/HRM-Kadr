<?php

namespace App\Modules\Attendance\Contracts;

/**
 * Sanctioned read surface through which payroll learns which norm working days fell in
 * a date range for an employee and on which of them the employee was away. Payroll
 * prorates pay that is due only for days actually worked (substitution, ƏM m.162) and
 * tells rest-day work done within the monthly norm from work beyond it (ƏM m.164).
 *
 * @see \App\Modules\Attendance\Application\Services\AttendancePayrollWorkedDaysService
 */
interface PayrollWorkedDays
{
    public const VACATION = 'vacation';

    public const LEAVE = 'leave';

    public const BUSINESS_TRIP = 'business_trip';

    /**
     * Norm working days in [$from, $to] (organisation calendar, the employee's structure
     * overrides; unmarked days Mon–Fri) and, keyed by date, the working days the employee
     * was away: vacation, day leave (sick leave included) or business trip.
     *
     * @return array{workdays:list<string>,absent:array<string,string>}
     */
    public function workdays(string $tabelNo, string $from, string $to): array;
}

<?php

namespace App\Modules\Attendance\Contracts;

/**
 * Sanctioned read surface through which payroll (and the finance feed) see the rest-day /
 * holiday work that orders called employees in for, and the monthly working-time norm an
 * hourly rate is derived from.
 *
 * Only order work (source "order") is listed: it is the work the employer decided on and
 * whose compensation the order fixed. A day appears once per employee even when more than
 * one order named it, so nothing downstream can pay it twice.
 *
 * @see \App\Modules\Attendance\Application\Services\AttendancePayrollRestDayWorkService
 */
interface PayrollRestDayWork
{
    /**
     * Approved order rest-day work in the month, per staff number, one entry per day.
     *
     * @param  list<string>  $tabelNos
     * @return array<string,list<array{date:string,minutes:int,compensation:string}>>
     */
    public function orderWorkFor(array $tabelNos, int $year, int $month): array;

    /**
     * The employee's working-time norm for the month in minutes (calendar, shift and
     * shortened working time applied) — the denominator of the hourly rate.
     */
    public function monthNormMinutes(string $tabelNo, int $year, int $month): int;
}

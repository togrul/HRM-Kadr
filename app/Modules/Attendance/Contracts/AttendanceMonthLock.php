<?php

namespace App\Modules\Attendance\Contracts;

/**
 * Sanctioned cross-module surface for one question: has attendance for this month been
 * closed (locked)? Consumers (e.g. the payroll period check that guards order reversals)
 * depend on THIS interface — never on AttendanceMonthLockService.
 *
 * @see \App\Modules\Attendance\Application\Services\AttendanceMonthLockReader
 */
interface AttendanceMonthLock
{
    public function isMonthLocked(int $year, int $month): bool;
}

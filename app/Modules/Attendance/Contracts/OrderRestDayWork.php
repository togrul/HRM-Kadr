<?php

namespace App\Modules\Attendance\Contracts;

use Carbon\CarbonImmutable;

/**
 * Sanctioned cross-module surface through which an approved order calls an employee in
 * to work on a rest day or public holiday. The day is put on record as approved work,
 * so the puantaj counts it as rest-day / holiday work and the compensation chosen on the
 * order (double pay or another day off) travels with it.
 *
 * @see \App\Modules\Attendance\Application\Services\AttendanceOrderRestDayWorkService
 */
interface OrderRestDayWork
{
    public const COMPENSATION_DOUBLE_PAY = 'double_pay';

    public const COMPENSATION_DAY_OFF = 'day_off';

    /**
     * Record the work and return the id the order keeps for reversal, or null when
     * attendance is not tracked on this install.
     */
    public function record(string $tabelNo, CarbonImmutable $date, string $compensation, string $reason): ?int;

    /**
     * Remove a record an order made. Safe when it is already gone.
     */
    public function remove(int $recordId): void;
}

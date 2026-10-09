<?php

namespace App\Modules\Leaves\Contracts;

use Carbon\CarbonImmutable;

/**
 * Sanctioned cross-module surface through which an approved order puts a paid,
 * day-level absence on record (military muster, donor day, election commission, civil
 * defence training…). The absence is an approved leave, so the puantaj shows its
 * attendance code on every day of the period and the overlap guard sees it.
 *
 * @see \App\Modules\Leaves\Application\Services\OrderAbsenceRecorderService
 */
interface OrderAbsenceRecorder
{
    /**
     * Record the absence and return the leave id the order keeps for reversal.
     *
     * @param  string  $typeName  leave type the absence is filed under (created on first use)
     * @param  string  $attendanceCode  puantaj code of that type (e.g. HT, DG)
     */
    public function record(
        string $tabelNo,
        CarbonImmutable $from,
        CarbonImmutable $to,
        string $typeName,
        string $attendanceCode,
        string $reason,
    ): int;

    /**
     * Remove an absence an order recorded. Safe when it is already gone.
     */
    public function remove(int $leaveId): void;
}

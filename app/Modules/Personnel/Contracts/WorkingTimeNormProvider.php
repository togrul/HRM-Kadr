<?php

namespace App\Modules\Personnel\Contracts;

/**
 * Sanctioned cross-module surface for an employee's weekly working-time norm. Attendance
 * (planned hours, timesheet defaults, ledger) reads the norm through THIS interface —
 * never the Personnel model's age, disability or override columns directly.
 *
 * @see \App\Modules\Personnel\Application\Services\WorkingTimeNormService
 */
interface WorkingTimeNormProvider
{
    /**
     * One profile per requested tabel number that exists (one query for the batch).
     *
     * @param  array<int, string>  $tabelNos
     * @return array<string, WorkingTimeProfile> tabel_no => profile
     */
    public function profiles(array $tabelNos): array;
}

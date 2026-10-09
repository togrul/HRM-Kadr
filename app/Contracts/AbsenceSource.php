<?php

namespace App\Contracts;

use App\Data\AbsencePeriod;
use Carbon\CarbonImmutable;

/**
 * One module's view of when an employee is away (leave, vacation, business trip).
 *
 * Each absence-owning module implements this in its own Application layer and tags it
 * `absence.sources` in its service provider; AbsenceOverlapGuard asks every tagged source,
 * so no module ever reads another module's tables to find a clash.
 */
interface AbsenceSource
{
    /** Tag every implementation is registered under. */
    public const TAG = 'absence.sources';

    /**
     * The employee's live (pending or approved) absences touching [$from, $to], both ends inclusive.
     *
     * @return list<AbsencePeriod>
     */
    public function overlapping(string $tabelNo, CarbonImmutable $from, CarbonImmutable $to): array;
}

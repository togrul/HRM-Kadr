<?php

namespace App\Modules\Vacation\Application\Services;

use App\Contracts\AbsenceSource;
use App\Data\AbsencePeriod;
use App\Models\PersonnelVacation;
use Carbon\CarbonImmutable;

/**
 * Vacation's contribution to the absence-overlap check: order-issued vacations and
 * self-service requests that are still pending or were approved (rejected ones and
 * deleted records no longer hold the days).
 */
class VacationAbsenceSource implements AbsenceSource
{
    public function overlapping(string $tabelNo, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return PersonnelVacation::query()
            ->where('tabel_no', $tabelNo)
            ->where(fn ($query) => $query->whereNull('approval_status')->orWhereIn('approval_status', ['pending', 'approved']))
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get(['id', 'start_date', 'end_date'])
            ->map(fn (PersonnelVacation $vacation): AbsencePeriod => AbsencePeriod::days(
                AbsencePeriod::TYPE_VACATION,
                (int) $vacation->id,
                CarbonImmutable::parse($vacation->getRawOriginal('start_date')),
                CarbonImmutable::parse($vacation->getRawOriginal('end_date')),
            ))
            ->values()
            ->all();
    }
}

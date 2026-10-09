<?php

namespace App\Modules\BusinessTrips\Application\Services;

use App\Contracts\AbsenceSource;
use App\Data\AbsencePeriod;
use App\Models\PersonnelBusinessTrip;
use Carbon\CarbonImmutable;

/**
 * BusinessTrips' contribution to the absence-overlap check: order-issued trips and
 * self-service requests that are still pending or were approved.
 */
class BusinessTripAbsenceSource implements AbsenceSource
{
    public function overlapping(string $tabelNo, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return PersonnelBusinessTrip::query()
            ->where('tabel_no', $tabelNo)
            ->where(fn ($query) => $query->whereNull('approval_status')->orWhereIn('approval_status', ['pending', 'approved']))
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('end_date', '>=', $from->toDateString())
            ->get(['id', 'start_date', 'end_date'])
            ->map(fn (PersonnelBusinessTrip $trip): AbsencePeriod => AbsencePeriod::days(
                AbsencePeriod::TYPE_BUSINESS_TRIP,
                (int) $trip->id,
                CarbonImmutable::parse($trip->getRawOriginal('start_date')),
                CarbonImmutable::parse($trip->getRawOriginal('end_date')),
            ))
            ->values()
            ->all();
    }
}

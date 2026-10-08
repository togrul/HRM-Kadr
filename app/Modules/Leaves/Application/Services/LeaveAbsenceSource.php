<?php

namespace App\Modules\Leaves\Application\Services;

use App\Contracts\AbsenceSource;
use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use Carbon\CarbonImmutable;

/**
 * Leaves' contribution to the absence-overlap check: pending and approved leaves
 * (cancelled and deleted ones no longer hold the time), with their partial-day shape.
 */
class LeaveAbsenceSource implements AbsenceSource
{
    public function overlapping(string $tabelNo, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Leave::query()
            ->where('tabel_no', $tabelNo)
            ->whereIn('status_id', [OrderStatusEnum::PENDING->value, OrderStatusEnum::APPROVED->value])
            ->whereDate('starts_at', '<=', $to->toDateString())
            ->whereDate('ends_at', '>=', $from->toDateString())
            ->get(['id', 'starts_at', 'ends_at', 'duration_unit', 'partial_day_part', 'starts_time', 'ends_time'])
            ->map(fn (Leave $leave) => $leave->absencePeriod())
            ->filter()
            ->values()
            ->all();
    }
}

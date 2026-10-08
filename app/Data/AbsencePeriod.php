<?php

namespace App\Data;

use Carbon\CarbonImmutable;

/**
 * A stretch of time an employee is away, as one absence-owning module reports it.
 * Full-day absences cover whole days; a leave may cover only part of one day
 * (half day or an hour range), which only clashes with a full day or the same part.
 */
final class AbsencePeriod
{
    public const TYPE_LEAVE = 'leave';

    public const TYPE_VACATION = 'vacation';

    public const TYPE_BUSINESS_TRIP = 'business_trip';

    public function __construct(
        public readonly string $type,
        public readonly ?int $id,
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly string $unit = 'day',
        public readonly ?string $dayPart = null,
        public readonly ?string $startsTime = null,
        public readonly ?string $endsTime = null,
    ) {}

    public static function days(string $type, ?int $id, CarbonImmutable $from, CarbonImmutable $to): self
    {
        return new self($type, $id, $from->startOfDay(), $to->startOfDay());
    }

    public function isPartialDay(): bool
    {
        return $this->unit !== 'day';
    }

    public function isSameRecord(self $other): bool
    {
        return $this->id !== null && $this->type === $other->type && $this->id === $other->id;
    }

    /** Do the two absences claim any of the same time? */
    public function overlaps(self $other): bool
    {
        if ($this->from->gt($other->to) || $other->from->gt($this->to)) {
            return false;
        }

        // A full day on either side takes the whole day.
        if (! $this->isPartialDay() || ! $other->isPartialDay()) {
            return true;
        }

        // Two partial-day leaves on the same day: only the same half, or crossing hours, clash.
        if ($this->unit === 'half_day' && $other->unit === 'half_day') {
            return $this->dayPart === null || $other->dayPart === null || $this->dayPart === $other->dayPart;
        }

        if ($this->unit === 'hour' && $other->unit === 'hour') {
            if (! $this->startsTime || ! $this->endsTime || ! $other->startsTime || ! $other->endsTime) {
                return true;
            }

            return substr($this->startsTime, 0, 5) < substr($other->endsTime, 0, 5)
                && substr($other->startsTime, 0, 5) < substr($this->endsTime, 0, 5);
        }

        // Half day against an hour range: without a fixed day schedule we cannot tell them apart.
        return true;
    }
}

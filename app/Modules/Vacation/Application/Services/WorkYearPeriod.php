<?php

namespace App\Modules\Vacation\Application\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Bir iş ili: sıra nömrəsi (işə qəbul ilindən 1-dən), başlanğıc və son (hər ikisi daxil).
 */
final class WorkYearPeriod
{
    public function __construct(
        public readonly int $sequence,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public function contains(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return $this->start->toDateString() <= $day && $day <= $this->end->toDateString();
    }

    /** "26.11.2025 – 25.11.2026" */
    public function label(): string
    {
        return $this->start->format('d.m.Y').' – '.$this->end->format('d.m.Y');
    }
}

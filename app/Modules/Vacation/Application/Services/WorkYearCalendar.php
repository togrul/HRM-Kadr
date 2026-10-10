<?php

namespace App\Modules\Vacation\Application\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * İş illərinin sərhədləri (ƏM m.113.3): iş ili işə qəbul günündən başlayır və növbəti ilin
 * həmin günündən bir gün əvvəl bitir. İş ilinə daxil olmayan dövrlər (m.132.2) həmin iş ilini
 * o qədər gün uzadır, sonrakı iş illəri də sürüşür. Saf hesablama — verilənlər bazası yoxdur.
 */
class WorkYearCalendar
{
    /**
     * $until tarixinədək başlamış iş illəri (ən çox $limit).
     *
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $excluded  iş ilinə daxil olmayan dövrlər [başlanğıc, son]
     * @return list<WorkYearPeriod>
     */
    public function periods(CarbonInterface $join, CarbonInterface $until, array $excluded = [], int $limit = 80): array
    {
        $start = CarbonImmutable::parse($join->toDateString());
        $until = CarbonImmutable::parse($until->toDateString());
        $periods = [];
        $sequence = 1;

        while ($start->lte($until) && $sequence <= $limit) {
            $end = $this->endOf($start, $excluded);
            $periods[] = new WorkYearPeriod($sequence, $start, $end);
            $start = $end->addDay();
            $sequence++;
        }

        return $periods;
    }

    /**
     * $start günündən başlayan bir iş ili (istisna dövrləri qədər uzanmış).
     *
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $excluded
     */
    public function period(int $sequence, CarbonInterface $start, array $excluded = []): WorkYearPeriod
    {
        $start = CarbonImmutable::parse($start->toDateString());

        return new WorkYearPeriod($sequence, $start, $this->endOf($start, $excluded));
    }

    /**
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $excluded
     */
    private function endOf(CarbonImmutable $start, array $excluded): CarbonImmutable
    {
        $natural = $start->addYear()->subDay();
        $end = $natural;

        // Uzanan iş ili yeni istisna dövrləri əhatə edə bilər — sabitləşənə qədər təkrarla.
        for ($i = 0; $i < 10; $i++) {
            $extended = $natural->addDays($this->overlapDays($start, $end, $excluded));

            if ($extended->equalTo($end)) {
                break;
            }

            $end = $extended;
        }

        return $end;
    }

    /**
     * @param  list<array{0: CarbonImmutable, 1: CarbonImmutable}>  $excluded
     */
    private function overlapDays(CarbonImmutable $start, CarbonImmutable $end, array $excluded): int
    {
        $days = 0;

        foreach ($excluded as [$from, $to]) {
            $a = $from->gt($start) ? $from : $start;
            $b = $to->lt($end) ? $to : $end;

            if ($a->lte($b)) {
                $days += (int) $a->diffInDays($b) + 1;
            }
        }

        return $days;
    }
}

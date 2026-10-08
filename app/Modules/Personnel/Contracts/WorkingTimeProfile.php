<?php

namespace App\Modules\Personnel\Contracts;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The facts that set one employee's weekly working-time norm, and the norm they give on
 * a date (age changes inside a month, a disability applies from its date). Pure: no
 * database access, so callers resolve it once per employee and ask per day.
 *
 * Rules (Azərbaycan Respublikasının Əmək Məcəlləsi):
 *   - m.89.3: normal week ≤ 40 hours; the standard norm is returned as null;
 *   - m.91.2: under 16 — ≤ 24 hours; 16–18, and disability for a 61–100% loss of body
 *     function (the former I and II groups) — ≤ 36 hours; pregnant women, women with a
 *     child under 1.5 and single parents of a child under 3 — ≤ 36 hours;
 *   - m.92: harmful working conditions — ≤ 36 hours;
 *   - m.90.3: the daily length follows the week (six-day week: 40 h → 7 h, 36 h → 6 h,
 *     24 h → 4 h), i.e. weekly norm ÷ working days per week;
 *   - m.108.1: the one-hour shortening before a holiday does not apply to the reduced
 *     working time of m.91–93.
 * The record carries age and disability; everything else arrives as the explicit
 * per-employee override (weekly_hours_norm). When several apply the shortest wins
 * ("not more than").
 */
final class WorkingTimeProfile
{
    public const STANDARD_WEEKLY_MINUTES = 40 * 60;

    public const UNDER_16_WEEKLY_MINUTES = 24 * 60;

    public const REDUCED_WEEKLY_MINUTES = 36 * 60;

    public const REASON_UNDER_16 = 'under_16';

    public const REASON_UNDER_18 = 'under_18';

    public const REASON_DISABILITY = 'disability';

    public const REASON_OVERRIDE = 'override';

    public function __construct(
        public readonly ?CarbonImmutable $birthdate = null,
        public readonly bool $reducingDisability = false,
        public readonly ?CarbonImmutable $disabilitySince = null,
        public readonly ?int $overrideWeeklyMinutes = null,
        public readonly bool $partTime = false,
        public readonly int $workingDaysPerWeek = 5,
    ) {}

    /**
     * The reduced weekly norm in minutes on $date, or null for the standard 40-hour week.
     */
    public function weeklyMinutesOn(CarbonInterface $date): ?int
    {
        $candidates = $this->candidates($date);

        if ($candidates === []) {
            return null;
        }

        $minutes = min($candidates);

        return $minutes < self::STANDARD_WEEKLY_MINUTES ? $minutes : null;
    }

    /**
     * Why the norm is reduced on $date (one of the REASON_* keys), or null.
     */
    public function reasonOn(CarbonInterface $date): ?string
    {
        $minutes = $this->weeklyMinutesOn($date);

        if ($minutes === null) {
            return null;
        }

        return array_search($minutes, $this->candidates($date), true) ?: null;
    }

    /**
     * Daily norm in minutes on $date: weekly norm ÷ working days per week (ƏM m.90.3),
     * or null for the standard week (the schedule's own daily length then applies).
     */
    public function dailyMinutesOn(CarbonInterface $date): ?int
    {
        $weekly = $this->weeklyMinutesOn($date);

        return $weekly === null ? null : (int) round($weekly / max(1, $this->workingDaysPerWeek));
    }

    /**
     * Whether the one-hour pre-holiday shortening applies (ƏM m.108.1): not for the reduced
     * working time of m.91–93. A part-time week (m.94) is not reduced working time, so it
     * keeps the shortening.
     */
    public function shortensBeforeHolidayOn(CarbonInterface $date): bool
    {
        $reason = $this->reasonOn($date);

        if ($reason === null) {
            return true;
        }

        return $reason === self::REASON_OVERRIDE && $this->partTime;
    }

    /**
     * @return array<string, int> reason => weekly minutes
     */
    private function candidates(CarbonInterface $date): array
    {
        $day = CarbonImmutable::parse($date->toDateString());
        $candidates = [];

        if ($this->overrideWeeklyMinutes !== null && $this->overrideWeeklyMinutes > 0) {
            $candidates[self::REASON_OVERRIDE] = $this->overrideWeeklyMinutes;
        }

        if ($this->birthdate !== null && $this->birthdate->lte($day)) {
            if ($this->birthdate->addYears(16)->gt($day)) {
                $candidates[self::REASON_UNDER_16] = self::UNDER_16_WEEKLY_MINUTES;
            } elseif ($this->birthdate->addYears(18)->gt($day)) {
                $candidates[self::REASON_UNDER_18] = self::REDUCED_WEEKLY_MINUTES;
            }
        }

        if ($this->reducingDisability && ($this->disabilitySince === null || $this->disabilitySince->lte($day))) {
            $candidates[self::REASON_DISABILITY] = self::REDUCED_WEEKLY_MINUTES;
        }

        // Ties go to the legal category ahead of the override (statute before paperwork).
        uksort($candidates, fn (string $a, string $b): int => ($a === self::REASON_OVERRIDE) <=> ($b === self::REASON_OVERRIDE));

        return $candidates;
    }
}

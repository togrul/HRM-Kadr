<?php

namespace App\Support\Language;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Throwable;

/**
 * Azerbaijani date/period formatting for orders.
 *
 * Notably computes a labour-year span the correct way: from a start date to the day
 * BEFORE the next anniversary (start + 1 year − 1 day), so it never reads as the
 * same day on both ends, e.g. 26.11.2025 → "26.11.2025-25.11.2026-cı il". The "-cı/
 * -ci/-cu/-cü il" suffix is chosen from how the year's ordinal is pronounced.
 */
class AzerbaijaniDateFormatter
{
    /** Ordinal suffix by final digit (1..9). */
    private const DIGIT_SUFFIX = [
        1 => 'ci', 2 => 'ci', 3 => 'cü', 4 => 'cü', 5 => 'ci',
        6 => 'cı', 7 => 'ci', 8 => 'ci', 9 => 'cu',
    ];

    /** Ordinal suffix when the number ends in 0, keyed by the tens value. */
    private const TENS_SUFFIX = [
        1 => 'cu',  // 10 onuncu
        2 => 'ci',  // 20 iyirminci
        3 => 'cu',  // 30 otuzuncu
        4 => 'cı',  // 40 qırxıncı
        5 => 'ci',  // 50 əllinci
        6 => 'cı',  // 60 altmışıncı
        7 => 'ci',  // 70 yetmişinci
        8 => 'ci',  // 80 səksəninci
        9 => 'cı',  // 90 doxsanıncı
        0 => 'cü',  // 00 yüzüncü
    ];

    public function yearSuffix(int $year): string
    {
        $last = $year % 10;

        if ($last !== 0) {
            return self::DIGIT_SUFFIX[$last];
        }

        return self::TENS_SUFFIX[intdiv($year % 100, 10)];
    }

    /** "26.11.2025" */
    public function shortDate(CarbonInterface $date): string
    {
        return $date->format('d.m.Y');
    }

    /** "26.11.2025-cı il" */
    public function longDate(CarbonInterface $date): string
    {
        return $this->shortDate($date).'-'.$this->yearSuffix((int) $date->year).' il';
    }

    /**
     * Parse a date the engine produced/accepts back into a Carbon: an ISO date
     * (2026-05-19), a "DD.MM.YYYY" or the Azerbaijani long form "19.05.2026-cı il".
     * Returns null when it cannot be parsed (so side-effects can fail safe).
     */
    public function parse(?string $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Strip the Azerbaijani ordinal-year suffix: "-cı il" / "-ci il" / …
        $value = trim((string) preg_replace('/-(cı|ci|cu|cü)\s*il\.?$/u', '', $value));

        foreach (['Y-m-d', 'd.m.Y', 'd-m-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (Throwable) {
                // try next format
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Labour-year span: "26.11.2025-25.11.2026-cı il" (end = start + 1 year − 1 day).
     */
    public function workYearSpan(CarbonInterface $start): string
    {
        $end = $start->copy()->addYear()->subDay();

        return sprintf(
            '%s-%s-%s il',
            $this->shortDate($start),
            $this->shortDate($end),
            $this->yearSuffix((int) $end->year),
        );
    }
}

<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTime;
use Illuminate\Support\Carbon;

/**
 * Reads a date a person typed into a date field. Livewire hands live calculations the raw
 * text ("12.0", "32.13.2024"), and Carbon::parse throws on it — a 500 while typing. This
 * accepts only a complete real date and returns null for anything else, so a calculation
 * can wait for valid input and the save-time validation reports the bad value.
 */
final class DateInput
{
    /** Storage format first, then the DD.MM.YYYY the date picker shows. */
    private const FORMATS = ['Y-m-d', 'd.m.Y', 'Y-m-d H:i:s'];

    public static function parse(mixed $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        foreach (self::FORMATS as $format) {
            $date = DateTime::createFromFormat('!'.$format, $value);

            // Round-trip check rejects overflow like 32.13.2024 that PHP silently rolls over.
            if ($date && $date->format($format) === $value) {
                return Carbon::instance($date);
            }
        }

        return null;
    }
}

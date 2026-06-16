<?php

namespace App\Services\Orders\Document;

use App\Support\Language\AzerbaijaniDateFormatter;
use Carbon\Carbon;

/**
 * Turns raw form input into the derived values templates expect, so the HR user
 * enters the minimum and the engine computes the rest correctly.
 *
 * Currently: a `work_year` entered as a single start date (Y-m-d) is expanded into
 * the proper labour-year span ending one day before the next anniversary
 * ("26.11.2025-25.11.2026-cı il") — eliminating the same-day-on-both-ends error.
 * Already-formatted values are passed through untouched.
 */
class OrderFieldTransformer
{
    public function __construct(private readonly AzerbaijaniDateFormatter $dates) {}

    /**
     * @param  array<string,mixed>  $fields
     * @return array<string,mixed>
     */
    public function transform(array $fields): array
    {
        foreach ($fields as $key => $value) {
            if (! is_string($value) || ! $this->isIsoDate($value)) {
                continue;
            }

            // work_year is a single start date expanded into the labour-year span;
            // every other ISO date becomes the Azerbaijani long form "19.05.2026-cı il".
            $fields[$key] = $key === 'work_year'
                ? $this->dates->workYearSpan(Carbon::parse($value))
                : $this->dates->longDate(Carbon::parse($value));
        }

        return $fields;
    }

    private function isIsoDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value));
    }
}

<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

/**
 * Shared helpers for the dynamic `scopeFilter` machinery on Personnel and OrderLog.
 */
trait NormalizesFilterRanges
{
    /**
     * A filter value counts as empty when it is null, a blank string, or an array whose
     * items are all empty. Booleans are always a real value.
     */
    protected function filterValueIsEmpty(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if (! $this->filterValueIsEmpty($item)) {
                    return false;
                }
            }

            return true;
        }

        if (is_bool($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        return $value === null;
    }

    /**
     * `['min' => ..., 'max' => ...]` as two Y-m-d strings, blanks filled with the defaults
     * (1990-01-01 / today) and swapped if given backwards.
     *
     * @param  array{min?: string|null, max?: string|null}  $value
     * @return array{0: string, 1: string}
     */
    protected function normalizeDateRange(array $value, ?string $defaultMin = null, ?string $defaultMax = null): array
    {
        $defaultMin ??= '1990-01-01';
        $defaultMax ??= Carbon::now()->format('Y-m-d');

        $min = $this->normalizeDateValue($value['min'] ?? null, $defaultMin);
        $max = $this->normalizeDateValue($value['max'] ?? null, $defaultMax);

        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return [$min, $max];
    }

    protected function normalizeDateValue(?string $value, string $fallback): string
    {
        if ($value === null || trim($value) === '') {
            return $fallback;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }
}

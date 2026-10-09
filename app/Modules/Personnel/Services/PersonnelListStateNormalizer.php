<?php

namespace App\Modules\Personnel\Services;

use App\Modules\Personnel\Support\Presence\PersonnelPresenceStatus;

class PersonnelListStateNormalizer
{
    /**
     * @param  array<int, string>  $allowedStatuses
     */
    public function normalizeStatus(mixed $value, array $allowedStatuses, string $default = 'current'): string
    {
        if (! is_string($value)) {
            return $default;
        }

        return in_array($value, $allowedStatuses, true) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function normalizeFilters(array $filters): array
    {
        return array_filter(
            $filters,
            fn ($value, $key) => $value !== null && $value !== '' && $value !== [] && $key !== '__identity',
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @return array<int, int>
     */
    public function normalizeStructure(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        if (is_numeric($value)) {
            return [(int) $value];
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value === ''
                ? []
                : array_values(array_filter(array_map('intval', explode(',', $value))));
        }

        return [];
    }

    public function normalizePosition(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Keep only known, filterable presence statuses, each once, in precedence order.
     *
     * @return list<string>
     */
    public function normalizePresence(mixed $value): array
    {
        $values = is_array($value) ? $value : (is_string($value) && $value !== '' ? explode(',', $value) : []);
        $values = array_map(fn ($item): string => is_string($item) ? trim($item) : '', $values);

        return array_values(array_map(
            fn (PersonnelPresenceStatus $status): string => $status->value,
            array_filter(
                PersonnelPresenceStatus::filterable(),
                fn (PersonnelPresenceStatus $status): bool => in_array($status->value, $values, true),
            ),
        ));
    }
}

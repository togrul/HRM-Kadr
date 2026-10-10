<?php

namespace App\Support\Exports;

/**
 * `fputcsv` ilə yazılan CSV ixracları üçün formula yeridilməsindən qoruma.
 *
 * Rəqəmlər və rəqəm kimi sətirlər toxunulmaz qalır; `=`, `+`, `-`, `@`, TAB, CR ilə başlayan
 * mətnlərin əvvəlinə apostrof qoyulur.
 */
final class CsvSafe
{
    public static function cell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '' || is_numeric($value)) {
            return $value;
        }

        return SafeValueBinder::neutralise($value);
    }

    /**
     * @param  array<array-key, mixed>  $row
     * @return array<array-key, mixed>
     */
    public static function row(array $row): array
    {
        return array_map(self::cell(...), $row);
    }
}

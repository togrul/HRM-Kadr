<?php

namespace App\Support;

use App\Enums\StructureEnum;
use App\Services\WordSuffixService;

class StructureSelect
{
    public static function suffixService(?WordSuffixService $service = null): WordSuffixService
    {
        return $service ?? app(WordSuffixService::class);
    }

    public static function levels($levels = null)
    {
        return $levels ?? collect(StructureEnum::cases())->mapWithKeys(fn ($c) => [$c->value => strtolower($c->name)]);
    }

    public static function resolveSelected($component, string $list, int $row, string $field, $preset = null): ?int
    {
        if ($preset !== null) {
            return $preset;
        }

        $rawValue = data_get($component->{$list}[$row] ?? [], $field);

        if (method_exists($component, 'componentFieldValue')) {
            return $component->componentFieldValue($row, $field);
        }

        return is_array($rawValue) ? ($rawValue['id'] ?? null) : $rawValue;
    }
}

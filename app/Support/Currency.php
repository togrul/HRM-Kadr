<?php

namespace App\Support;

/**
 * ISO 4217 currency codes accepted by pay, scale and loan forms — the single list the
 * form selects and their `in:` validation rules are built from.
 */
final class Currency
{
    public const DEFAULT = 'AZN';

    /**
     * @var list<string>
     */
    public const CODES = ['AZN', 'USD', 'EUR', 'GBP', 'RUB', 'TRY'];

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return self::CODES;
    }

    public static function isSupported(?string $code): bool
    {
        return in_array($code, self::CODES, true);
    }

    /**
     * Validation rule set for a required currency field.
     *
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['required', 'string', 'in:'.implode(',', self::CODES)];
    }

    /**
     * Options for `x-ui.select-dropdown`. A stored value outside the list (legacy free-text
     * input) is kept as an extra option so the edit form still shows what is saved; saving
     * it again fails validation until a supported code is chosen.
     *
     * @return list<array{id: string, label: string}>
     */
    public static function options(?string $current = null, ?string $legacyLabel = null): array
    {
        $options = array_map(
            fn (string $code): array => ['id' => $code, 'label' => $code],
            self::CODES
        );

        $current = $current !== null ? trim($current) : null;

        if ($current !== null && $current !== '' && ! self::isSupported($current)) {
            $options[] = ['id' => $current, 'label' => $legacyLabel ?? $current];
        }

        return $options;
    }
}

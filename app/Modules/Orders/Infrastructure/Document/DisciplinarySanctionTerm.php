<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\Setting;

/**
 * How many months a disciplinary sanction imposed by order stays on the employee's
 * record before it lapses (Admin → Settings, "Disciplinary sanction term (months)").
 * Defaults to 12 months; a blank or non-positive value falls back to the default.
 */
class DisciplinarySanctionTerm
{
    public const SETTING = 'Disciplinary sanction term (months)';

    public const DEFAULT_MONTHS = 12;

    public function months(): int
    {
        $configured = (int) trim((string) Setting::query()->where('name', self::SETTING)->value('value'));

        return $configured > 0 ? $configured : self::DEFAULT_MONTHS;
    }
}

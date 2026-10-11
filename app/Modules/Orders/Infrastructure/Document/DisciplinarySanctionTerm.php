<?php

namespace App\Modules\Orders\Infrastructure\Document;

use App\Models\Setting;

/**
 * How many months a disciplinary sanction imposed by order stays on the employee's
 * record before it lapses (Admin → Settings, "Disciplinary sanction term (months)").
 *
 * ƏM Md.190.1: «İntizam tənbehi verilən gündən altı ay müddətində qüvvədə olur.»
 * The term is set by law, so six months is both the default and the ceiling; the
 * employer may only lift a sanction earlier (Md.190.2, 190.4), which a shorter
 * configured term allows. A blank, non-positive or longer value falls back to six.
 */
class DisciplinarySanctionTerm
{
    public const SETTING = 'Disciplinary sanction term (months)';

    public const LEGAL_MONTHS = 6;

    public const DEFAULT_MONTHS = self::LEGAL_MONTHS;

    public function months(): int
    {
        $configured = (int) trim((string) Setting::query()->where('name', self::SETTING)->value('value'));

        return $configured > 0 && $configured <= self::LEGAL_MONTHS ? $configured : self::LEGAL_MONTHS;
    }
}

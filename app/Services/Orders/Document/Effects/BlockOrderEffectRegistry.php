<?php

namespace App\Services\Orders\Document\Effects;

/**
 * Resolves the side-effect for a block order by its template code. Order families
 * map to one effect; an unmapped type simply has no side-effect (a pure document),
 * which is the safe default — a new type never accidentally runs the wrong effect.
 */
class BlockOrderEffectRegistry
{
    /** Leave families all create a personnel vacation record. */
    private const VACATION_CODES = [
        'leave',
        'paternity_leave',
        'maternity_leave',
        'unpaid_leave',
        'education_leave',
    ];

    public function for(string $templateCode): ?BlockOrderEffect
    {
        if (in_array($templateCode, self::VACATION_CODES, true)) {
            return app(VacationBlockEffect::class);
        }

        return null;
    }
}

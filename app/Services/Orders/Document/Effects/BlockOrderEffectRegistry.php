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

    /** Termination families end employment. */
    private const TERMINATION_CODES = [
        'termination_request',
        'termination_cause',
    ];

    public function for(string $templateCode): ?BlockOrderEffect
    {
        return match (true) {
            in_array($templateCode, self::VACATION_CODES, true) => app(VacationBlockEffect::class),
            in_array($templateCode, self::TERMINATION_CODES, true) => app(TerminationBlockEffect::class),
            $templateCode === 'surname_change' => app(SurnameChangeBlockEffect::class),
            $templateCode === 'transfer' => app(TransferBlockEffect::class),
            $templateCode === 'hire' => app(HireBlockEffect::class),
            default => null,
        };
    }
}

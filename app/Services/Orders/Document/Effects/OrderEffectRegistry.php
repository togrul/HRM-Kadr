<?php

namespace App\Services\Orders\Document\Effects;

/**
 * Resolves the effect implementation for a template's effect kind. Unmapped/none
 * kinds return null (the order just changes status, no side-effect).
 */
class OrderEffectRegistry
{
    public function for(string $effect): ?OrderEffect
    {
        return match ($effect) {
            'vacation' => app(VacationEffect::class),
            'termination' => app(TerminationEffect::class),
            'transfer' => app(TransferEffect::class),
            'surname_change' => app(SurnameChangeEffect::class),
            default => null,
        };
    }
}

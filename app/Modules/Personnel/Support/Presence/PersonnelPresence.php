<?php

namespace App\Modules\Personnel\Support\Presence;

use Carbon\CarbonImmutable;

/**
 * One employee's resolved status for one day, with the absence period behind it.
 */
final class PersonnelPresence
{
    public function __construct(
        public readonly int $personnelId,
        public readonly string $tabelNo,
        public readonly PersonnelPresenceStatus $status,
        public readonly string $reason,
        public readonly ?CarbonImmutable $periodStart = null,
        public readonly ?CarbonImmutable $periodEnd = null,
        public readonly ?CarbonImmutable $expectedReturn = null,
    ) {}

    public function label(): string
    {
        return $this->status->label();
    }

    public function tone(): string
    {
        return $this->status->tone();
    }

    public function isAbsent(): bool
    {
        return $this->status->isAbsence();
    }

    public function expectedReturnLabel(): ?string
    {
        return $this->expectedReturn?->format('d.m.Y');
    }

    public function periodLabel(): ?string
    {
        if ($this->periodStart === null || $this->periodEnd === null) {
            return null;
        }

        return $this->periodStart->format('d.m.Y').' – '.$this->periodEnd->format('d.m.Y');
    }
}

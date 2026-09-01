<?php

namespace App\Modules\Integration\Support;

use App\Modules\Integration\Domain\Contracts\PayrollOwnership;

/**
 * Reads the answer from configuration.
 *
 * Deliberately not derived from "is the integration module enabled?": a customer
 * may connect the two systems for personnel and attendance while still running
 * payroll here. Ownership is a commercial decision, so it is stated explicitly
 * rather than guessed from a side effect.
 */
class ConfiguredPayrollOwnership implements PayrollOwnership
{
    public const SELF = 'self';

    public const FINANCE = 'finance';

    public function isOurs(): bool
    {
        return $this->owner() !== self::FINANCE;
    }

    public function isFinance(): bool
    {
        return ! $this->isOurs();
    }

    private function owner(): string
    {
        return (string) config('integration.payroll_owner', self::SELF);
    }
}

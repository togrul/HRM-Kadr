<?php

namespace App\Modules\Payroll\Domain\Contracts;

/**
 * The sanctioned way for another module to pay someone once through payroll (a bonus,
 * an award). The line joins the regular run of its pay month as a taxable earning.
 */
interface PayrollOneOffEarnings
{
    /**
     * Records or updates the earning; a line already paid is left alone. Returns false
     * when this installation does not run payroll itself.
     */
    public function record(string $tabelNo, string $code, string $name, float $amount, int $year, int $month, string $sourceKey, bool $taxable = true, bool $affectsSocial = true): bool;

    /**
     * Withdraws an unpaid earning (e.g. the order behind it was cancelled).
     */
    public function withdraw(string $sourceKey): void;
}

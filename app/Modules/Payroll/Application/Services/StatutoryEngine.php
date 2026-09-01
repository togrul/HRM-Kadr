<?php

namespace App\Modules\Payroll\Application\Services;

use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;

class StatutoryEngine
{
    public function __construct(private readonly CompensationReadRepository $compensation) {}

    /**
     * Compute statutory payslip lines (employee deductions + employer contributions)
     * for a regime/date given the taxable and social bases.
     *
     * @return array<int,array<string,mixed>>
     */
    public function compute(?int $regimeId, ?string $date, float $taxableBase, float $socialBase): array
    {
        $lines = [];
        $sort = 100;

        foreach ($this->compensation->statutoryRatesFor($regimeId, $date) as $rate) {
            $base = $rate->base === 'taxable' ? $taxableBase : $socialBase;
            $amount = round($this->applyBrackets($base, (array) $rate->brackets), 2);

            if ($amount <= 0) {
                continue;
            }

            $lines[] = [
                'component_id' => null,
                'code' => $rate->component_code.'_'.$rate->payer,
                'name' => $this->label($rate->component_code, $rate->payer),
                'kind' => $rate->payer === 'er' ? 'employer' : 'deduction',
                'amount' => $amount,
                'taxable' => false,
                'affects_social' => false,
                'is_statutory' => true,
                'sort' => $sort++,
            ];
        }

        return $lines;
    }

    /**
     * Marginal bracket calculation. Brackets are ordered tiers: [{up_to: number|null, rate: percent}].
     *
     * @param  array<int,array<string,mixed>>  $brackets
     */
    public function applyBrackets(float $amount, array $brackets): float
    {
        $total = 0.0;
        $previous = 0.0;

        foreach ($brackets as $bracket) {
            $upTo = $bracket['up_to'] ?? null;
            $ceiling = $upTo === null ? $amount : min($amount, (float) $upTo);

            if ($ceiling > $previous) {
                $total += ($ceiling - $previous) * ((float) $bracket['rate'] / 100);
                $previous = $ceiling;
            }

            if ($upTo !== null && $amount <= (float) $upTo) {
                break;
            }
        }

        return $total;
    }

    private function label(string $code, string $payer): string
    {
        $name = __('payroll::dashboard.statutory.'.$code);

        return $payer === 'er'
            ? $name.' ('.__('payroll::dashboard.kinds.employer').')'
            : $name;
    }
}

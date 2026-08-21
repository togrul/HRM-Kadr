<?php

namespace App\Modules\Payroll\Application\Services;

use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;

class PayrollCalculator
{
    public function __construct(
        private readonly CompensationReadRepository $compensation,
        private readonly StatutoryEngine $statutory,
        private readonly ProrationService $proration,
        private readonly LoanService $loans,
    ) {}

    /**
     * Compute a gross→net breakdown for one employee on a given date.
     * Earnings + voluntary deductions come from the employee's compensation lines;
     * statutory deductions (income tax / DSMF / unemployment / medical) and employer
     * contributions are computed from effective statutory_rates for the regime.
     *
     * @return array{gross:float,total_deductions:float,net:float,employer_cost:float,proration_factor:float,currency:string,lines:array<int,array<string,mixed>>}|null
     */
    public function calculate(string $tabelNo, ?string $onDate = null, ?int $year = null, ?int $month = null): ?array
    {
        $current = $this->compensation->currentCompensation($tabelNo, $onDate);

        if (! $current) {
            return null;
        }

        // Proration factor (paid days / working days) from attendance for the period.
        $factor = ($year && $month) ? $this->proration->factorFor($tabelNo, $year, $month) : 1.0;

        $base = round((float) $current->base_amount * $factor, 2);

        $lines = [[
            'component_id' => null,
            'code' => 'base',
            'name' => 'Baza maaş',
            'kind' => 'earning',
            'amount' => $base,
            'taxable' => true,
            'affects_social' => true,
            'is_statutory' => false,
            'sort' => 0,
        ]];

        $sort = 1;

        foreach ($this->compensation->componentsFor($tabelNo, $onDate) as $component) {
            $kind = ($component['type'] ?? 'earning') === 'deduction' ? 'deduction' : 'earning';
            $amount = round((float) ($component['amount'] ?? 0), 2);

            // Earnings are prorated by attendance; deductions (voluntary) are not.
            if ($kind === 'earning') {
                $amount = round($amount * $factor, 2);
            }

            $lines[] = [
                'component_id' => null,
                'code' => $component['component_code'] ?? '',
                'name' => $component['component_name'] ?? '',
                'kind' => $kind,
                'amount' => $amount,
                'taxable' => (bool) ($component['taxable'] ?? false),
                'affects_social' => (bool) ($component['affects_social'] ?? false),
                'is_statutory' => (bool) ($component['is_statutory'] ?? false),
                'sort' => $sort++,
            ];
        }

        // Bases derive from the earning lines and their flags.
        $gross = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning');
        $taxableBase = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning' && $l['taxable']);
        $socialBase = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning' && $l['affects_social']);

        $statutoryLines = $this->statutory->compute($current->regime_id, $onDate, $taxableBase, $socialBase);
        $lines = array_merge($lines, $statutoryLines);

        // Loan / advance repayment — a flat deduction, not prorated, not statutory.
        $loanTotal = round($this->loans->activeInstallmentTotal($tabelNo), 2);
        if ($loanTotal > 0) {
            $lines[] = [
                'component_id' => null,
                'code' => 'loan',
                'name' => __('payroll::dashboard.loan.line'),
                'kind' => 'deduction',
                'amount' => $loanTotal,
                'taxable' => false,
                'affects_social' => false,
                'is_statutory' => false,
                'sort' => 200,
            ];
        }

        $deductions = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'deduction');
        $employerCost = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'employer');

        return [
            'gross' => round($gross, 2),
            'total_deductions' => round($deductions, 2),
            'net' => round($gross - $deductions, 2),
            'employer_cost' => round($employerCost, 2),
            'proration_factor' => $factor,
            'currency' => $current->currency,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<int,array<string,mixed>>  $lines
     */
    private function sumWhere(array $lines, callable $predicate): float
    {
        $sum = 0.0;

        foreach ($lines as $line) {
            if ($predicate($line)) {
                $sum += (float) $line['amount'];
            }
        }

        return $sum;
    }
}

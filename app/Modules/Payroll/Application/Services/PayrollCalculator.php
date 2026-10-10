<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollOneOffEarning;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Support\Database\InstalledTables;

class PayrollCalculator
{
    public function __construct(
        private readonly CompensationReadRepository $compensation,
        private readonly StatutoryEngine $statutory,
        private readonly ProrationService $proration,
        private readonly LoanService $loans,
        private readonly OrderEarningsService $orderEarnings,
    ) {}

    /**
     * Compute a gross→net breakdown for one employee on a given date.
     * Earnings + voluntary deductions come from the employee's compensation lines;
     * statutory deductions (income tax / DSMF / unemployment / medical) and employer
     * contributions are computed from effective statutory_rates for the regime.
     * One-off earnings handed over for the pay month (bonus, award) join before the tax
     * bases are summed; off-cycle runs leave them out so they are paid once. The same holds
     * for what approved orders entitle the employee to in the month (double-paid rest-day
     * work, substitution extra pay), read live from their owning modules so a recalculation
     * follows the orders. $orderLines replaces those order-derived lines (retro uses it to
     * price a locked month with what was actually paid for its orders).
     *
     * A one-off already paid by a locked run is not paid again: only unpaid one-offs join,
     * plus those paid by $paidByRunId (the run being recalculated, or the locked run whose
     * month retro prices). Loan instalments are deducted only by a regular run — an
     * off-cycle run in the same month would otherwise take the instalment a second time.
     *
     * @param  list<array<string,mixed>>|null  $orderLines
     * @return array{gross:float,total_deductions:float,net:float,employer_cost:float,proration_factor:float,currency:string,lines:array<int,array<string,mixed>>}|null
     */
    public function calculate(string $tabelNo, ?string $onDate = null, ?int $year = null, ?int $month = null, bool $withOneOffs = true, ?array $orderLines = null, ?int $paidByRunId = null): ?array
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

        if ($withOneOffs && $year && $month && InstalledTables::has('payroll_one_off_earnings')) {
            $oneOffs = PayrollOneOffEarning::query()
                ->where('tabel_no', $tabelNo)
                ->where('pay_year', $year)
                ->where('pay_month', $month)
                ->where(fn ($query) => $query->whereNull('paid_payroll_run_id')
                    ->when($paidByRunId !== null, fn ($query) => $query->orWhere('paid_payroll_run_id', $paidByRunId)))
                ->orderBy('id')
                ->get();

            foreach ($oneOffs as $oneOff) {
                $lines[] = [
                    'component_id' => null,
                    'code' => $oneOff->code,
                    'name' => $oneOff->name,
                    'kind' => 'earning',
                    'amount' => round($oneOff->amount, 2),
                    'taxable' => $oneOff->taxable,
                    'affects_social' => $oneOff->affects_social,
                    'is_statutory' => false,
                    'sort' => 100 + $sort++,
                ];
            }
        }

        if ($withOneOffs && $year && $month) {
            foreach ($orderLines ?? $this->orderEarnings->linesFor($tabelNo, (float) $current->base_amount, $year, $month) as $line) {
                $lines[] = $line;
            }
        }

        // Bases derive from the earning lines and their flags.
        $gross = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning');
        $taxableBase = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning' && $l['taxable']);
        $socialBase = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning' && $l['affects_social']);

        $statutoryLines = $this->statutory->compute($current->regime_id, $onDate, $taxableBase, $socialBase);
        $lines = array_merge($lines, $statutoryLines);

        // Loan / advance repayment — a flat deduction, not prorated, not statutory.
        $loanTotal = $withOneOffs ? round($this->loans->activeInstallmentTotal($tabelNo), 2) : 0.0;
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
     * The order-derived earning lines the employee is entitled to for the month right now.
     *
     * @return list<array<string,mixed>>
     */
    public function orderEarningLines(string $tabelNo, string $onDate, int $year, int $month): array
    {
        $current = $this->compensation->currentCompensation($tabelNo, $onDate);

        return $current ? $this->orderEarnings->linesFor($tabelNo, (float) $current->base_amount, $year, $month) : [];
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

<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\StatutoryExemption;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use Illuminate\Support\Collection;

class StatutoryEngine
{
    /** @var array<string, Collection<int, StatutoryExemption>> */
    private array $exemptions = [];

    public function __construct(private readonly CompensationReadRepository $compensation) {}

    /**
     * Compute statutory payslip lines (employee deductions + employer contributions)
     * for a regime/date given the taxable and social bases.
     *
     * Income-tax reliefs (Vergi Məcəlləsi m.102, `statutory_exemptions`) reduce the base the
     * brackets apply to — not the tax: e.g. m.102.1.6 takes 200 AZN off a main-workplace monthly
     * income of at most 2500 AZN. A monthly relief is given once per month, so an off-cycle run
     * (a second payment in the same month) passes $monthlyRelief = false.
     *
     * @return array<int,array<string,mixed>>
     */
    public function compute(?int $regimeId, ?string $date, float $taxableBase, float $socialBase, bool $mainWorkplace = true, bool $monthlyRelief = true): array
    {
        $lines = [];
        $sort = 100;
        $exemptions = $this->exemptionsFor($regimeId, $date);

        foreach ($this->compensation->statutoryRatesFor($regimeId, $date) as $rate) {
            $base = $rate->base === 'taxable' ? $taxableBase : $socialBase;
            $relief = $rate->base === 'taxable'
                ? $this->reliefFor((string) $rate->component_code, $base, $exemptions, $mainWorkplace, $monthlyRelief)
                : ['amount' => 0.0, 'basis' => []];
            $amount = round($this->applyBrackets(max(0.0, $base - $relief['amount']), (array) $rate->brackets), 2);

            if ($amount <= 0) {
                continue;
            }

            $name = $this->label($rate->component_code, $rate->payer);

            if ($relief['amount'] > 0) {
                $name = __('payroll::dashboard.statutory.relief', [
                    'name' => $name,
                    'amount' => number_format($relief['amount'], 2, ',', ' '),
                    'basis' => implode(', ', $relief['basis']),
                ]);
            }

            $lines[] = [
                'component_id' => null,
                'code' => $rate->component_code.'_'.$rate->payer,
                'name' => $name,
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
     * The taxable base of a payslip: the taxable earning lines. Where a `taxable_components`
     * rule is in force for the regime (VM m.102.1.14.15 — of a serviceman's pay only the
     * position and rank salaries are taxed) only the listed component codes count.
     *
     * @param  array<int,array<string,mixed>>  $lines
     */
    public function taxableBase(array $lines, ?int $regimeId, ?string $date): float
    {
        $only = null;

        foreach ($this->exemptionsFor($regimeId, $date) as $exemption) {
            if ($exemption->kind === StatutoryExemption::KIND_TAXABLE_COMPONENTS && $exemption->component_code === 'income_tax') {
                $only = array_merge($only ?? [], array_map('strval', (array) $exemption->component_codes));
            }
        }

        $sum = 0.0;

        foreach ($lines as $line) {
            if (($line['kind'] ?? null) !== 'earning' || ! ($line['taxable'] ?? false)) {
                continue;
            }

            if ($only !== null && ! in_array((string) ($line['code'] ?? ''), $only, true)) {
                continue;
            }

            $sum += (float) $line['amount'];
        }

        return $sum;
    }

    /**
     * Fixed-amount reliefs that apply to this component and income.
     *
     * @param  Collection<int, StatutoryExemption>  $exemptions
     * @return array{amount: float, basis: list<string>}
     */
    public function reliefFor(string $componentCode, float $income, Collection $exemptions, bool $mainWorkplace = true, bool $monthlyRelief = true): array
    {
        $amount = 0.0;
        $basis = [];

        if (! $monthlyRelief || $income <= 0) {
            return ['amount' => 0.0, 'basis' => []];
        }

        foreach ($exemptions as $exemption) {
            if ($exemption->kind !== StatutoryExemption::KIND_FIXED_AMOUNT || $exemption->component_code !== $componentCode) {
                continue;
            }

            if ($exemption->main_workplace_only && ! $mainWorkplace) {
                continue;
            }

            // «2500 manatadək» — hədd daxildir: 2500,00 güzəşt alır, 2500,01 almır.
            if ($exemption->income_up_to !== null && round($income, 2) > round((float) $exemption->income_up_to, 2)) {
                continue;
            }

            $amount += (float) $exemption->amount;
            $basis[] = $exemption->legal_basis;
        }

        return ['amount' => round(min($amount, $income), 2), 'basis' => $basis];
    }

    /**
     * @return Collection<int, StatutoryExemption>
     */
    public function exemptionsFor(?int $regimeId, ?string $date): Collection
    {
        return $this->exemptions[($regimeId ?? 0).'|'.($date ?? '')] ??= $this->compensation->statutoryExemptionsFor($regimeId, $date);
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

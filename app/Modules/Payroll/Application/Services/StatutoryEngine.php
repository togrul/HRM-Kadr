<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\EmployeeTaxRelief;
use App\Models\StatutoryExemption;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use Illuminate\Support\Collection;

class StatutoryEngine
{
    /**
     * Yalnız DSMF bazasına daxil olan qazanc sətirlərinin kodları: işsizlik və icbari tibbi
     * sığorta bazasına düşmür (məs. yemək normasından artıq hissə — DVX izahı 10.07.2026).
     * Kod config/benefits.php → payroll.imputed_dsmf_code ilə eynidir.
     */
    public const DSMF_ONLY_CODES = ['benefit_in_kind_dsmf'];

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
     * Personal reliefs (VM m.102.1-1, 102.2–102.5 — the employee's relief cards, `personal`
     * exemptions) are passed as $personalReliefs (exemption codes in force for the employee this
     * month). Of 102.1-1 / 102.2 / 102.3 / 102.4 only the largest is given (m.102.7); 102.5 and the
     * 102.1.6 allowance are not in that list and are added on top.
     *
     * $socialBases lets a social-based rate use its own base, keyed by component code (e.g. a
     * serviceman's DSMF base); a component missing from it uses $socialBase.
     *
     * @param  list<string>  $personalReliefs
     * @param  array<string, float>  $socialBases
     * @return array<int,array<string,mixed>>
     */
    public function compute(?int $regimeId, ?string $date, float $taxableBase, float $socialBase, bool $mainWorkplace = true, bool $monthlyRelief = true, array $personalReliefs = [], array $socialBases = []): array
    {
        $lines = [];
        $sort = 100;
        $exemptions = $this->exemptionsFor($regimeId, $date);

        foreach ($this->compensation->statutoryRatesFor($regimeId, $date) as $rate) {
            $base = $rate->base === 'taxable' ? $taxableBase : ($socialBases[(string) $rate->component_code] ?? $socialBase);
            $relief = $rate->base === 'taxable'
                ? $this->reliefFor((string) $rate->component_code, $base, $exemptions, $mainWorkplace, $monthlyRelief, $personalReliefs)
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
     * Ümumi sosial baza (işsizlik, icbari tibbi və digər sosial əsaslı komponentlər): sosial
     * sığortaya cəlb olunan qazanclar, yalnız DSMF-ə aid sətirlər (`DSMF_ONLY_CODES`) çıxılmaqla.
     *
     * @param  array<int,array<string,mixed>>  $lines
     */
    public function socialBase(array $lines): float
    {
        $sum = 0.0;

        foreach ($lines as $line) {
            if (($line['kind'] ?? null) === 'earning' && ($line['affects_social'] ?? false) && ! in_array((string) ($line['code'] ?? ''), self::DSMF_ONLY_CODES, true)) {
                $sum += (float) $line['amount'];
            }
        }

        return $sum;
    }

    /**
     * Per-component social bases where a `taxable_components` rule targets a social-based component
     * (e.g. `dsmf` for servicemen: only the listed pay codes count). Components without such a
     * rule are absent from the result and use the payslip's ordinary social base.
     *
     * @param  array<int,array<string,mixed>>  $lines
     * @return array<string, float>
     */
    public function socialBases(array $lines, ?int $regimeId, ?string $date): array
    {
        $only = [];

        foreach ($this->exemptionsFor($regimeId, $date) as $exemption) {
            if ($exemption->kind === StatutoryExemption::KIND_TAXABLE_COMPONENTS && $exemption->component_code !== 'income_tax') {
                $only[(string) $exemption->component_code] = array_merge($only[(string) $exemption->component_code] ?? [], array_map('strval', (array) $exemption->component_codes));
            }
        }

        $bases = [];

        foreach ($only as $component => $codes) {
            $sum = 0.0;

            foreach ($lines as $line) {
                if (($line['kind'] ?? null) === 'earning' && ($line['affects_social'] ?? false) && in_array((string) ($line['code'] ?? ''), $codes, true)) {
                    $sum += (float) $line['amount'];
                }
            }

            $bases[$component] = $sum;
        }

        // Yalnız DSMF-ə aid sətirlər: DSMF bazası = ümumi baza + bu sətirlər. Hərbçi qaydası
        // (`dsmf` üçün kod siyahısı) artıq varsa, ona toxunulmur — siyahıda bu kodlar yoxdur.
        if (! array_key_exists('dsmf', $bases)) {
            $dsmfOnly = 0.0;

            foreach ($lines as $line) {
                if (($line['kind'] ?? null) === 'earning' && ($line['affects_social'] ?? false) && in_array((string) ($line['code'] ?? ''), self::DSMF_ONLY_CODES, true)) {
                    $dsmfOnly += (float) $line['amount'];
                }
            }

            if ($dsmfOnly != 0.0) {
                $bases['dsmf'] = $this->socialBase($lines) + $dsmfOnly;
            }
        }

        return $bases;
    }

    /**
     * Fixed-amount reliefs that apply to this component and income.
     *
     * Personal (`personal`) exemptions count only when their code is among $personalReliefs; of
     * those in the m.102.7 group (EmployeeTaxRelief::LARGEST_ONLY) only the largest is taken.
     *
     * @param  Collection<int, StatutoryExemption>  $exemptions
     * @param  list<string>  $personalReliefs
     * @return array{amount: float, basis: list<string>}
     */
    public function reliefFor(string $componentCode, float $income, Collection $exemptions, bool $mainWorkplace = true, bool $monthlyRelief = true, array $personalReliefs = []): array
    {
        $amount = 0.0;
        $basis = [];
        $largest = null;

        if (! $monthlyRelief || $income <= 0) {
            return ['amount' => 0.0, 'basis' => []];
        }

        foreach ($exemptions as $exemption) {
            if ($exemption->component_code !== $componentCode) {
                continue;
            }

            if ($exemption->kind === StatutoryExemption::KIND_PERSONAL) {
                // VM m.102.8: yalnız əsas iş yerində; kart işçidə həmin ay qüvvədə olmalıdır.
                if (! in_array($exemption->code, $personalReliefs, true) || ($exemption->main_workplace_only && ! $mainWorkplace)) {
                    continue;
                }

                if (in_array($exemption->code, EmployeeTaxRelief::LARGEST_ONLY, true)) {
                    if ($largest === null || (float) $exemption->amount > (float) $largest->amount) {
                        $largest = $exemption;
                    }

                    continue;
                }

                $amount += (float) $exemption->amount;
                $basis[] = $exemption->legal_basis;

                continue;
            }

            if ($exemption->kind !== StatutoryExemption::KIND_FIXED_AMOUNT) {
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

        if ($largest !== null) {
            $amount += (float) $largest->amount;
            $basis[] = $largest->legal_basis;
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

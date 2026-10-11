<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollOneOffEarning;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Compensation\Domain\Contracts\TaxReliefReadRepository;
use App\Support\Database\InstalledTables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculator
{
    /** @var array<string, bool> tabel_no → main workplace (memo for one run / retro pass) */
    private array $mainWorkplace = [];

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
     * $settleLoans (the employee leaves this pay month; the run paying their last wage): the
     * loan line is the OUTSTANDING balance instead of the instalment, capped so that all
     * non-statutory deductions stay within `payroll.final_settlement_loan_cap_ratio` of the
     * wage due after tax (ƏM m.176.1); what does not fit stays on the loan as a debt.
     * A regular run's instalment is held to the same cap (all deductions, voluntary ones
     * included); what does not fit stays on the loan and the schedule runs longer.
     *
     * Income-tax reliefs (VM m.102 — e.g. m.102.1.6: 200 AZN off a main-workplace monthly
     * income up to 2500 AZN) come from `statutory_exemptions`; the employee's main workplace is
     * `personnels.workplace_type` (anything but `secondary`). $monthlyRelief = false (an
     * off-cycle run, a second payment in the month) leaves the monthly relief out.
     *
     * Son haqq-hesab run-u ($backlog): birdəfəlik ödənişlərdən yalnız ayın özünü deyil, ödəniş
     * ayı bu aya qədər olan hələ ödənilməmiş bütün sətirləri götürür (xitam olunmuş işçi
     * sonrakı adi run-lara düşmür). $extrasOnly: işçinin əmək haqqı artıq ödənilib (və ya
     * şərti bağlanıb) — yalnız həmin ödənilməmiş sətirlər hesablanır; əsas maaş, komponentlər
     * və aya bağlı əmr sətirləri (ikiqat ödəniş olmasın) daxil edilmir, ödənilməmiş sətir
     * yoxdursa nəticə null-dur.
     *
     * @param  list<array<string,mixed>>|null  $orderLines
     * @return array{gross:float,total_deductions:float,net:float,employer_cost:float,proration_factor:float,currency:string,lines:array<int,array<string,mixed>>}|null
     */
    public function calculate(string $tabelNo, ?string $onDate = null, ?int $year = null, ?int $month = null, bool $withOneOffs = true, ?array $orderLines = null, ?int $paidByRunId = null, bool $settleLoans = false, bool $monthlyRelief = true, bool $backlog = false, bool $extrasOnly = false): ?array
    {
        $current = $this->compensation->currentCompensation($tabelNo, $onDate);

        if (! $current) {
            return null;
        }

        // Proration factor (paid days / working days) from attendance for the period.
        $factor = ($year && $month && ! $extrasOnly) ? $this->proration->factorFor($tabelNo, $year, $month) : 1.0;

        $base = round((float) $current->base_amount * $factor, 2);

        $lines = $extrasOnly ? [] : [[
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

        foreach ($extrasOnly ? [] : $this->compensation->componentsFor($tabelNo, $onDate) as $component) {
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
                ->when(
                    $backlog || $extrasOnly,
                    fn ($query) => $query->dueBy($year, $month),
                    fn ($query) => $query->where('pay_year', $year)->where('pay_month', $month),
                )
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

        if ($extrasOnly && $lines === []) {
            return null;
        }

        if ($withOneOffs && $year && $month && ! $extrasOnly) {
            foreach ($orderLines ?? $this->orderEarnings->linesFor($tabelNo, (float) $current->base_amount, $year, $month) as $line) {
                $lines[] = $line;
            }
        }

        // Bases derive from the earning lines and their flags.
        $gross = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning');
        $taxableBase = $this->statutory->taxableBase($lines, $current->regime_id, $onDate);
        $socialBase = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning' && $l['affects_social']);

        $statutoryLines = $this->statutory->compute($current->regime_id, $onDate, $taxableBase, $socialBase, $this->isMainWorkplace($tabelNo), $monthlyRelief, $monthlyRelief ? $this->personalReliefs($tabelNo, $onDate, $year, $month) : [], $this->statutory->socialBases($lines, $current->regime_id, $onDate));
        $lines = array_merge($lines, $statutoryLines);

        // Enforcement documents (icra sənədləri — aliment, məhkəmə qərarı …) come first: within
        // 50 % / 70 % of the wage due after tax (ƏM m.176.2–176.3, «İcra haqqında» m.64–65), by
        // priority. A fixed monthly amount is taken only by the month's own payment; a percentage
        // by every payment, off-cycle included.
        $statutory = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'deduction' && $l['is_statutory']);
        $wageDue = $gross - $statutory;
        // «İcra haqqında» m.68.1.4: ağır və zərərli əmək şəraitinə görə ödənişə tutma yönəldilmir.
        $protected = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'earning' && in_array($l['code'], EnforcementService::PROTECTED_EARNINGS, true));
        $enforcement = $this->enforcement($tabelNo, max(0.0, $wageDue - $protected), $onDate, $year, $month, $monthlyRelief);

        foreach ($enforcement['lines'] as $index => $line) {
            if ($line['amount'] < 0.01) {
                continue;
            }

            $lines[] = [
                'component_id' => null,
                'code' => EnforcementService::LINE_CODE,
                'name' => $line['name'],
                'kind' => 'deduction',
                'amount' => $line['amount'],
                'taxable' => false,
                'affects_social' => false,
                'is_statutory' => false,
                'sort' => 150 + $index,
                'sources' => EnforcementService::encodeSource($line['document_id'], $line['requested']),
            ];
        }

        // Loan / advance repayment — a flat deduction, not prorated, not statutory. Every payment
        // keeps the ordinary non-statutory deductions within ƏM m.176.1 (20 % of the wage due after
        // tax) and, with enforcement documents, all deductions together within their 50 % / 70 %.
        $enforcementTotal = $this->sumWhere($lines, fn ($l) => $l['code'] === EnforcementService::LINE_CODE);
        $otherDeductions = $this->sumWhere($lines, fn ($l) => $l['kind'] === 'deduction' && ! $l['is_statutory'] && $l['code'] !== EnforcementService::LINE_CODE);
        $loan = match (true) {
            $settleLoans => $this->loans->settlement($tabelNo, $wageDue, $otherDeductions),
            $withOneOffs => $this->loans->installmentAmount($tabelNo, $wageDue, $otherDeductions),
            default => ['requested' => 0.0, 'amount' => 0.0],
        };

        if ($enforcementTotal > 0) {
            $loan['amount'] = round(min($loan['amount'], DeductionCap::room($wageDue, $otherDeductions + $enforcementTotal, $enforcement['ratio'])), 2);
        }

        $loanTotal = $loan['amount'];

        if ($loanTotal > 0) {
            $lines[] = [
                'component_id' => null,
                'code' => 'loan',
                'name' => $loanTotal < $loan['requested']
                    ? __('payroll::dashboard.loan.line_capped', [
                        'requested' => number_format($loan['requested'], 2, ',', ' '),
                        'deferred' => number_format($loan['requested'] - $loanTotal, 2, ',', ' '),
                    ])
                    : __('payroll::dashboard.loan.line'),
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
     * VM m.102.1.6 / 102.8: güzəşt yalnız əsas iş yerində. İşçi kartında «İş yeri: əsas və ya
     * əlavə» (`personnels.workplace_type`) `secondary` deyilsə — boş da daxil — əsas iş yeri sayılır.
     */
    private function isMainWorkplace(string $tabelNo): bool
    {
        if (! InstalledTables::hasColumn('personnels', 'workplace_type')) {
            return true;
        }

        return $this->mainWorkplace[$tabelNo] ??= DB::table('personnels')->where('tabel_no', $tabelNo)->value('workplace_type') !== 'secondary';
    }

    /**
     * @return array{ratio: float, room: float, lines: list<array{document_id: int, requested: float, amount: float, name: string}>}
     */
    private function enforcement(string $tabelNo, float $wageDue, ?string $onDate, ?int $year, ?int $month, bool $monthlyPayment): array
    {
        $start = ($year && $month)
            ? Carbon::create($year, $month, 1)->startOfDay()
            : Carbon::parse($onDate ?? now()->toDateString())->startOfMonth();

        return app(EnforcementService::class)->plan($tabelNo, $wageDue, $start->toDateString(), $start->copy()->endOfMonth()->toDateString(), $monthlyPayment);
    }

    /**
     * İşçinin həmin ay qüvvədə olan güzəşt kartları (VM m.102.1-1, 102.2–102.5) — `payroll_from`
     * tarixindən, yəni kilidlənmiş aylardan sonra (TaxReliefService).
     *
     * @return list<string>
     */
    private function personalReliefs(string $tabelNo, ?string $onDate, ?int $year, ?int $month): array
    {
        if (! app()->bound(TaxReliefReadRepository::class)) {
            return [];
        }

        $start = ($year && $month)
            ? Carbon::create($year, $month, 1)->startOfDay()
            : Carbon::parse($onDate ?? now()->toDateString())->startOfMonth();

        return app(TaxReliefReadRepository::class)->codesFor($tabelNo, $start->toDateString(), $start->copy()->endOfMonth()->toDateString());
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

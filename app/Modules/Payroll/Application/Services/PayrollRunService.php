<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollOneOffEarning;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\User;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Integration\Domain\Contracts\PayrollOwnership;
use App\Services\StructureService;
use App\Support\Database\InstalledTables;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PayrollRunService
{
    public function __construct(
        private readonly PayrollCalculator $calculator,
        private readonly CompensationReadRepository $compensation,
        private readonly LoanService $loans,
        private readonly RetroService $retro,
        private readonly PayrollOwnership $ownership,
    ) {}

    /** Run bütövlükdə dəyişir: onun bütün işçiləri aktorun görünürlüyündə olmalıdır. */
    private function guardStructureScope(PayrollRun $run, bool $includeAssignees = false): void
    {
        $actor = auth()->user();
        if (! $actor instanceof User) {
            return;
        }

        $tabelNos = $run->payslips()->pluck('tabel_no');
        if ($includeAssignees) {
            $tabelNos = $tabelNos->merge(array_map('strval', array_keys($this->assignees($run))));
        }

        if (! app(StructureService::class)->allowsAllTabelNos($actor, $tabelNos)) {
            throw new AuthorizationException;
        }
    }

    /**
     * Refuse to compute when the finance system owns payroll.
     *
     * HR does not hold what a payroll calculation needs. It knows the
     * conditions — who is employed, on what base pay, with which allowances,
     * for how many days — but not the progressive tax brackets, the
     * social-insurance rates by sector, the average-earnings rules, the
     * garnishment ceilings, or the accounting periods the result must post
     * into. The finance system holds all of that.
     *
     * Letting both sides compute would produce two answers to the same
     * question, and the only way anyone would find out they disagreed is an
     * employee noticing their payslip. So this refuses loudly instead.
     *
     * Reading stays open: existing runs and payslips remain visible.
     */
    private function guardOwnership(string $action): void
    {
        if ($this->ownership->isOurs()) {
            return;
        }

        throw new RuntimeException(
            "Payroll is computed by the finance system; {$action} is not available here. ".
            'This side supplies the conditions (employment, base pay, allowances, attendance).'
        );
    }

    /**
     * Run lifecycle: draft → calculated → approved → locked; reopen sends an approved or
     * locked run back to calculated.
     * Only draft / calculated runs may be recalculated or deleted.
     *
     * @throws ValidationException
     */
    private function guardStatus(bool $allowed, string $messageKey): void
    {
        if (! $allowed) {
            throw ValidationException::withMessages(['run' => __('payroll::dashboard.messages.'.$messageKey)]);
        }
    }

    /**
     * @throws ValidationException
     */
    public function deleteRun(PayrollRun $run): void
    {
        $this->guardStructureScope($run);

        $this->guardStatus($run->isEditable(), 'not_editable');

        $run->delete();
    }

    /**
     * @throws ValidationException
     */
    public function deletePayslip(Payslip $payslip): void
    {
        if ($payslip->run !== null) {
            $this->guardStructureScope($payslip->run);
        }

        $this->guardStatus((bool) $payslip->run?->isEditable(), 'not_editable');

        $payslip->delete();
    }

    /**
     * A closed pay month is final: no run is created, calculated or reopened in it until the
     * period itself is reopened (PayrollPeriodService::reopen, with a reason).
     *
     * @throws ValidationException
     */
    private function guardPeriodOpen(?PayrollPeriod $period): void
    {
        if ($period !== null && $period->isClosed()) {
            throw ValidationException::withMessages(['run' => __('payroll::dashboard.messages.period_closed')]);
        }
    }

    /**
     * At most one regular run per period and regime: a second one would pay the month again
     * (salary, one-offs, loan instalment). Extra payments go through an off-cycle run. The
     * `regular_slot` unique index backs this up against a concurrent second request.
     *
     * @throws ValidationException
     */
    public function createRun(PayrollPeriod $period, ?int $regimeId = null, ?int $userId = null, string $runType = 'regular'): PayrollRun
    {
        $this->guardPeriodOpen($period);

        $runType = in_array($runType, PayrollRun::RUN_TYPES, true) ? $runType : 'regular';

        if ($runType === 'regular' && PayrollRun::query()->where('regular_slot', PayrollRun::regularSlotFor((int) $period->id, $regimeId))->exists()) {
            throw ValidationException::withMessages(['run' => __('payroll::dashboard.messages.regular_run_exists')]);
        }

        try {
            return PayrollRun::create([
                'payroll_period_id' => $period->id,
                'regime_id' => $regimeId,
                'run_type' => $runType,
                'status' => 'draft',
                'created_by' => $userId,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['run' => __('payroll::dashboard.messages.regular_run_exists')]);
        }
    }

    /**
     * Build payslips for every employee with an active compensation in the run's regime scope.
     */
    public function calculate(PayrollRun $run): PayrollRun
    {
        $this->guardStructureScope($run, true);

        $this->guardOwnership('calculation');

        $this->guardStatus($run->isEditable(), 'not_editable');

        $this->guardPeriodOpen($run->period);

        return DB::transaction(function () use ($run): PayrollRun {
            $run->payslips()->delete();

            $totals = ['gross' => 0.0, 'deductions' => 0.0, 'net' => 0.0, 'employer' => 0.0, 'count' => 0];

            foreach ($this->computePayslips($run) as $tabelNo => $computed) {
                $payslip = $run->payslips()->create($computed['attributes'] + ['tabel_no' => $tabelNo, 'status' => 'calculated']);

                foreach ($computed['lines'] as $line) {
                    $payslip->lines()->create($line);
                }

                $totals['gross'] += $computed['attributes']['gross'];
                $totals['deductions'] += $computed['attributes']['total_deductions'];
                $totals['net'] += $computed['attributes']['net'];
                $totals['employer'] += $computed['attributes']['employer_cost'];
                $totals['count']++;
            }

            $run->update([
                'status' => 'calculated',
                'gross_total' => round($totals['gross'], 2),
                'deduction_total' => round($totals['deductions'], 2),
                'net_total' => round($totals['net'], 2),
                'employer_total' => round($totals['employer'], 2),
                'employee_count' => $totals['count'],
                'calculated_at' => now(),
            ]);

            return $run->refresh();
        });
    }

    /**
     * What each employee in the run's scope is due right now: payslip attributes and lines,
     * keyed by tabel_no. Used by calculation and, at lock, to prove nothing moved since.
     *
     * @return array<string,array{attributes:array<string,mixed>,lines:list<array<string,mixed>>}>
     */
    private function computePayslips(PayrollRun $run): array
    {
        $year = (int) $run->period->year;
        $month = (int) $run->period->month;
        $regular = $run->run_type === 'regular';
        $leavers = $this->leaversInPeriod($run->period);
        $payslips = [];

        foreach ($this->assignees($run) as $tabelNo => $onDate) {
            $tabelNo = (string) $tabelNo;
            // Kredit: adi run aylıq taksiti tutur; off-cycle heç nə tutmur (ikiqat tutulma
            // olmasın). İşçinin son əmək haqqını ödəyən run (bu ay çıxan işçi üçün adi run və
            // ya final_settlement) taksit yox, qalan borcu ƏM m.176.1 həddinə qədər tutur.
            $settleLoans = $run->run_type !== 'off_cycle' && isset($leavers[$tabelNo]);
            $calc = $this->calculator->calculate($tabelNo, $onDate, $year, $month, $regular, null, (int) $run->id, $settleLoans);

            if (! $calc) {
                continue;
            }

            $lines = $calc['lines'];
            $gross = $calc['gross'];
            $net = $calc['net'];
            $deductions = $calc['total_deductions'];

            // Back-pay owed from prior locked periods (net top-up, already net-of-tax). The
            // line names the periods it settles, so locking books exactly this.
            $pendingRetro = $this->retro->pendingRetro($tabelNo, (int) $run->id);
            $retroTotal = $pendingRetro['total'];

            if ($retroTotal > 0.0) {
                $lines[] = [
                    'component_id' => null,
                    'code' => RetroService::RETRO,
                    'name' => __('payroll::dashboard.fields.retro'),
                    'kind' => 'earning',
                    'amount' => $retroTotal,
                    'taxable' => false,
                    'affects_social' => false,
                    'is_statutory' => false,
                    'sort' => 300,
                    'sources' => RetroService::encodeSources($pendingRetro['lines'], 'payment', $retroTotal),
                ];

                $gross = round($gross + $retroTotal, 2);
                $net = round($net + $retroTotal, 2);
            }

            // Order pay a locked month no longer stands behind (revoked order) is taken
            // back on the next regular run — only where the employee's written consent
            // allows it (ƏM m.175.1/175.5, config payroll.recover_revoked_order_pay) and
            // never above 20 % of the wage due (m.176.1); the rest stays pending.
            $recovery = $regular && config('payroll.recover_revoked_order_pay', false)
                ? round(min($pendingRetro['recovery'], max(0.0, $net) * (float) config('payroll.recovery_cap_ratio', 0.20)), 2)
                : 0.0;

            if ($recovery >= 0.01) {
                $lines[] = [
                    'component_id' => null,
                    'code' => RetroService::RECOVERY,
                    'name' => __('payroll::dashboard.fields.retro_recovery'),
                    'kind' => 'deduction',
                    'amount' => $recovery,
                    'taxable' => false,
                    'affects_social' => false,
                    'is_statutory' => false,
                    'sort' => 310,
                    'sources' => RetroService::encodeSources($pendingRetro['lines'], 'recovery', $recovery),
                ];

                $net = round($net - $recovery, 2);
                $deductions = round($deductions + $recovery, 2);
            }

            $payslips[(string) $tabelNo] = [
                'attributes' => [
                    'regime_id' => $run->regime_id,
                    'gross' => $gross,
                    'total_deductions' => $deductions,
                    'net' => $net,
                    'employer_cost' => $calc['employer_cost'],
                    'proration_factor' => $calc['proration_factor'],
                    'currency' => $calc['currency'],
                ],
                'lines' => $lines,
            ];
        }

        return $payslips;
    }

    /**
     * Whom the run pays, tabel_no → the date compensation is read on.
     *  - regular / off_cycle: everyone with an active compensation on the period's last day;
     *  - final_settlement: those who leave within the period and are NOT in the regular run
     *    any more (their compensation ended with the employment), priced on their last day.
     *    Someone whose compensation still runs past month end is paid — and settled — by the
     *    regular run, so the two never pay the same month twice.
     *
     * @return array<string,string>
     */
    private function assignees(PayrollRun $run): array
    {
        $periodEnd = $run->period->ends_on->toDateString();
        $active = $this->compensation->activeAssignees($run->regime_id, $periodEnd)->map(fn ($tabelNo): string => (string) $tabelNo);

        if ($run->run_type !== 'final_settlement') {
            return $active->mapWithKeys(fn (string $tabelNo): array => [$tabelNo => $periodEnd])->all();
        }

        $assignees = [];
        foreach ($this->leaversInPeriod($run->period) as $tabelNo => $lastDay) {
            if ($active->contains($tabelNo)) {
                continue;
            }

            if ($this->compensation->activeAssignees($run->regime_id, $lastDay)->map(fn ($no): string => (string) $no)->contains($tabelNo)) {
                $assignees[$tabelNo] = $lastDay;
            }
        }

        return $assignees;
    }

    /**
     * Employees whose last working day falls in the pay month: tabel_no → last day.
     *
     * @return array<string,string>
     */
    private function leaversInPeriod(PayrollPeriod $period): array
    {
        if (! InstalledTables::has('personnels')) {
            return [];
        }

        return DB::table('personnels')
            ->whereNull('deleted_at')
            ->whereNotNull('leave_work_date')
            ->whereDate('leave_work_date', '>=', $period->starts_on->toDateString())
            ->whereDate('leave_work_date', '<=', $period->ends_on->toDateString())
            ->pluck('leave_work_date', 'tabel_no')
            ->mapWithKeys(fn ($date, $tabelNo): array => [(string) $tabelNo => substr((string) $date, 0, 10)])
            ->all();
    }

    /**
     * Locking books what the payslips say (loan repayments, retro, one-offs paid). If any
     * pay input moved since the calculation — compensation, attendance, rates, a retro that
     * arose meanwhile, an employee added or gone — the payslips no longer match and the run
     * is refused until it is recalculated.
     *
     * @throws ValidationException
     */
    private function guardPayUnchangedSinceCalculation(PayrollRun $run): void
    {
        $stored = $run->payslips()->with('lines')->get()->keyBy(fn (Payslip $payslip): string => (string) $payslip->tabel_no);
        $now = $this->computePayslips($run);

        $changed = collect(array_keys($now))->map(fn ($key): string => (string) $key)->sort()->values()->all()
            !== $stored->keys()->map(fn ($key): string => (string) $key)->sort()->values()->all();

        foreach ($now as $tabelNo => $computed) {
            if ($changed) {
                break;
            }

            $payslip = $stored->get($tabelNo);
            if ($payslip === null) {
                $changed = true;

                break;
            }

            foreach (['gross' => 'gross', 'total_deductions' => 'total_deductions', 'net' => 'net'] as $attribute => $column) {
                if (abs(round((float) $computed['attributes'][$attribute], 2) - round((float) $payslip->getAttribute($column), 2)) >= 0.01) {
                    $changed = true;

                    break 2;
                }
            }

            foreach ([RetroService::RETRO, RetroService::RECOVERY] as $code) {
                $then = RetroService::decodeSources($payslip->lines->firstWhere('code', $code)?->sources);
                $line = collect($computed['lines'])->firstWhere('code', $code);
                $nowSources = RetroService::decodeSources($line['sources'] ?? null);
                ksort($then);
                ksort($nowSources);

                if ($then !== [] && $then != $nowSources) {
                    $changed = true;

                    break 2;
                }
            }
        }

        if ($changed) {
            throw ValidationException::withMessages(['run' => __('payroll::dashboard.messages.pay_changed')]);
        }
    }

    public function approve(PayrollRun $run): PayrollRun
    {
        $this->guardStructureScope($run);

        $this->guardOwnership('approval');

        $this->guardStatus($run->status === 'calculated', 'approve_requires_calculated');

        $run->update(['status' => 'approved', 'approved_at' => now()]);

        return $run;
    }

    /**
     * Lock the run and freeze each payslip's inputs into an immutable snapshot.
     */
    /**
     * Locking marks the month's one-off earnings paid, so each of them must be on the
     * payslips being locked: one handed over or changed after the calculation is not.
     *
     * @throws ValidationException
     */
    private function guardOneOffsUnchangedSinceCalculation(PayrollRun $run): void
    {
        if ($run->run_type !== 'regular' || $run->calculated_at === null || ! InstalledTables::has('payroll_one_off_earnings')) {
            return;
        }

        // ponytail: second-precision timestamps; a hand-off in the calculation's own second slips through.
        $changed = PayrollOneOffEarning::query()
            ->whereIn('tabel_no', $run->payslips()->select('tabel_no'))
            ->where('pay_year', (int) $run->period->year)
            ->where('pay_month', (int) $run->period->month)
            ->whereNull('paid_payroll_run_id')
            ->where('updated_at', '>', $run->calculated_at)
            ->exists();

        if ($changed) {
            throw ValidationException::withMessages(['run' => __('payroll::dashboard.messages.recalculate_first')]);
        }
    }

    /**
     * Order-derived lines (rest-day work, substitution) are read live at calculation; an
     * order approved, changed or revoked afterwards would otherwise be locked in wrong.
     * Each such line names the records it was built from, so the run is compared with the
     * order facts now and refused until it is recalculated.
     *
     * @throws ValidationException
     */
    private function guardOrderEarningsUnchangedSinceCalculation(PayrollRun $run): void
    {
        if ($run->run_type !== 'regular') {
            return;
        }

        $onDate = $run->period->ends_on->toDateString();
        $year = (int) $run->period->year;
        $month = (int) $run->period->month;

        foreach ($run->payslips()->with('lines')->get() as $payslip) {
            $calculated = OrderEarningsService::signature($payslip->lines->map(fn ($line): array => [
                'code' => $line->code,
                'amount' => $line->amount,
                'sources' => $line->sources,
            ]));
            $now = OrderEarningsService::signature($this->calculator->orderEarningLines((string) $payslip->getAttribute('tabel_no'), $onDate, $year, $month));

            if ($calculated !== $now) {
                throw ValidationException::withMessages(['run' => __('payroll::dashboard.messages.order_earnings_changed')]);
            }
        }
    }

    public function lock(PayrollRun $run): PayrollRun
    {
        $this->guardStructureScope($run, true);

        $this->guardOwnership('locking');

        $this->guardStatus($run->status === 'approved', 'lock_requires_approval');

        $this->guardOneOffsUnchangedSinceCalculation($run);

        $this->guardOrderEarningsUnchangedSinceCalculation($run);

        $this->guardPayUnchangedSinceCalculation($run);

        return DB::transaction(function () use ($run): PayrollRun {
            $run->payslips()->with('lines')->get()->each(function (Payslip $payslip): void {
                $payslip->update([
                    'status' => 'locked',
                    'snapshot' => [
                        'gross' => $payslip->gross,
                        'total_deductions' => $payslip->total_deductions,
                        'net' => $payslip->net,
                        'employer_cost' => $payslip->employer_cost,
                        'currency' => $payslip->currency,
                        'lines' => $payslip->lines->map(fn ($line): array => [
                            'code' => $line->code,
                            'name' => $line->name,
                            'kind' => $line->kind,
                            'amount' => $line->amount,
                        ])->all(),
                    ],
                ]);
            });

            $this->loans->recordRepaymentsForRun($run);
            $this->retro->recordRetroPayments($run);

            if ($run->run_type === 'regular' && InstalledTables::has('payroll_one_off_earnings')) {
                PayrollOneOffEarning::query()
                    ->whereIn('tabel_no', $run->payslips()->pluck('tabel_no'))
                    ->where('pay_year', (int) $run->period->year)
                    ->where('pay_month', (int) $run->period->month)
                    ->whereNull('paid_payroll_run_id')
                    ->update(['paid_payroll_run_id' => $run->id]);
            }

            $run->update(['status' => 'locked', 'locked_at' => now()]);

            return $run->refresh();
        });
    }

    /**
     * Send an approved or locked run back to calculated, undoing what locking booked (loan
     * repayments, retro ledger, one-offs marked paid) in one transaction. Refused in a closed
     * pay month: reopen the period first (with a reason).
     *
     * @throws ValidationException
     */
    public function reopen(PayrollRun $run): PayrollRun
    {
        $this->guardStructureScope($run);

        $this->guardStatus(in_array($run->status, ['approved', 'locked'], true), 'reopen_not_allowed');

        $this->guardPeriodOpen($run->period);

        return DB::transaction(function () use ($run): PayrollRun {
            $locked = PayrollRun::query()->whereKey($run->id)->lockForUpdate()->first();
            $this->guardStatus($locked !== null && in_array($locked->status, ['approved', 'locked'], true), 'reopen_not_allowed');

            $this->loans->reverseRepaymentsForRun($run);
            $this->retro->reverseRetroPayments($run);

            if (InstalledTables::has('payroll_one_off_earnings')) {
                PayrollOneOffEarning::query()->where('paid_payroll_run_id', $run->id)->update(['paid_payroll_run_id' => null]);
            }

            $run->update(['status' => 'calculated', 'approved_at' => null, 'locked_at' => null]);
            $run->payslips()->update(['status' => 'calculated']);

            return $run;
        });
    }
}

<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollOneOffEarning;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Modules\Integration\Domain\Contracts\PayrollOwnership;
use App\Support\Database\InstalledTables;
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
        $this->guardStatus($run->isEditable(), 'not_editable');

        $run->delete();
    }

    /**
     * @throws ValidationException
     */
    public function deletePayslip(Payslip $payslip): void
    {
        $this->guardStatus((bool) $payslip->run?->isEditable(), 'not_editable');

        $payslip->delete();
    }

    public function createRun(PayrollPeriod $period, ?int $regimeId = null, ?int $userId = null, string $runType = 'regular'): PayrollRun
    {
        return PayrollRun::create([
            'payroll_period_id' => $period->id,
            'regime_id' => $regimeId,
            'run_type' => in_array($runType, ['regular', 'off_cycle'], true) ? $runType : 'regular',
            'status' => 'draft',
            'created_by' => $userId,
        ]);
    }

    /**
     * Build payslips for every employee with an active compensation in the run's regime scope.
     */
    public function calculate(PayrollRun $run): PayrollRun
    {
        $this->guardOwnership('calculation');

        $this->guardStatus($run->isEditable(), 'not_editable');

        $onDate = $run->period->ends_on->toDateString();
        $year = (int) $run->period->year;
        $month = (int) $run->period->month;

        return DB::transaction(function () use ($run, $onDate, $year, $month): PayrollRun {
            $run->payslips()->delete();

            $totals = ['gross' => 0.0, 'deductions' => 0.0, 'net' => 0.0, 'employer' => 0.0, 'count' => 0];

            foreach ($this->compensation->activeAssignees($run->regime_id, $onDate) as $tabelNo) {
                $calc = $this->calculator->calculate($tabelNo, $onDate, $year, $month, $run->run_type === 'regular');

                if (! $calc) {
                    continue;
                }

                $payslip = $run->payslips()->create([
                    'tabel_no' => $tabelNo,
                    'regime_id' => $run->regime_id,
                    'gross' => $calc['gross'],
                    'total_deductions' => $calc['total_deductions'],
                    'net' => $calc['net'],
                    'employer_cost' => $calc['employer_cost'],
                    'proration_factor' => $calc['proration_factor'],
                    'currency' => $calc['currency'],
                    'status' => 'calculated',
                ]);

                foreach ($calc['lines'] as $line) {
                    $payslip->lines()->create($line);
                }

                // Back-pay owed from prior locked periods (net top-up, already net-of-tax).
                $pendingRetro = $this->retro->pendingRetro($tabelNo);
                $retroTotal = $pendingRetro['total'];
                $gross = $calc['gross'];
                $net = $calc['net'];
                $deductions = $calc['total_deductions'];

                if ($retroTotal > 0.0) {
                    $payslip->lines()->create([
                        'component_id' => null,
                        'code' => RetroService::RETRO,
                        'name' => __('payroll::dashboard.fields.retro'),
                        'kind' => 'earning',
                        'amount' => $retroTotal,
                        'taxable' => false,
                        'affects_social' => false,
                        'is_statutory' => false,
                        'sort' => 300,
                    ]);

                    $gross = round($gross + $retroTotal, 2);
                    $net = round($net + $retroTotal, 2);
                    $payslip->update(['gross' => $gross, 'net' => $net]);
                }

                // Order pay a locked month no longer stands behind (revoked order) is taken
                // back on the next regular run — only where the employee's written consent
                // allows it (ƏM m.175.1/175.5, config payroll.recover_revoked_order_pay) and
                // never above 20 % of the wage due (m.176.1); the rest stays pending.
                $recovery = $run->run_type === 'regular' && config('payroll.recover_revoked_order_pay', false)
                    ? round(min($pendingRetro['recovery'], max(0.0, $net) * (float) config('payroll.recovery_cap_ratio', 0.20)), 2)
                    : 0.0;

                if ($recovery >= 0.01) {
                    $payslip->lines()->create([
                        'component_id' => null,
                        'code' => RetroService::RECOVERY,
                        'name' => __('payroll::dashboard.fields.retro_recovery'),
                        'kind' => 'deduction',
                        'amount' => $recovery,
                        'taxable' => false,
                        'affects_social' => false,
                        'is_statutory' => false,
                        'sort' => 310,
                    ]);

                    $net = round($net - $recovery, 2);
                    $deductions = round($deductions + $recovery, 2);
                    $payslip->update(['total_deductions' => $deductions, 'net' => $net]);
                }

                $totals['gross'] += $gross;
                $totals['deductions'] += $deductions;
                $totals['net'] += $net;
                $totals['employer'] += $calc['employer_cost'];
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

    public function approve(PayrollRun $run): PayrollRun
    {
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
        $this->guardOwnership('locking');

        $this->guardStatus($run->status === 'approved', 'lock_requires_approval');

        $this->guardOneOffsUnchangedSinceCalculation($run);

        $this->guardOrderEarningsUnchangedSinceCalculation($run);

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

    public function reopen(PayrollRun $run): PayrollRun
    {
        $this->guardStatus(in_array($run->status, ['approved', 'locked'], true), 'reopen_not_allowed');

        $this->loans->reverseRepaymentsForRun($run);
        $this->retro->reverseRetroPayments($run);

        if (InstalledTables::has('payroll_one_off_earnings')) {
            PayrollOneOffEarning::query()->where('paid_payroll_run_id', $run->id)->update(['paid_payroll_run_id' => null]);
        }

        $run->update(['status' => 'calculated', 'approved_at' => null, 'locked_at' => null]);
        $run->payslips()->update(['status' => 'calculated']);

        return $run;
    }
}

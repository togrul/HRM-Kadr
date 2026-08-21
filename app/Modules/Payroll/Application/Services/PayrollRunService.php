<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollRunService
{
    public function __construct(
        private readonly PayrollCalculator $calculator,
        private readonly CompensationReadRepository $compensation,
        private readonly LoanService $loans,
        private readonly RetroService $retro,
    ) {}

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
        if ($run->isLocked()) {
            throw new RuntimeException('A locked payroll run cannot be recalculated.');
        }

        $onDate = $run->period->ends_on->toDateString();
        $year = (int) $run->period->year;
        $month = (int) $run->period->month;

        return DB::transaction(function () use ($run, $onDate, $year, $month): PayrollRun {
            $run->payslips()->delete();

            $totals = ['gross' => 0.0, 'deductions' => 0.0, 'net' => 0.0, 'employer' => 0.0, 'count' => 0];

            foreach ($this->compensation->activeAssignees($run->regime_id, $onDate) as $tabelNo) {
                $calc = $this->calculator->calculate($tabelNo, $onDate, $year, $month);

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
                $retroTotal = $this->retro->pendingRetro($tabelNo)['total'];
                $gross = $calc['gross'];
                $net = $calc['net'];

                if ($retroTotal > 0.0) {
                    $payslip->lines()->create([
                        'component_id' => null,
                        'code' => 'retro',
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

                $totals['gross'] += $gross;
                $totals['deductions'] += $calc['total_deductions'];
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
        if ($run->status !== 'calculated') {
            throw new RuntimeException('Only a calculated run can be approved.');
        }

        $run->update(['status' => 'approved', 'approved_at' => now()]);

        return $run;
    }

    /**
     * Lock the run and freeze each payslip's inputs into an immutable snapshot.
     */
    public function lock(PayrollRun $run): PayrollRun
    {
        if (! in_array($run->status, ['calculated', 'approved'], true)) {
            throw new RuntimeException('Only a calculated or approved run can be locked.');
        }

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

            $run->update(['status' => 'locked', 'locked_at' => now()]);

            return $run->refresh();
        });
    }

    public function reopen(PayrollRun $run): PayrollRun
    {
        $this->loans->reverseRepaymentsForRun($run);
        $this->retro->reverseRetroPayments($run);

        $run->update(['status' => 'calculated', 'approved_at' => null, 'locked_at' => null]);
        $run->payslips()->update(['status' => 'calculated']);

        return $run;
    }
}

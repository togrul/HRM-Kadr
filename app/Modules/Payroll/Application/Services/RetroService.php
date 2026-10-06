<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\RetroPayment;
use Illuminate\Support\Facades\DB;

class RetroService
{
    public function __construct(private readonly PayrollCalculator $calculator) {}

    /**
     * Outstanding retro for an employee: for each LOCKED (paid) payslip, recompute the net with
     * the compensation/rates now effective as-of that period, compare to what was paid, and
     * subtract any retro already paid out for that source period. Positive remainders are owed.
     *
     * @return array{lines:array<int,array<string,mixed>>,total:float}
     */
    public function pendingRetro(string $tabelNo): array
    {
        $lines = [];
        $total = 0.0;

        $payslips = Payslip::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'locked')
            ->with('run.period')
            ->get();

        foreach ($payslips as $payslip) {
            $period = $payslip->run?->period;
            $sourceRunId = $payslip->payroll_run_id;

            if (! $period) {
                continue;
            }

            $recalc = $this->calculator->calculate($tabelNo, $period->ends_on->toDateString(), (int) $period->year, (int) $period->month);

            if (! $recalc) {
                continue;
            }

            $paidNet = (float) ($payslip->snapshot['net'] ?? $payslip->net);
            $alreadyPaid = (float) RetroPayment::query()
                ->where('tabel_no', $tabelNo)
                ->where('source_payroll_run_id', $sourceRunId)
                ->sum('amount');

            $delta = round($recalc['net'] - $paidNet - $alreadyPaid, 2);

            if ($delta < 0.01) {
                continue;
            }

            $lines[] = [
                'source_run_id' => $sourceRunId,
                'period_code' => $period->code,
                'paid_net' => round($paidNet, 2),
                'recomputed_net' => $recalc['net'],
                'delta' => $delta,
            ];
            $total += $delta;
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }

    /**
     * Record the run's retro pay-outs into the ledger (idempotent per source+paying run).
     */
    public function recordRetroPayments(PayrollRun $run): void
    {
        $paidOn = $run->period->ends_on->toDateString();
        $tabelNos = $run->payslips()->pluck('tabel_no')->unique();

        DB::transaction(function () use ($run, $tabelNos, $paidOn): void {
            foreach ($tabelNos as $tabelNo) {
                foreach ($this->pendingRetro($tabelNo)['lines'] as $line) {
                    RetroPayment::query()->updateOrCreate(
                        [
                            'tabel_no' => $tabelNo,
                            'source_payroll_run_id' => $line['source_run_id'],
                            'paid_payroll_run_id' => $run->id,
                        ],
                        ['amount' => $line['delta'], 'paid_on' => $paidOn],
                    );
                }
            }
        });
    }

    public function reverseRetroPayments(PayrollRun $run): void
    {
        RetroPayment::query()->where('paid_payroll_run_id', $run->id)->delete();
    }
}

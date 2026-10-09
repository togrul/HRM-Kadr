<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\RetroPayment;
use Illuminate\Support\Facades\DB;

class RetroService
{
    public function __construct(private readonly PayrollCalculator $calculator) {}

    /** Codes of the retro lines a run puts on a payslip (they are not part of what a period earned). */
    public const RETRO = 'retro';

    public const RECOVERY = 'retro_recovery';

    /**
     * Outstanding retro for an employee: for each LOCKED (paid) payslip, recompute the net with
     * the compensation/rates now effective as-of that period, compare to what was paid, and
     * subtract any retro already settled for that source period. Positive remainders are owed
     * (`total`).
     *
     * A negative remainder is recovered (`recovery`, a deduction on the next regular run) only
     * for the part caused by order-derived earnings that no longer stand — e.g. a rest-day
     * work or substitution order revoked after its month was paid. Other negative differences
     * (a retroactive pay cut, a rate change) stay unrecovered as before: wages already paid
     * are not clawed back on a recalculation.
     *
     * @return array{lines:array<int,array<string,mixed>>,total:float,recovery:float}
     */
    public function pendingRetro(string $tabelNo): array
    {
        $lines = [];
        $total = 0.0;
        $recovery = 0.0;

        $payslips = Payslip::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'locked')
            ->with(['run.period', 'lines'])
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

            // The paying run's own retro lines settle other periods; they are not this period's pay.
            $paidNet = (float) ($payslip->snapshot['net'] ?? $payslip->net)
                - (float) $payslip->lines->where('code', self::RETRO)->sum('amount')
                + (float) $payslip->lines->where('code', self::RECOVERY)->sum('amount');
            $alreadyPaid = (float) RetroPayment::query()
                ->where('tabel_no', $tabelNo)
                ->where('source_payroll_run_id', $sourceRunId)
                ->sum('amount');

            $delta = round($recalc['net'] - $paidNet - $alreadyPaid, 2);

            if ($delta <= -0.01) {
                $recover = $this->orderRecovery($payslip, $tabelNo, $period->ends_on->toDateString(), (int) $period->year, (int) $period->month, $recalc['net'], $delta);

                if ($recover <= -0.01) {
                    $lines[] = [
                        'source_run_id' => $sourceRunId,
                        'period_code' => $period->code,
                        'paid_net' => round($paidNet, 2),
                        'recomputed_net' => $recalc['net'],
                        'delta' => $recover,
                        'kind' => 'recovery',
                    ];
                    $recovery += -$recover;
                }

                continue;
            }

            if ($delta < 0.01) {
                continue;
            }

            $lines[] = [
                'source_run_id' => $sourceRunId,
                'period_code' => $period->code,
                'paid_net' => round($paidNet, 2),
                'recomputed_net' => $recalc['net'],
                'delta' => $delta,
                'kind' => 'payment',
            ];
            $total += $delta;
        }

        return ['lines' => $lines, 'total' => round($total, 2), 'recovery' => round($recovery, 2)];
    }

    /**
     * The negative part of $delta caused by order-derived earnings: the net now minus the
     * net the period would have now had the order lines stayed as paid, never more than
     * the whole shortfall. Zero when the orders behind the period did not lose pay, and
     * zero when the same order records still stand (an amount that only followed a
     * retroactive base-pay change is a pay cut, which is not clawed back).
     */
    private function orderRecovery(Payslip $payslip, string $tabelNo, string $onDate, int $year, int $month, float $recalcNet, float $delta): float
    {
        $paidOrderLines = $payslip->lines
            ->filter(fn ($line): bool => in_array($line->code, OrderEarningsService::CODES, true))
            ->map(fn ($line): array => [
                'component_id' => null,
                'code' => $line->code,
                'name' => $line->name,
                'kind' => 'earning',
                'amount' => (float) $line->amount,
                'taxable' => (bool) $line->taxable,
                'affects_social' => (bool) $line->affects_social,
                'is_statutory' => false,
                'sort' => (int) $line->sort,
            ])
            ->values()
            ->all();

        $paidSources = $this->sources($payslip->lines->map(fn ($line): array => ['code' => $line->code, 'sources' => $line->sources])->all());
        $currentSources = $this->sources($this->calculator->orderEarningLines($tabelNo, $onDate, $year, $month));

        if ($paidSources === $currentSources) {
            return 0.0;
        }

        $asPaid = $this->calculator->calculate($tabelNo, $onDate, $year, $month, true, $paidOrderLines);

        if (! $asPaid) {
            return 0.0;
        }

        $orderDelta = round($recalcNet - $asPaid['net'], 2);

        return $orderDelta < 0 ? max($delta, $orderDelta) : 0.0;
    }

    /**
     * @param  iterable<array<string,mixed>>  $lines
     * @return list<string>
     */
    private function sources(iterable $lines): array
    {
        $sources = [];

        foreach ($lines as $line) {
            if (in_array($line['code'] ?? null, OrderEarningsService::CODES, true)) {
                array_push($sources, ...array_values((array) ($line['sources'] ?? [])));
            }
        }

        sort($sources);

        return $sources;
    }

    /**
     * Record the run's retro pay-outs into the ledger (idempotent per source+paying run).
     * Recoveries are recorded as negative amounts, only up to what the run's payslip
     * actually deducted (`retro_recovery` line); the rest stays pending for a later run.
     */
    public function recordRetroPayments(PayrollRun $run): void
    {
        $paidOn = $run->period->ends_on->toDateString();
        $deducted = $run->payslips()
            ->join('payslip_lines', 'payslip_lines.payslip_id', '=', 'payslips.id')
            ->where('payslip_lines.code', self::RECOVERY)
            ->groupBy('payslips.tabel_no')
            ->selectRaw('payslips.tabel_no as tabel_no, sum(payslip_lines.amount) as amount')
            ->pluck('amount', 'tabel_no');
        $tabelNos = $run->payslips()->pluck('tabel_no')->unique();

        DB::transaction(function () use ($run, $tabelNos, $paidOn, $deducted): void {
            foreach ($tabelNos as $tabelNo) {
                $remaining = round((float) ($deducted[$tabelNo] ?? 0), 2);

                foreach ($this->pendingRetro($tabelNo)['lines'] as $line) {
                    if (($line['kind'] ?? 'payment') === 'recovery') {
                        $take = round(min(-$line['delta'], $remaining), 2);
                        $remaining = round($remaining - $take, 2);

                        if ($take < 0.01) {
                            continue;
                        }

                        $line['delta'] = -$take;
                    }

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

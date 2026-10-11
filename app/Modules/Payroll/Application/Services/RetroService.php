<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\PayrollPeriod;
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

    /** Code of the loan instalment line (PayrollCalculator). */
    public const LOAN = 'loan';

    /** Code of an enforcement-document deduction line (EnforcementService). */
    public const ENFORCEMENT = EnforcementService::LINE_CODE;

    /**
     * Outstanding retro for an employee: for each LOCKED (paid) payslip of a regular run,
     * recompute the net with the compensation/rates now effective as-of that period, compare
     * to what was paid, and subtract any retro already settled for that source period.
     * Positive remainders are owed (`total`).
     *
     * A month paid by a final_settlement run is a source only for order-derived pay that
     * arose after it was locked (settlementOrderBackPay).
     *
     * Off-cycle payslips are not sources: they pay a supplement, not the month, so comparing
     * them with a recomputed month would invent a difference. $excludeRunId leaves a run's
     * own payslips out (the run being calculated or locked never owes itself retro). Loan
     * instalments and enforcement-document deductions are left out of both sides: a loan or a
     * court order closed or received since is not pay.
     *
     * A negative remainder is recovered (`recovery`, a deduction on the next regular run) only
     * for the part caused by order-derived earnings that no longer stand — e.g. a rest-day
     * work or substitution order revoked after its month was paid. Other negative differences
     * (a retroactive pay cut, a rate change) stay unrecovered as before: wages already paid
     * are not clawed back on a recalculation.
     *
     * @return array{lines:array<int,array<string,mixed>>,total:float,recovery:float}
     */
    public function pendingRetro(string $tabelNo, ?int $excludeRunId = null): array
    {
        $lines = [];
        $total = 0.0;
        $recovery = 0.0;

        $payslips = Payslip::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'locked')
            ->whereHas('run', fn ($query) => $query->whereIn('run_type', ['regular', 'final_settlement']))
            ->when($excludeRunId !== null, fn ($query) => $query->where('payroll_run_id', '!=', $excludeRunId))
            ->with(['run.period', 'lines'])
            ->orderBy('payroll_run_id')
            ->get();

        foreach ($payslips as $payslip) {
            $period = $payslip->run?->period;
            $sourceRunId = (int) $payslip->payroll_run_id;

            if (! $period) {
                continue;
            }

            if ($payslip->run->run_type === 'final_settlement') {
                $line = $this->settlementOrderBackPay($payslip, $tabelNo, $period);

                if ($line !== null) {
                    $lines[] = $line;
                    $total += $line['delta'];
                }

                continue;
            }

            $recalc = $this->calculator->calculate($tabelNo, $period->ends_on->toDateString(), (int) $period->year, (int) $period->month, true, null, $sourceRunId);

            if (! $recalc) {
                continue;
            }

            // Kredit taksiti və icra sənədi tutulmaları əmək haqqı deyil — hər iki tərəfdən çıxarılır.
            $recalcNet = round($recalc['net'] + $this->sumCode($recalc['lines'], self::LOAN) + $this->sumCode($recalc['lines'], self::ENFORCEMENT), 2);

            // The paying run's own retro lines settle other periods; they are not this period's pay.
            $paidNet = round((float) ($payslip->snapshot['net'] ?? $payslip->net)
                - (float) $payslip->lines->where('code', self::RETRO)->sum('amount')
                + (float) $payslip->lines->where('code', self::RECOVERY)->sum('amount')
                + (float) $payslip->lines->where('code', self::LOAN)->sum('amount')
                + (float) $payslip->lines->where('code', self::ENFORCEMENT)->sum('amount'), 2);
            $alreadyPaid = (float) RetroPayment::query()
                ->where('tabel_no', $tabelNo)
                ->where('source_payroll_run_id', $sourceRunId)
                ->sum('amount');

            $delta = round($recalcNet - $paidNet - $alreadyPaid, 2);

            if ($delta <= -0.01) {
                $recover = $this->orderRecovery($payslip, $tabelNo, $period->ends_on->toDateString(), (int) $period->year, (int) $period->month, $recalcNet, $delta);

                if ($recover <= -0.01) {
                    $lines[] = [
                        'source_run_id' => $sourceRunId,
                        'period_code' => $period->code,
                        'paid_net' => $paidNet,
                        'recomputed_net' => $recalcNet,
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
                'paid_net' => $paidNet,
                'recomputed_net' => $recalcNet,
                'delta' => $delta,
                'kind' => 'payment',
            ];
            $total += $delta;
        }

        return ['lines' => $lines, 'total' => round($total, 2), 'recovery' => round($recovery, 2)];
    }

    /**
     * The sources a retro or recovery payslip line records: "source run id:amount" per
     * period it settles, so locking books exactly what the payslip pays.
     *
     * @param  array<int,array<string,mixed>>  $lines  pendingRetro() lines
     * @return list<string>
     */
    public static function encodeSources(array $lines, string $kind, float $cap): array
    {
        $sources = [];
        $left = round($cap, 2);

        foreach ($lines as $line) {
            if (($line['kind'] ?? 'payment') !== $kind || $left < 0.01) {
                continue;
            }

            $amount = round(min(abs((float) $line['delta']), $left), 2);
            if ($amount < 0.01) {
                continue;
            }

            $left = round($left - $amount, 2);
            $sources[] = sprintf('%d:%s', (int) $line['source_run_id'], number_format($amount, 2, '.', ''));
        }

        return $sources;
    }

    /**
     * Mənbələr bazadakı JSON sütunundan gəlir — hər element ayrıca yoxlanılır, yararsızı atlanır.
     *
     * @param  array<array-key, mixed>|null  $sources
     * @return array<int,float> source run id => amount
     */
    public static function decodeSources(?array $sources): array
    {
        $decoded = [];

        foreach ((array) $sources as $source) {
            if (! is_string($source) || ! preg_match('/^(\d+):(\d+(?:\.\d+)?)$/', $source, $match)) {
                continue;
            }

            $decoded[(int) $match[1]] = round(($decoded[(int) $match[1]] ?? 0.0) + (float) $match[2], 2);
        }

        return $decoded;
    }

    /**
     * @param  array<int,array<string,mixed>>  $lines
     */
    private function sumCode(array $lines, string $code): float
    {
        $sum = 0.0;
        foreach ($lines as $line) {
            if (($line['code'] ?? null) === $code) {
                $sum += (float) $line['amount'];
            }
        }

        return round($sum, 2);
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
        $paidSources = $this->sources($payslip->lines->map(fn ($line): array => ['code' => $line->code, 'sources' => $line->sources])->all());
        $currentSources = $this->sources($this->calculator->orderEarningLines($tabelNo, $onDate, $year, $month));

        if ($paidSources === $currentSources) {
            return 0.0;
        }

        $asPaid = $this->calculator->calculate($tabelNo, $onDate, $year, $month, true, $this->paidOrderLines($payslip), (int) $payslip->payroll_run_id);

        if (! $asPaid) {
            return 0.0;
        }

        $orderDelta = round($recalcNet - $asPaid['net'] - $this->sumCode($asPaid['lines'], self::LOAN) - $this->sumCode($asPaid['lines'], self::ENFORCEMENT), 2);

        return $orderDelta < 0 ? max($delta, $orderDelta) : 0.0;
    }

    /**
     * Son haqq-hesabla ödənilmiş ay: işçi xitam olunub, sonrakı heç bir adi run onu tutmur. Həmin ay
     * üçün xitamdan sonra təsdiqlənən (və ya dəyişən) qeyri-iş günü işi / əvəzetmə əmri burada
     * «geri ödəniş» kimi yaranır və növbəti son haqq-hesab run-u onu ödəyir. Fərq yalnız əmr
     * sətirlərindən götürülür: ay həm indiki əmr faktları, həm ödənilmiş əmr sətirləri ilə eyni
     * girişlərlə (birdəfəlik ödənişlər, maaş) hesablanır və xalis fərq alınır — sonradan gələn
     * ödənilməmiş birdəfəlik sətir onsuz da son haqq-hesaba düşür, ikinci dəfə sayılmır. Mənfi fərq
     * (əmr ləğv edilib) xitam olunmuş işçidən tutulmur. Yalnız birdəfəlik sətir ödəyən (maaşsız)
     * payslip mənbə deyil.
     *
     * @return array<string,mixed>|null
     */
    private function settlementOrderBackPay(Payslip $payslip, string $tabelNo, PayrollPeriod $period): ?array
    {
        if ($payslip->lines->firstWhere('code', 'base') === null) {
            return null;
        }

        $year = (int) $period->year;
        $month = (int) $period->month;
        $leftOn = DB::table('personnels')->where('tabel_no', $tabelNo)->value('leave_work_date');
        $onDate = $leftOn !== null && substr((string) $leftOn, 0, 10) >= $period->starts_on->toDateString() && substr((string) $leftOn, 0, 10) <= $period->ends_on->toDateString()
            ? substr((string) $leftOn, 0, 10)
            : $period->ends_on->toDateString();

        $paidSources = $this->sources($payslip->lines->map(fn ($line): array => ['code' => $line->code, 'sources' => $line->sources])->all());
        $current = $this->calculator->orderEarningLines($tabelNo, $onDate, $year, $month);

        if ($paidSources === $this->sources($current)) {
            return null;
        }

        $sourceRunId = (int) $payslip->payroll_run_id;
        $now = $this->calculator->calculate($tabelNo, $onDate, $year, $month, true, $current, $sourceRunId, false, true, true);
        $asPaid = $this->calculator->calculate($tabelNo, $onDate, $year, $month, true, $this->paidOrderLines($payslip), $sourceRunId, false, true, true);

        if (! $now || ! $asPaid) {
            return null;
        }

        $net = fn (array $calc): float => $calc['net'] + $this->sumCode($calc['lines'], self::LOAN) + $this->sumCode($calc['lines'], self::ENFORCEMENT);
        $alreadyPaid = (float) RetroPayment::query()
            ->where('tabel_no', $tabelNo)
            ->where('source_payroll_run_id', $sourceRunId)
            ->sum('amount');
        $delta = round($net($now) - $net($asPaid) - $alreadyPaid, 2);

        if ($delta < 0.01) {
            return null;
        }

        return [
            'source_run_id' => $sourceRunId,
            'period_code' => $period->code,
            'paid_net' => round($net($asPaid), 2),
            'recomputed_net' => round($net($now), 2),
            'delta' => $delta,
            'kind' => 'payment',
        ];
    }

    /**
     * Ödənilmiş payslip-in əmr sətirləri — ayı «ödənildiyi kimi» qiymətləndirmək üçün.
     *
     * @return list<array<string,mixed>>
     */
    private function paidOrderLines(Payslip $payslip): array
    {
        return $payslip->lines
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
     * Only what the run's payslips actually carry is booked: each `retro` line names the
     * source periods and amounts it pays, each `retro_recovery` line what it deducts
     * (booked as negative amounts). Nothing is recalculated here — a retro that arose after
     * the calculation was not paid and stays pending (lock refuses such a run anyway).
     */
    public function recordRetroPayments(PayrollRun $run): void
    {
        $paidOn = $run->period->ends_on->toDateString();
        $payslips = $run->payslips()
            ->with(['lines' => fn ($query) => $query->whereIn('code', [self::RETRO, self::RECOVERY])])
            ->get();

        DB::transaction(function () use ($run, $payslips, $paidOn): void {
            foreach ($payslips as $payslip) {
                $tabelNo = (string) $payslip->tabel_no;
                $booked = [];

                foreach ($payslip->lines as $line) {
                    $sign = $line->code === self::RECOVERY ? -1 : 1;
                    $sources = self::decodeSources($line->sources);

                    // A line calculated before sources were recorded: attribute its amount to
                    // the periods still pending, never more than the line itself.
                    if ($sources === []) {
                        $pending = $this->pendingRetro($tabelNo, (int) $run->id)['lines'];
                        $sources = self::decodeSources(self::encodeSources($pending, $sign < 0 ? 'recovery' : 'payment', (float) $line->amount));
                    }

                    foreach ($sources as $sourceRunId => $amount) {
                        $booked[$sourceRunId] = round(($booked[$sourceRunId] ?? 0.0) + $sign * $amount, 2);
                    }
                }

                foreach ($booked as $sourceRunId => $amount) {
                    if (abs($amount) < 0.01) {
                        continue;
                    }

                    RetroPayment::query()->updateOrCreate(
                        [
                            'tabel_no' => $tabelNo,
                            'source_payroll_run_id' => $sourceRunId,
                            'paid_payroll_run_id' => $run->id,
                        ],
                        ['amount' => $amount, 'paid_on' => $paidOn],
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

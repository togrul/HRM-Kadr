<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\EmployeeLoan;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * @param  array<string,mixed>  $data
     */
    public function createLoan(string $tabelNo, array $data): EmployeeLoan
    {
        return EmployeeLoan::create([
            'tabel_no' => $tabelNo,
            'type' => $data['type'] ?? 'loan',
            'principal' => $data['principal'],
            'monthly_installment' => $data['monthly_installment'],
            'remaining' => $data['principal'],
            'currency' => $data['currency'] ?? 'AZN',
            'status' => 'active',
            'start_on' => $data['start_on'],
            'note' => $data['note'] ?? null,
        ]);
    }

    /**
     * Total installment to deduct this period for an employee (capped at remaining per loan).
     */
    public function activeInstallmentTotal(string $tabelNo): float
    {
        return (float) EmployeeLoan::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->get()
            ->sum(fn (EmployeeLoan $loan) => min((float) $loan->monthly_installment, (float) $loan->remaining));
    }

    /**
     * Adi (aylıq) run-da tutulacaq taksit: aktiv kreditlərin taksitləri (hər biri qalan borcdan çox
     * olmamaqla), amma ƏM m.176.1 həddinə qədər — bütün qeyri-statutor tutulmaların cəmi işçiyə
     * verilməli olan əmək haqqının 20 %-ni keçmir. Könüllü (işçinin ərizəsi ilə, m.175.1/175.6)
     * taksit də «tutulan bütün məbləğlər»ə daxildir. Sığmayan hissə kreditdə qalır və qrafik uzanır:
     * növbəti aylarda yenə adi taksit tutulur (artıq tutulmur), borc bir neçə ay gec bağlanır.
     *
     * @param  float  $wageDue  gross − statutory deductions of this payment
     * @param  float  $otherDeductions  non-statutory deductions already on the payslip
     * @return array{requested: float, amount: float}
     */
    public function installmentAmount(string $tabelNo, float $wageDue, float $otherDeductions = 0.0): array
    {
        $requested = round($this->activeInstallmentTotal($tabelNo), 2);

        if ($requested <= 0) {
            return ['requested' => 0.0, 'amount' => 0.0];
        }

        return ['requested' => $requested, 'amount' => round(min($requested, DeductionCap::room($wageDue, $otherDeductions)), 2)];
    }

    /**
     * Son haqq-hesabda tutulacaq kredit/avans borcu: bütün aktiv kreditlərin QALAN məbləği,
     * amma hər ödənişdə qeyri-statutor tutulmaların cəmi işçiyə verilməli olan əmək haqqının
     * (vergi və məcburi ödənişlərdən sonra) `payroll.deduction_cap_ratio` payını (ƏM m.176.1 —
     * 20 %) keçmir. Sığmayan hissə kreditdə borc olaraq qalır.
     *
     * @param  float  $wageDue  gross − statutory deductions of this payment
     * @param  float  $otherDeductions  non-statutory deductions already on the payslip
     */
    public function settlementAmount(string $tabelNo, float $wageDue, float $otherDeductions = 0.0): float
    {
        return $this->settlement($tabelNo, $wageDue, $otherDeductions)['amount'];
    }

    /**
     * @return array{requested: float, amount: float}
     */
    public function settlement(string $tabelNo, float $wageDue, float $otherDeductions = 0.0): array
    {
        $outstanding = round((float) EmployeeLoan::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->get()
            ->sum(fn (EmployeeLoan $loan): float => max(0.0, (float) $loan->remaining)), 2);

        if ($outstanding <= 0) {
            return ['requested' => 0.0, 'amount' => 0.0];
        }

        return ['requested' => $outstanding, 'amount' => round(min($outstanding, DeductionCap::room($wageDue, $otherDeductions)), 2)];
    }

    /**
     * Apply repayments for every employee paid in the run. Idempotent: a loan is repaid at most
     * once per run (unique loan+run), so re-running lock never double-deducts.
     *
     * What is booked is exactly the payslip's `loan` line (lock has just proved it matches a
     * fresh calculation): first each loan's instalment, then — on a final settlement — the
     * rest oldest loan first. Off-cycle runs deduct no instalment, so they repay nothing.
     *
     * Each repayment row also keeps what was scheduled for the loan in this run
     * (`scheduled_amount`: the instalment, or the outstanding debt on a settlement). When the
     * ƏM m.176.1 cap leaves less room, the difference stays on the loan and the schedule runs
     * longer; a loan the cap left nothing for still gets a 0.00 row, so the deferral is visible.
     *
     * @param  list<string>  $settledTabelNos  employees the run settles (they leave this pay month)
     */
    public function recordRepaymentsForRun(PayrollRun $run, array $settledTabelNos = []): void
    {
        if (! in_array($run->run_type, ['regular', 'final_settlement'], true)) {
            return;
        }

        $paidOn = $run->period->ends_on->toDateString();
        $paid = $run->payslips()->pluck('tabel_no')->map(fn ($tabelNo): string => (string) $tabelNo);
        $deducted = $run->payslips()
            ->join('payslip_lines', 'payslip_lines.payslip_id', '=', 'payslips.id')
            ->where('payslip_lines.code', 'loan')
            ->groupBy('payslips.tabel_no')
            ->selectRaw('payslips.tabel_no as tabel_no, sum(payslip_lines.amount) as amount')
            ->pluck('amount', 'tabel_no');
        $settles = fn (string $tabelNo): bool => $run->run_type === 'final_settlement' || in_array($tabelNo, $settledTabelNos, true);

        DB::transaction(function () use ($run, $paid, $deducted, $paidOn, $settles): void {
            EmployeeLoan::query()
                ->whereIn('tabel_no', $paid->all())
                ->where('status', 'active')
                ->orderBy('start_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->reject(fn (EmployeeLoan $loan): bool => $loan->repayments()->where('payroll_run_id', $run->id)->exists())
                ->groupBy('tabel_no')
                ->each(function ($loans, $tabelNo) use ($run, $deducted, $paidOn, $settles): void {
                    $left = round((float) $deducted->get($tabelNo, 0), 2);
                    $settlement = $settles((string) $tabelNo);
                    $take = [];

                    // 1) instalments, 2) the rest of a settlement, oldest loan first.
                    foreach ([true, false] as $instalmentPass) {
                        foreach ($loans as $loan) {
                            $open = round((float) $loan->remaining - ($take[$loan->id] ?? 0.0), 2);
                            $amount = round(min($left, $open, $instalmentPass ? (float) $loan->monthly_installment : $open), 2);
                            if ($amount > 0) {
                                $take[$loan->id] = round(($take[$loan->id] ?? 0.0) + $amount, 2);
                                $left = round($left - $amount, 2);
                            }
                        }
                    }

                    foreach ($loans as $loan) {
                        $amount = $take[$loan->id] ?? 0.0;
                        $scheduled = round($settlement
                            ? max(0.0, (float) $loan->remaining)
                            : min((float) $loan->monthly_installment, max(0.0, (float) $loan->remaining)), 2);

                        if ($amount <= 0 && $scheduled <= 0) {
                            continue;
                        }

                        $loan->repayments()->create([
                            'payroll_run_id' => $run->id,
                            'amount' => $amount,
                            'scheduled_amount' => max($scheduled, $amount),
                            'paid_on' => $paidOn,
                        ]);

                        if ($amount <= 0) {
                            continue;
                        }

                        $remaining = round((float) $loan->remaining - $amount, 2);
                        $loan->update([
                            'remaining' => $remaining,
                            'status' => $remaining <= 0 ? 'closed' : 'active',
                        ]);
                    }
                });
        });
    }

    /**
     * Undo a run's repayments (on reopen): restore remaining and re-activate loans.
     */
    public function reverseRepaymentsForRun(PayrollRun $run): void
    {
        DB::transaction(function () use ($run): void {
            $run->loadMissing('payslips');

            \App\Models\LoanRepayment::query()
                ->where('payroll_run_id', $run->id)
                ->with('loan')
                ->get()
                ->each(function (\App\Models\LoanRepayment $repayment): void {
                    $loan = $repayment->loan;

                    if ($loan) {
                        $loan->update([
                            'remaining' => round((float) $loan->remaining + (float) $repayment->amount, 2),
                            'status' => 'active',
                        ]);
                    }

                    $repayment->delete();
                });
        });
    }
}

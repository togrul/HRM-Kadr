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
     * Son haqq-hesabda tutulacaq kredit/avans borcu: bütün aktiv kreditlərin QALAN məbləği,
     * amma hər ödənişdə qeyri-statutor tutulmaların cəmi işçiyə verilməli olan əmək haqqının
     * (vergi və məcburi ödənişlərdən sonra) `payroll.final_settlement_loan_cap_ratio` payını
     * keçmir (ƏM m.176.1 — 20 %). Sığmayan hissə kreditdə borc olaraq qalır.
     *
     * @param  float  $wageDue  gross − statutory deductions of this payment
     * @param  float  $otherDeductions  non-statutory deductions already on the payslip
     */
    public function settlementAmount(string $tabelNo, float $wageDue, float $otherDeductions = 0.0): float
    {
        $outstanding = round((float) EmployeeLoan::query()
            ->where('tabel_no', $tabelNo)
            ->where('status', 'active')
            ->get()
            ->sum(fn (EmployeeLoan $loan): float => max(0.0, (float) $loan->remaining)), 2);

        if ($outstanding <= 0) {
            return 0.0;
        }

        $ratio = max(0.0, min(1.0, (float) config('payroll.final_settlement_loan_cap_ratio', 0.20)));
        // Aşağı yuvarlaqlaşdırma: hədd bir qəpik də aşılmasın.
        $room = floor((max(0.0, $wageDue) * $ratio - $otherDeductions) * 100 + 1e-6) / 100;

        return round(max(0.0, min($outstanding, $room)), 2);
    }

    /**
     * Apply repayments for every employee paid in the run. Idempotent: a loan is repaid at most
     * once per run (unique loan+run), so re-running lock never double-deducts.
     *
     * What is booked is exactly the payslip's `loan` line (lock has just proved it matches a
     * fresh calculation): first each loan's instalment, then — on a final settlement — the
     * rest oldest loan first. Off-cycle runs deduct no instalment, so they repay nothing.
     */
    public function recordRepaymentsForRun(PayrollRun $run): void
    {
        if (! in_array($run->run_type, ['regular', 'final_settlement'], true)) {
            return;
        }

        $paidOn = $run->period->ends_on->toDateString();
        $deducted = $run->payslips()
            ->join('payslip_lines', 'payslip_lines.payslip_id', '=', 'payslips.id')
            ->where('payslip_lines.code', 'loan')
            ->groupBy('payslips.tabel_no')
            ->selectRaw('payslips.tabel_no as tabel_no, sum(payslip_lines.amount) as amount')
            ->pluck('amount', 'tabel_no');

        DB::transaction(function () use ($run, $deducted, $paidOn): void {
            EmployeeLoan::query()
                ->whereIn('tabel_no', $deducted->keys())
                ->where('status', 'active')
                ->orderBy('start_on')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->reject(fn (EmployeeLoan $loan): bool => $loan->repayments()->where('payroll_run_id', $run->id)->exists())
                ->groupBy('tabel_no')
                ->each(function ($loans, $tabelNo) use ($run, $deducted, $paidOn): void {
                    $left = round((float) $deducted->get($tabelNo, 0), 2);
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
                        if ($amount <= 0) {
                            continue;
                        }

                        $loan->repayments()->create([
                            'payroll_run_id' => $run->id,
                            'amount' => $amount,
                            'paid_on' => $paidOn,
                        ]);

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

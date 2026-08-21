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
     * Apply repayments for every employee paid in the run. Idempotent: a loan is repaid at most
     * once per run (unique loan+run), so re-running lock never double-deducts.
     */
    public function recordRepaymentsForRun(PayrollRun $run): void
    {
        $paidOn = $run->period->ends_on->toDateString();
        $tabelNos = $run->payslips()->pluck('tabel_no')->unique();

        DB::transaction(function () use ($run, $tabelNos, $paidOn): void {
            EmployeeLoan::query()
                ->whereIn('tabel_no', $tabelNos)
                ->where('status', 'active')
                ->get()
                ->each(function (EmployeeLoan $loan) use ($run, $paidOn): void {
                    if ($loan->repayments()->where('payroll_run_id', $run->id)->exists()) {
                        return;
                    }

                    $amount = round(min((float) $loan->monthly_installment, (float) $loan->remaining), 2);

                    if ($amount <= 0) {
                        return;
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

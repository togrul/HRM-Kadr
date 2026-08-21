<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\CompensationComponent;
use App\Models\PayrollRun;
use App\Models\PayslipLine;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;

class PayrollExportService
{
    public function __construct(private readonly CompensationReadRepository $compensation) {}

    /**
     * Bank payment file rows: one credit per employee with a positive net.
     *
     * @return array<int,array<string,mixed>>
     */
    public function bankRows(PayrollRun $run): array
    {
        return $run->payslips()
            ->with('personnel:tabel_no,surname,name')
            ->get()
            ->filter(fn ($payslip) => (float) $payslip->net > 0)
            ->map(function ($payslip): array {
                $bank = $this->compensation->primaryBankAccount($payslip->tabel_no);

                return [
                    'tabel_no' => $payslip->tabel_no,
                    'full_name' => $this->fullName($payslip),
                    'iban' => $bank?->iban,
                    'bank_name' => $bank?->bank_name,
                    'amount' => $this->money($payslip->net),
                    'currency' => $payslip->currency,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * General-ledger rows: payslip lines aggregated by component code with GL account mapping.
     *
     * @return array<int,array<string,mixed>>
     */
    public function glRows(PayrollRun $run): array
    {
        $glByCode = CompensationComponent::query()->pluck('gl_code', 'code');
        $payslipIds = $run->payslips()->pluck('id');

        return PayslipLine::query()
            ->whereIn('payslip_id', $payslipIds)
            ->get()
            ->groupBy('code')
            ->map(function ($group) use ($glByCode): array {
                $first = $group->first();

                return [
                    'gl_code' => $glByCode[$first->code] ?? null,
                    'code' => $first->code,
                    'name' => $first->name,
                    'kind' => $first->kind,
                    'amount' => $this->money($group->sum('amount')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * State report rows: per-employee statutory amounts (DSMF / tax / insurance).
     *
     * @return array<int,array<string,mixed>>
     */
    public function stateRows(PayrollRun $run): array
    {
        return $run->payslips()
            ->with(['personnel:tabel_no,surname,name,pin', 'lines'])
            ->get()
            ->map(function ($payslip): array {
                $byCode = $payslip->lines->keyBy('code');
                $amount = fn (string $code) => $this->money($byCode[$code]->amount ?? 0);

                return [
                    'tabel_no' => $payslip->tabel_no,
                    'full_name' => $this->fullName($payslip),
                    'pin' => $payslip->personnel?->pin,
                    'gross' => $this->money($payslip->gross),
                    'income_tax' => $amount('income_tax_ee'),
                    'dsmf_ee' => $amount('dsmf_ee'),
                    'dsmf_er' => $amount('dsmf_er'),
                    'unemployment_ee' => $amount('unemployment_ee'),
                    'unemployment_er' => $amount('unemployment_er'),
                    'medical_ee' => $amount('medical_ee'),
                    'medical_er' => $amount('medical_er'),
                    'net' => $this->money($payslip->net),
                ];
            })
            ->values()
            ->all();
    }

    private function fullName($payslip): string
    {
        return trim(($payslip->personnel?->surname ?? '').' '.($payslip->personnel?->name ?? ''));
    }

    private function money(float|string|null $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}

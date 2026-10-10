<?php

namespace App\Modules\Payroll\Application\Services;

use App\Models\CompensationComponent;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PayslipLine;
use App\Modules\Compensation\Domain\Contracts\CompensationReadRepository;
use App\Services\StructureScope;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;

class PayrollExportService
{
    public function __construct(private readonly CompensationReadRepository $compensation) {}

    /**
     * Bank payment file rows: one credit per employee with a positive net.
     *
     * @return array<int,array<string,mixed>>
     */
    public function bankRows(PayrollRun $run, ?StructureScope $scope = null): array
    {
        return $this->payslips($run, $scope)
            ->with('personnel:tabel_no,surname,name')
            ->get()
            ->filter(fn (Payslip $payslip): bool => BigDecimal::of($payslip->net)->isPositive())
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
     * General-ledger rows: payslip lines aggregated by component, kind and currency with GL account mapping.
     *
     * @return array<int,array<string,mixed>>
     */
    public function glRows(PayrollRun $run, ?StructureScope $scope = null): array
    {
        $glByCode = CompensationComponent::query()->pluck('gl_code', 'code');
        $payslipIds = $this->payslips($run, $scope)->pluck('id');

        return PayslipLine::query()
            ->whereIn('payslip_id', $payslipIds)
            ->with('payslip:id,currency')
            ->get()
            ->groupBy(fn (PayslipLine $line): string => json_encode([$line->code, $line->kind, $line->payslip->currency], JSON_THROW_ON_ERROR))
            ->map(function ($group) use ($glByCode): array {
                $first = $group->first();
                $amount = BigDecimal::zero();
                foreach ($group as $line) {
                    $amount = $amount->plus($line->amount);
                }

                return [
                    'gl_code' => $glByCode[$first->code] ?? null,
                    'code' => $first->code,
                    'name' => $first->name,
                    'kind' => $first->kind,
                    'amount' => $this->money((string) $amount),
                    'currency' => $first->payslip->currency,
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
    public function stateRows(PayrollRun $run, ?StructureScope $scope = null): array
    {
        return $this->payslips($run, $scope)
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

    /**
     * Run-ın hesab vərəqələri; görünürlük verilibsə yalnız istifadəçinin strukturlarındakı
     * işçilərinki (ixrac faylı başqa strukturun maaşını sızdırmasın).
     *
     * @return Builder<\App\Models\Payslip>
     */
    private function payslips(PayrollRun $run, ?StructureScope $scope): Builder
    {
        $query = $run->payslips()->getQuery();

        return $scope ? $scope->constrainThrough($query, 'personnel') : $query;
    }

    private function fullName($payslip): string
    {
        return trim(($payslip->personnel?->surname ?? '').' '.($payslip->personnel?->name ?? ''));
    }

    private function money(float|string|null $value): string
    {
        return (string) BigDecimal::of($value ?? '0')->toScale(2, RoundingMode::HalfUp);
    }
}

<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\CompensationComponent;
use App\Models\StatutoryExemption;
use App\Models\StatutoryRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Master data behind payroll: the pay component catalog and the statutory rate table.
 */
class CompensationCatalogService
{
    /**
     * @param  array<string,mixed>  $data
     */
    public function saveComponent(array $data, ?int $componentId = null): CompensationComponent
    {
        $data['gl_code'] = ($data['gl_code'] ?? null) ?: null;
        $data['sort'] = $data['sort'] ?? 0;

        if ($componentId) {
            $component = CompensationComponent::findOrFail($componentId);
            $component->update($data);

            return $component;
        }

        return CompensationComponent::create($data);
    }

    public function deleteComponent(int $componentId): void
    {
        CompensationComponent::whereKey($componentId)->delete();
    }

    /**
     * A blank "up to" closes the bracket ladder (no upper limit).
     *
     * @param  array<string,mixed>  $data
     * @param  array<int, array{up_to?: mixed, rate: mixed}>  $brackets
     */
    public function createStatutoryRate(array $data, array $brackets): StatutoryRate
    {
        return StatutoryRate::create([
            'regime_id' => ($data['regime_id'] ?? null) ?: null,
            'component_code' => $data['component_code'],
            'payer' => $data['payer'],
            'base' => $data['base'],
            'brackets' => array_map(fn (array $b): array => [
                'up_to' => (($b['up_to'] ?? null) === '' || ($b['up_to'] ?? null) === null) ? null : (float) $b['up_to'],
                'rate' => (float) $b['rate'],
            ], array_values($brackets)),
            'effective_from' => $data['effective_from'],
            'is_active' => true,
        ]);
    }

    public function deleteStatutoryRate(int $rateId): void
    {
        StatutoryRate::whereKey($rateId)->delete();
    }

    /**
     * Sabit məbləğli gəlir vergisi azadolması (VM m.102, məs. 102.1.6). Yeni sətir öz effektiv
     * tarixindən qüvvədədir; əvvəlki (eyni kodlu) sətrin müddəti bir gün əvvəl bağlanır ki, kilidli
     * aylar köhnə qayda ilə qalsın.
     *
     * @param  array<string,mixed>  $data
     */
    public function createStatutoryExemption(array $data): StatutoryExemption
    {
        return DB::transaction(function () use ($data): StatutoryExemption {
            $regimeId = ($data['regime_id'] ?? null) ?: null;
            $from = Carbon::parse((string) $data['effective_from'])->toDateString();
            $code = trim((string) $data['code']);

            StatutoryExemption::query()
                ->where('code', $code)
                ->where('regime_id', $regimeId)
                ->whereNull('effective_to')
                ->whereDate('effective_from', '<', $from)
                ->update(['effective_to' => Carbon::parse($from)->subDay()->toDateString()]);

            return StatutoryExemption::create([
                'regime_id' => $regimeId,
                'code' => $code,
                'legal_basis' => trim((string) $data['legal_basis']),
                'kind' => StatutoryExemption::KIND_FIXED_AMOUNT,
                'component_code' => 'income_tax',
                'amount' => (float) $data['amount'],
                'income_up_to' => ($data['income_up_to'] ?? '') === '' || ($data['income_up_to'] ?? null) === null ? null : (float) $data['income_up_to'],
                'main_workplace_only' => (bool) ($data['main_workplace_only'] ?? false),
                'effective_from' => $from,
                'is_active' => true,
            ]);
        });
    }

    public function deleteStatutoryExemption(int $exemptionId): void
    {
        StatutoryExemption::whereKey($exemptionId)->delete();
    }
}

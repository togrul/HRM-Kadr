<?php

namespace App\Modules\Compensation\Application\Services;

use App\Models\CompensationComponent;
use App\Models\StatutoryRate;

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
}

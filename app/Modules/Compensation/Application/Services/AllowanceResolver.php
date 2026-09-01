<?php

namespace App\Modules\Compensation\Application\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AllowanceResolver
{
    public function __construct(private readonly CompensationService $compensationService) {}

    /**
     * Resolve the effective component lines for an employee into concrete amounts.
     * Percent lines are computed against the compensation base amount.
     *
     * @return Collection<int,array<string,mixed>>
     */
    public function resolve(string $tabelNo, ?Carbon $date = null): Collection
    {
        $compensation = $this->compensationService->currentFor($tabelNo, $date);

        if (! $compensation) {
            return collect();
        }

        $base = (float) $compensation->base_amount;

        return $compensation->lines->map(function ($line) use ($base): array {
            $component = $line->component;

            $amount = match (true) {
                $line->amount !== null => (float) $line->amount,
                $line->percent !== null => round($base * (float) $line->percent / 100, 2),
                default => 0.0,
            };

            return [
                'component_code' => $component?->code,
                'component_name' => $component?->name,
                'type' => $component?->type,
                'taxable' => (bool) $component?->taxable,
                'affects_social' => (bool) $component?->affects_social,
                'is_statutory' => (bool) $component?->is_statutory,
                'amount' => $amount,
            ];
        })->values();
    }
}

<?php

namespace App\Modules\PerformanceEvaluation\Application\Services\Kpi;

use App\Models\PerformanceKpi;
use App\Models\PerformanceKpiTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Position KPI templates (spec §4.2). Hard rules block the save: item weights sum to
 * 100, the KPI and competency shares sum to 100, threshold < 100% ≤ stretch ≤ cap,
 * a target (or range bounds) per item and one template per position. The soft rules —
 * 3–7 KPIs, 10–40% each — come back as warnings.
 */
class KpiTemplateService
{
    public const MIN_ITEMS = 3;

    public const MAX_ITEMS = 7;

    public const MIN_WEIGHT = 10;

    public const MAX_WEIGHT = 40;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $positionIds
     * @return array{template: PerformanceKpiTemplate, warnings: array<int, string>}
     *
     * @throws ValidationException
     */
    public function save(array $data, array $items, array $positionIds, ?PerformanceKpiTemplate $template = null): array
    {
        $this->validate($data, $items, $positionIds, $template);

        $template = DB::transaction(function () use ($data, $items, $positionIds, $template): PerformanceKpiTemplate {
            $template ??= new PerformanceKpiTemplate(['created_by' => auth()->id()]);
            $template->fill($data)->save();

            $template->items()->delete();
            foreach (array_values($items) as $index => $item) {
                $template->items()->create([
                    ...collect($item)->only(['performance_kpi_id', 'weight', 'target', 'range_min', 'range_max', 'threshold', 'stretch', 'cap', 'target_editable'])->all(),
                    'sort_order' => $index,
                ]);
            }

            $template->positions()->sync($positionIds);

            return $template;
        });

        return ['template' => $template, 'warnings' => $this->warnings($items)];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, string>
     */
    public function warnings(array $items): array
    {
        $warnings = [];
        $count = count($items);

        if ($count < self::MIN_ITEMS || $count > self::MAX_ITEMS) {
            $warnings[] = __('performance_evaluation::kpi.warnings.item_count', ['min' => self::MIN_ITEMS, 'max' => self::MAX_ITEMS]);
        }

        if (collect($items)->contains(fn (array $item): bool => (float) $item['weight'] < self::MIN_WEIGHT || (float) $item['weight'] > self::MAX_WEIGHT)) {
            $warnings[] = __('performance_evaluation::kpi.warnings.item_weight', ['min' => self::MIN_WEIGHT, 'max' => self::MAX_WEIGHT]);
        }

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, int>  $positionIds
     *
     * @throws ValidationException
     */
    private function validate(array $data, array $items, array $positionIds, ?PerformanceKpiTemplate $template): void
    {
        $errors = [];

        if (abs((float) $data['kpi_weight_share'] + (float) $data['competency_weight_share'] - 100) > 0.001) {
            $errors['shares'] = __('performance_evaluation::kpi.errors.shares_sum_invalid');
        }

        if ($items === []) {
            $errors['items'] = __('performance_evaluation::kpi.errors.items_required');
        } elseif (abs(collect($items)->sum(fn (array $item): float => (float) $item['weight']) - 100) > 0.001) {
            $errors['items'] = __('performance_evaluation::kpi.errors.weights_sum_invalid');
        }

        $kpis = PerformanceKpi::query()->whereIn('id', array_column($items, 'performance_kpi_id'))->get(['id', 'direction', 'type'])->keyBy('id');

        foreach (array_values($items) as $index => $item) {
            $kpi = $kpis->get((int) $item['performance_kpi_id']);
            $message = match (true) {
                $kpi === null => __('performance_evaluation::kpi.errors.kpi_missing'),
                $kpi->direction === 'range' && $kpi->type === 'quantitative' && ! $this->rangeIsValid($item) => __('performance_evaluation::kpi.errors.range_invalid'),
                $kpi->direction !== 'range' && $kpi->type === 'quantitative' && ! is_numeric($item['target'] ?? null) => __('performance_evaluation::kpi.errors.target_required'),
                ! $this->bandIsOrdered($item) => __('performance_evaluation::kpi.errors.threshold_order_invalid'),
                default => null,
            };

            if ($message !== null) {
                $errors["items.$index"] = $message;
            }
        }

        if (count(array_unique(array_column($items, 'performance_kpi_id'))) !== count($items)) {
            $errors['items'] ??= __('performance_evaluation::kpi.errors.duplicate_kpi');
        }

        $takenPositions = DB::table('performance_kpi_template_positions')
            ->whereIn('position_id', $positionIds)
            ->when($template?->exists, fn ($query) => $query->where('performance_kpi_template_id', '!=', $template->id))
            ->exists();

        if ($takenPositions) {
            $errors['positions'] = __('performance_evaluation::kpi.errors.position_taken');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * threshold < 100% ≤ stretch ≤ cap, each bound optional.
     *
     * @param  array<string, mixed>  $item
     */
    private function bandIsOrdered(array $item): bool
    {
        $threshold = $this->number($item['threshold'] ?? null);
        $stretch = $this->number($item['stretch'] ?? null);
        $cap = $this->number($item['cap'] ?? null);

        return ($threshold === null || ($threshold >= 0 && $threshold < 100))
            && ($stretch === null || $stretch >= 100)
            && ($cap === null || $cap >= 100)
            && ($stretch === null || $cap === null || $stretch <= $cap);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function rangeIsValid(array $item): bool
    {
        $min = $this->number($item['range_min'] ?? null);
        $max = $this->number($item['range_max'] ?? null);

        return $min !== null && $max !== null && $min <= $max;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}

<?php

namespace App\Modules\Orders\Support\Traits\Orders;

use App\Enums\StructureEnum;
use App\Models\Structure;
use App\Services\WordSuffixService;

trait DropdownLabelCache
{
    protected array $componentDropdownFields = [
        'rank_id',
        'personnel_id',
        'structure_main_id',
        'structure_id',
        'position_id',
        'transportation',
    ];

    public array $componentOptionLabels = [];

    /**
     * Cached lookup for all structures (id => attributes) to avoid per-click queries.
     *
     * @var \Illuminate\Support\Collection<int, array<string, mixed>>|null
     */
    protected $structureLookup;
    protected array $structureLineageCache = [];

    protected function isDropdownField(string $field): bool
    {
        if (in_array($field, $this->componentDropdownFields, true)) {
            return true;
        }

        if (property_exists($this, 'dynamicDropdownFields') && is_array($this->dynamicDropdownFields)) {
            return in_array($field, $this->dynamicDropdownFields, true);
        }

        return false;
    }

    protected function registerComponentOptionLabels(string $field, array $options, bool $overrideExisting = false): void
    {
        foreach ($options as $option) {
            $id = (int) $option['id'];
            $label = (string) $option['label'];

            if (! $overrideExisting && isset($this->componentOptionLabels[$field][$id])) {
                continue;
            }

            $this->componentOptionLabels[$field][$id] = $label;
        }
    }

    protected function dropdownFieldLabel(string $field, $value, ?int $row = null): string
    {
        if (empty($value)) {
            return '---';
        }

        $value = (int) $value;

        if ($field === 'structure_id') {
            $isCoded = $row !== null ? (bool) ($this->coded_list[$row] ?? false) : false;
            $variantKey = ($isCoded ? 'coded' : 'plain').':'.$value;

            if (isset($this->componentOptionLabels[$field][$variantKey])) {
                return $this->componentOptionLabels[$field][$variantKey];
            }

            $label = $this->structureLabelForRow($row, $value);
            $this->componentOptionLabels[$field][$variantKey] = $label;

            return $label;
        }

        if (isset($this->componentOptionLabels[$field][$value])) {
            return $this->componentOptionLabels[$field][$value];
        }

        $label = match ($field) {
            'structure_main_id' => $this->resolveStructureLabel($value, true),
            'structure_id' => $this->structureLabelForRow($row, $value),
            default => (string) $value,
        };

        $this->componentOptionLabels[$field][$value] = $label;

        return $label;
    }

    protected function structureLabelForRow(?int $row, int $structureId): string
    {
        $lineage = $this->structureLineage($structureId);

        if (empty($lineage)) {
            return '---';
        }

        return $this->codedStructureLabelSimple($lineage);
    }

    protected function resolveStructureLabel(int $id, bool $isCoded): string
    {
        $lineage = $this->structureLineage($id);

        if (empty($lineage)) {
            return '---';
        }

        if ($isCoded) {
            return $this->codedStructureLabelSimple($lineage);
        }

        return $this->buildStructureValue($lineage, $isCoded);
    }

    protected function buildStructureValue(array $lineage, $isCoded): string
    {
        $value = '';
        $suffixService = new WordSuffixService;

        foreach ($lineage as $parent) {
            if (empty($parent['parent_id'])) {
                continue;
            }

            $level_name = __(strtolower((collect(StructureEnum::cases())->pluck('name', 'value')[$parent['level']])));

            $data = $isCoded
                ? $this->codedStructureLabelWithNormalizedSuffix($parent, $level_name, $suffixService)
                : $suffixService->getStructureSuffix($parent['name']) . ' ';

            $value .= $data;
        }

        return $value;
    }

    protected function codedStructureLabelWithNormalizedSuffix(array $parent, string $levelName, WordSuffixService $suffixService): string
    {
        $levelWithSuffix = (int) ($parent['level'] ?? 0) > 1
            ? $suffixService->getMultiSuffix($levelName)
            : $suffixService->getStructureSuffix($levelName);

        $levelWithSuffix = $this->normalizeStructureSuffixLabel($levelName, $levelWithSuffix, $suffixService);

        return (string) ($parent['code'] ?? '')
            . $suffixService->getNumberSuffix((int) ($parent['code'] ?? 0))
            . ' '
            . $levelWithSuffix
            . ' ';
    }

    protected function codedStructureLabelSimple(array $lineage): string
    {
        $node = collect($lineage)->last();
        if (! is_array($node)) {
            return '---';
        }

        $code = (int) ($node['code'] ?? 0);
        if ($code <= 0) {
            return (string) ($node['name'] ?? '---');
        }

        $levelName = __(strtolower((string) (collect(StructureEnum::cases())->pluck('name', 'value')[$node['level']] ?? '')));
        $suffixService = new WordSuffixService;
        $levelBase = mb_strtolower((string) $levelName);
        $levelWithSuffix = $this->normalizeStructureSuffixLabel(
            $levelBase,
            $suffixService->getStructureSuffix($levelBase),
            $suffixService
        );

        return trim($code.$suffixService->getNumberSuffix($code).' '.$levelWithSuffix);
    }

    protected function normalizeStructureSuffixLabel(
        string $baseText,
        string $value,
        WordSuffixService $suffixService
    ): string {
        $normalized = trim($value);

        // Keep suffix, but normalize "-nin" -> "nin" form.
        $normalized = preg_replace('/[-‐‑‒–—](nın|nin|nun|nün|ın|in|un|ün)$/u', '$1', $normalized) ?? $normalized;

        if (preg_match('/(nın|nin|nun|nün|ın|in|un|ün)$/u', $normalized)) {
            return $normalized;
        }

        $suffixOnly = $suffixService->getStructureSuffix($baseText, true);
        $suffixOnly = preg_replace('/^[-‐‑‒–—]/u', '', (string) $suffixOnly) ?? (string) $suffixOnly;

        return trim($normalized.$suffixOnly);
    }

    protected function structureLineage(int $structureId): array
    {
        if (isset($this->structureLineageCache[$structureId])) {
            return $this->structureLineageCache[$structureId];
        }

        $index = $this->structureIndex();

        $nodes = [];
        $currentId = $structureId;

        while ($currentId && ($node = $index->get($currentId))) {
            array_unshift($nodes, $node);
            $currentId = $node['parent_id'] ?? null;
        }

        return $this->structureLineageCache[$structureId] = $nodes;
    }

    protected function structureIndex(): \Illuminate\Support\Collection
    {
        if ($this->structureLookup instanceof \Illuminate\Support\Collection) {
            return $this->structureLookup;
        }

        $this->structureLookup = Structure::query()
            ->select('id', 'parent_id', 'name', 'code', 'level')
            ->get()
            ->map(fn ($structure) => [
                'id' => (int) $structure->id,
                'parent_id' => $structure->parent_id ? (int) $structure->parent_id : null,
                'name' => $structure->name,
                'code' => $structure->code,
                'level' => (int) $structure->level,
            ])
            ->keyBy('id');

        return $this->structureLookup;
    }
}

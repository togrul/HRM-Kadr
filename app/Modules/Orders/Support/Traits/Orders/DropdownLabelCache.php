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
        return in_array($field, $this->componentDropdownFields, true);
    }

    protected function registerComponentOptionLabels(string $field, array $options): void
    {
        foreach ($options as $option) {
            $this->componentOptionLabels[$field][(int) $option['id']] = (string) $option['label'];
        }
    }

    protected function dropdownFieldLabel(string $field, $value, ?int $row = null): string
    {
        if (empty($value)) {
            return '---';
        }

        $value = (int) $value;

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

        $isCoded = $row !== null ? (bool) ($this->coded_list[$row] ?? false) : false;

        if ($isCoded) {
            return $this->buildStructureValue($lineage, true);
        }

        return optional(collect($lineage)->last())['name'] ?? '---';
    }

    protected function resolveStructureLabel(int $id, bool $isCoded): string
    {
        $lineage = $this->structureLineage($id);

        if (empty($lineage)) {
            return '---';
        }

        return $this->buildStructureValue($lineage, $isCoded);
    }

    protected function buildStructureValue(array $lineage, $isCoded): string
    {
        $value = '';
        $suffixService = new WordSuffixService;

        foreach ($lineage as $parent) {
            if(empty($parent['parent_id']))
                continue;
            $level_name = __(strtolower((collect(StructureEnum::cases())->pluck('name', 'value')[$parent['level']])));
            $level_with_suffix = $parent['level'] > 1
                ? $suffixService->getMultiSuffix($level_name)
                : $suffixService->getStructureSuffix($level_name);

            $data = $isCoded 
                ? $parent['code'] . $suffixService->getNumberSuffix($parent['code']) . ' ' . $level_with_suffix . ' '
                : $suffixService->getStructureSuffix($parent['name']) . ' ';

            $value .= $data;
        }

        return $value;
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

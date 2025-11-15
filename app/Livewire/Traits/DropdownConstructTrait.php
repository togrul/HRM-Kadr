<?php

namespace App\Livewire\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

trait DropdownConstructTrait
{
    protected int $dropdownCacheMinutes = 10;
    /**
     * Simple in-request cache for selected option rows.
     *
     * @var array<string, mixed>
     */
    protected array $selectedOptionRowCache = [];

    /**
     * Labels we already have from eager-loaded relations, keyed by table name.
     *
     * @var array<string, array<int, string>>
     */
    protected array $preloadedDropdownLabels = [];

    /**
     * Map builder/collection into [{id,label}], sorted.
     */
    protected function toOptions(Builder|Collection $src, string $idCol = 'id', string $labelCol = 'label'): array
    {
        $items = $src instanceof Builder ? $src->get() : $src;

        return $items
            ->map(fn ($s) => [
                'id'    => (int) data_get($s, $idCol),
                'label' => (string) data_get($s, $labelCol),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * Generic options with:
     * - optional base where
     * - LIKE on the REAL column (not alias)
     * - LIMIT when search empty (performance)
     * - always include selected row even if it's out of LIMIT
     *
     * @param  Builder       $base        ready builder with SELECTs (must include label alias!)
     * @param  string|null   $searchCol   real column for LIKE (e.g. 'name', 'title_en', 't.title')
     * @param  string|null   $searchTerm
     * @param  int|string|null $selectedId
     * @param  int           $limit       how many rows when search is empty
     */
    protected function optionsWithSelected(
        Builder $base,
        ?string $searchCol,
        ?string $searchTerm,
        int|string|null $selectedId,
        int $limit = 50
    ): array {
        $searchTerm = trim((string) $searchTerm);

        // 1) base (no LIKE) — when search is empty we limit
        $listQ = clone $base;
        if ($searchTerm === '') {
            $listQ->limit($limit);
        } else {
            // 2) with LIKE on the REAL column (not alias)
            if ($searchCol) {
                $listQ->where($searchCol, 'like', '%'.$searchTerm.'%');
            }
        }

        $list = $listQ->get();

        // 3) ensure selected is present
        if ($selectedId) {
            $pk = $base->getModel()->getQualifiedKeyName(); // table.id
            $has = $list->firstWhere('id', (int) $selectedId);
            if (!$has) {
                $selectedRow = $this->fetchSelectedOptionRow($base, $selectedId);
                if ($selectedRow) {
                    $list->push($selectedRow);
                }
            }
        }

        // 4) uniq + map
        return $this->toOptions($list->unique('id')->values());
    }

    protected function fetchSelectedOptionRow(Builder $base, int|string $selectedId): mixed
    {
        $query = clone $base;
        $query->where($base->getModel()->getQualifiedKeyName(), $selectedId);

        $cacheKey = $this->selectedOptionCacheKey($query, $selectedId);

        if (array_key_exists($cacheKey, $this->selectedOptionRowCache)) {
            return $this->selectedOptionRowCache[$cacheKey];
        }

        $ttl = $this->dropdownCacheTtlMinutes();

        $row = Cache::remember(
            $cacheKey,
            now()->addMinutes($ttl),
            fn () => $query->first()
        );

        return $this->selectedOptionRowCache[$cacheKey] = $row;
    }

    protected function selectedOptionCacheKey(Builder $query, int|string $selectedId): string
    {
        $model = $query->getModel();
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        $hash = md5($sql.'|'.serialize($bindings));
        $user = auth()->id() ?? 'guest';

        return implode(':', [
            'personnel',
            'option',
            $model->getTable(),
            $selectedId,
            $user,
            $hash,
        ]);
    }

    protected function dropdownCacheTtlMinutes(): int
    {
        return property_exists($this, 'dropdownCacheMinutes')
            ? (int) $this->dropdownCacheMinutes
            : 10;
    }

    protected function registerDropdownLabel(string $tableKey, $id, ?string $label): void
    {
        if (empty($tableKey) || empty($id) || empty($label)) {
            return;
        }

        $this->preloadedDropdownLabels[$tableKey][(int) $id] = (string) $label;
    }

    protected function resetDropdownLabelCache(): void
    {
        $this->preloadedDropdownLabels = [];
    }

    protected function dropdownSearch(string $property): string
    {
        return trim((string) ($this->{$property} ?? ''));
    }

    protected function getPreloadedDropdownLabel(string $tableKey, $id): ?string
    {
        if (empty($tableKey) || empty($id)) {
            return null;
        }

        return $this->preloadedDropdownLabels[$tableKey][(int) $id] ?? null;
    }

    protected function dropdownLabelCacheKey(Builder $base): string
    {
        return $base->getModel()->getTable();
    }

    protected function cachedOptionsWithSelected(string $cacheKey, Builder $base, $selectedId, int $limit = 50): array
    {
        $options = cache()->remember(
            $cacheKey,
            now()->addMinutes($this->dropdownCacheMinutes),
            function () use ($base, $limit) {
                $query = clone $base;
                $query->limit($limit);

                return $this->toOptions($query);
            }
        );

        return $this->appendSelectedOption($options, $base, $selectedId);
    }

     protected function appendSelectedOption(array $options, Builder $base, $selectedId): array
    {
        if (empty($selectedId)) {
            return $options;
        }

        $hasSelected = collect($options)->first(
            fn ($option) => (int) $option['id'] === (int) $selectedId
        );

        if ($hasSelected) {
            return $options;
        }

        $tableKey = $this->dropdownLabelCacheKey($base);
        $preloadedLabel = $this->getPreloadedDropdownLabel($tableKey, $selectedId);

        if ($preloadedLabel) {
            $options[] = [
                'id' => (int) $selectedId,
                'label' => $preloadedLabel,
            ];
        } else {
            $selectedRow = $this->fetchSelectedOptionRow($base, $selectedId);

            if ($selectedRow) {
                $options[] = [
                    'id' => (int) data_get($selectedRow, 'id'),
                    'label' => (string) data_get($selectedRow, 'label'),
                ];
            }
        }

        return collect($options)
            ->unique('id')
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}

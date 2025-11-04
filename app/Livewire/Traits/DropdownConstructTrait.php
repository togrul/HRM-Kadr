<?php

namespace App\Livewire\Traits;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait DropdownConstructTrait
{
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
                $selectedRow = (clone $base)->where($pk, $selectedId)->first();
                if ($selectedRow) {
                    $list->push($selectedRow);
                }
            }
        }

        // 4) uniq + map
        return $this->toOptions($list->unique('id')->values());
    }
}

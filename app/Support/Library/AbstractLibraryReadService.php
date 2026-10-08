<?php

namespace App\Support\Library;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractLibraryReadService
{
    use BuildsLibraryDirectoryPayload;

    /** Catalog filter chips, in display order; "all" hides archived items. */
    public const CATALOG_STATUSES = ['all', 'active', 'inactive', 'required', 'auto_assign', 'archived'];

    public function build(string $librarySearch = '', string $personnelSearch = '', string $structureSearch = '', string $positionSearch = ''): array
    {
        return [
            'summary' => $this->summaryData(),
            'analytics' => $this->analyticsData(),
            $this->libraryItemsKey() => $this->libraryItems($librarySearch),
            $this->assignmentItemsKey() => $this->assignmentItems(),
            'personnels' => $this->personnels($personnelSearch),
            'structures' => $this->structures($structureSearch),
            'positions' => $this->positions($positionSearch),
            'recent_assignments' => $this->recentAssignmentsData(),
        ];
    }

    public function buildGeneral(
        string $personnelSearch = '',
        string $structureSearch = '',
        string $positionSearch = '',
        string $pageName = 'libraryRecentAssignmentsPage'
    ): array {
        return [
            'summary' => $this->summaryData(),
            $this->assignmentItemsKey() => $this->assignmentItems(),
            'personnels' => $this->personnels($personnelSearch),
            'structures' => $this->structures($structureSearch),
            'positions' => $this->positions($positionSearch),
            'recent_assignments' => $this->recentAssignmentsPaginatedData(10, $pageName),
        ];
    }

    public function buildSummary(): array
    {
        return [
            'summary' => $this->summaryData(),
        ];
    }

    public function buildLibrary(string $librarySearch = ''): array
    {
        return [
            $this->libraryItemsKey() => $this->libraryItems($librarySearch),
        ];
    }

    public function buildReports(): array
    {
        return [
            'analytics' => $this->analyticsData(),
        ];
    }

    /**
     * The catalog page: three headline metrics, per-status counts for the filter chips and
     * one page of item cards. Uncached on purpose — a toggle must show up on the next render.
     */
    public function buildCatalog(string $search = '', string $type = '', string $status = 'all', string $pageName = 'libraryPage'): array
    {
        $counts = $this->libraryModel()::query()
            ->selectRaw('SUM(CASE WHEN archived_at IS NULL THEN 1 ELSE 0 END) as all_count')
            ->selectRaw('SUM(CASE WHEN archived_at IS NULL AND is_active = 1 THEN 1 ELSE 0 END) as active_count')
            ->selectRaw('SUM(CASE WHEN archived_at IS NULL AND is_active = 0 THEN 1 ELSE 0 END) as inactive_count')
            ->selectRaw('SUM(CASE WHEN archived_at IS NULL AND is_required = 1 THEN 1 ELSE 0 END) as required_count')
            ->selectRaw('SUM(CASE WHEN archived_at IS NULL AND auto_assign_new_hires = 1 THEN 1 ELSE 0 END) as auto_assign_count')
            ->selectRaw('SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) as archived_count')
            ->first();

        $assignments = $this->assignmentModel()::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN assigned_at >= ? THEN 1 ELSE 0 END) as this_month', [now()->startOfMonth()])
            ->first();

        [$relation, $column] = $this->completionRelation();
        $completed = $this->assignmentModel()::query()
            ->whereHas($relation, fn (Builder $query) => $query->whereNotNull($column))
            ->count();
        $total = (int) ($assignments?->total ?? 0);

        $statusCounts = [];
        foreach (self::CATALOG_STATUSES as $key) {
            $statusCounts[$key] = (int) ($counts?->{$key.'_count'} ?? 0);
        }

        $items = $this->libraryModel()::query()
            ->when($search !== '', fn (Builder $query) => $query->where('title', 'like', '%'.$search.'%'))
            ->when($type !== '', fn (Builder $query) => $query->where($this->typeColumn(), $type))
            ->tap(fn (Builder $query) => match ($status) {
                'active' => $query->whereNull('archived_at')->where('is_active', true),
                'inactive' => $query->whereNull('archived_at')->where('is_active', false),
                'required' => $query->whereNull('archived_at')->where('is_required', true),
                'auto_assign' => $query->whereNull('archived_at')->where('auto_assign_new_hires', true),
                'archived' => $query->whereNotNull('archived_at'),
                default => $query->whereNull('archived_at'),
            })
            ->latest('created_at')
            ->paginate(12, ['*'], $pageName);

        $deletableIds = $this->deletableIds($items->getCollection()->map(fn (Model $item): int => (int) $item->getKey())->all());

        $items->setCollection($items->getCollection()->map(fn (Model $item): array => [
            'id' => (int) $item->getKey(),
            'title' => (string) $item->title,
            'type' => $this->typeLabel((string) $item->{$this->typeColumn()}),
            'meta' => $this->itemMeta($item),
            'url' => $this->itemUrl($item),
            'is_active' => (bool) $item->is_active,
            'is_archived' => $item->archived_at !== null,
            'required' => (bool) $item->is_required,
            'can_delete' => in_array((int) $item->getKey(), $deletableIds, true),
        ]));

        return [
            'metrics' => [
                'active' => $statusCounts['active'],
                'assigned_this_month' => (int) ($assignments?->this_month ?? 0),
                'completion' => $total > 0 ? (int) round($completed * 100 / $total) : 0,
            ],
            'status_counts' => $statusCounts,
            'items' => $items,
        ];
    }

    /**
     * Kitabxanadan tam silinə bilən elementlərin id-ləri. Defolt olaraq heç biri —
     * kitabxana öz silmə qaydasını təyin edəndə bu metodu override edir.
     *
     * @param  list<int>  $ids
     * @return list<int>
     */
    protected function deletableIds(array $ids): array
    {
        return [];
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function libraryModel(): string;

    /**
     * @return class-string<Model>
     */
    abstract protected function assignmentModel(): string;

    /**
     * Relation on the assignment model and its column that marks an assignment as done.
     *
     * @return array{0: string, 1: string}
     */
    abstract protected function completionRelation(): array;

    abstract protected function typeColumn(): string;

    abstract protected function typeLabel(string $type): string;

    abstract protected function itemMeta(Model $item): ?string;

    abstract protected function itemUrl(Model $item): ?string;

    abstract protected function summaryData(): array;

    abstract protected function analyticsData(): array;

    abstract protected function libraryItems(string $librarySearch): array;

    abstract protected function assignmentItems(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function recentAssignmentsData(): array;

    abstract protected function recentAssignmentsPaginatedData(int $perPage, string $pageName): LengthAwarePaginator;

    abstract protected function libraryItemsKey(): string;

    abstract protected function assignmentItemsKey(): string;
}

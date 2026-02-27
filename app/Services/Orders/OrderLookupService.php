<?php

namespace App\Services\Orders;

use App\Models\Candidate;
use App\Models\Component;
use App\Models\OrderType;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Rank;
use App\Models\Structure;
use App\Services\StructureService;
use App\Support\OrderLookupCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OrderLookupService
{
    public function __construct(private readonly StructureService $structureService)
    {
    }

    public function templates(?int $orderId, ?string $search = null): Collection
    {
        if ($search) {
            return OrderType::query()
                ->where('name', 'LIKE', "%{$search}%")
                ->when($orderId, fn (Builder $query) => $query->where('order_id', $orderId))
                ->orderBy('name')
                ->get();
        }

        $cacheKey = OrderLookupCache::key('templates', (string) ($orderId ?? 'all'));

        return Cache::remember($cacheKey, 600, function () use ($orderId) {
            return OrderType::query()
                ->when($orderId, fn (Builder $query) => $query->where('order_id', $orderId))
                ->orderBy('name')
                ->get();
        });
    }

    public function components(?int $templateId): Collection
    {
        if (! $templateId) {
            return collect();
        }

        $cacheKey = OrderLookupCache::key('components', (string) $templateId);

        return Cache::remember($cacheKey, 600, function () use ($templateId) {
            return Component::query()
                ->with('orderType')
                ->where('order_type_id', $templateId)
                ->orderBy('name')
                ->get();
        });
    }

    public function personnels(
        bool $forCandidates,
        array $excludeIds,
        ?string $search = null,
        int $nonCandidateDefaultLimit = 15
    ): Collection
    {
        $normalizedSearch = trim((string) $search);

        if ($forCandidates) {
            return $this->candidatePersonnelQuery($excludeIds, $normalizedSearch)
                ->orderBy('surname')
                ->orderBy('name')
                ->get();
        }

        return $this->activePersonnelQuery($excludeIds, $normalizedSearch)
            ->when($normalizedSearch === '', fn (Builder $query) => $query->limit(max(1, $nonCandidateDefaultLimit)))
            ->orderBy('surname')
            ->orderBy('name')
            ->get();
    }

    public function ranks(): Collection
    {
        $cacheKey = OrderLookupCache::key('ranks', 'all');

        return Cache::remember($cacheKey, 600, function () {
            return Rank::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->get();
        });
    }

    public function mainStructures(): Collection
    {
        $cacheKey = OrderLookupCache::key('main_structures', 'all');

        return Cache::remember($cacheKey, 600, function () {
            return Structure::query()
                ->with('parent')
                ->where('level', 0)
                ->orderBy('id')
                ->get();
        });
    }

    public function structures(?string $search = null): Collection
    {
        if ($search) {
            return $this->structureQuery()
                ->where('name', 'LIKE', "%{$search}%")
                ->get();
        }

        $accessible = implode('-', $this->structureService->getAccessibleStructures());
        $cacheKey = OrderLookupCache::key('structures', md5($accessible ?: 'all'));

        return Cache::remember($cacheKey, 600, fn () => $this->structureQuery()->get());
    }

    public function positions(?string $search = null): Collection
    {
        if ($search) {
            return Position::query()
                ->where('name', 'LIKE', "%{$search}%")
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $cacheKey = OrderLookupCache::key('positions', 'all');

        return Cache::remember($cacheKey, 600, function () {
            return Position::query()
                ->orderBy('name')
                ->get(['id', 'name']);
        });
    }

    private function activePersonnelQuery(array $excludeIds, ?string $search): Builder
    {
        $normalizedSearch = trim((string) $search);

        return Personnel::query()
            ->when($normalizedSearch !== '', function (Builder $query) use ($normalizedSearch) {
                $this->applyMultiTermSearch($query, $normalizedSearch, [
                    'name',
                    'surname',
                    'patronymic',
                    'tabel_no',
                ]);
            })
            ->active()
            ->whereIn('structure_id', $this->structureService->getAccessibleStructures())
            ->whereNotIn('id', $excludeIds)
            ->orderBy('position_id')
            ->orderBy('structure_id');
    }

    private function candidatePersonnelQuery(array $excludeIds, ?string $search): Builder
    {
        $normalizedSearch = trim((string) $search);

        return Candidate::query()
            ->when($normalizedSearch !== '', function (Builder $query) use ($normalizedSearch) {
                $this->applyMultiTermSearch($query, $normalizedSearch, [
                    'name',
                    'surname',
                    'patronymic',
                ], 'id');
            })
            ->whereNotIn('id', $excludeIds)
            ->where('status_id', 30);
    }

    private function applyMultiTermSearch(Builder $query, string $search, array $columns, ?string $numericColumn = null): void
    {
        $terms = collect(preg_split('/\s+/', $search))
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->values();

        foreach ($terms as $term) {
            $query->where(function (Builder $nested) use ($columns, $term, $numericColumn) {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $nested->where($column, 'LIKE', "%{$term}%");
                        continue;
                    }

                    $nested->orWhere($column, 'LIKE', "%{$term}%");
                }

                if ($numericColumn !== null && ctype_digit($term)) {
                    $nested->orWhere($numericColumn, (int) $term);
                }
            });
        }
    }

    private function structureQuery(): Builder
    {
        return Structure::query()
            ->withRecursive('subs')
            ->accessible()
            ->whereNotNull('parent_id')
            ->where('code', '<>', 0)
            ->orderBy('code');
    }
}

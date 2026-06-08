<?php

namespace App\Services\Orders;

use App\Models\Component;
use App\Models\OrderType;
use App\Modules\Orders\Domain\Contracts\AccessibleStructureScopeReadRepository;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\PersonnelLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\RankPositionLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\StructureLookupReadRepository;
use App\Support\OrderLookupCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class OrderLookupService
{
    public function __construct(
        private readonly AccessibleStructureScopeReadRepository $structureScopeLookup,
        private readonly OrderTypeStatusLookupReadRepository $orderTypeStatusLookup,
        private readonly PersonnelLookupReadRepository $personnelLookup,
        private readonly StructureLookupReadRepository $structureLookup,
        private readonly RankPositionLookupReadRepository $rankPositionLookup,
    ) {}

    public function templates(?int $orderId, ?string $search = null): Collection
    {
        if (trim((string) $search) !== '') {
            return $this->orderTypeStatusLookup->orderTypes($orderId, $search);
        }

        $cacheKey = OrderLookupCache::key('templates', (string) ($orderId ?? 'all'));

        return Cache::remember($cacheKey, 600, fn () => $this->orderTypeStatusLookup->orderTypes($orderId));
    }

    public function components(?int $templateId): Collection
    {
        if (! $templateId) {
            return collect();
        }

        $usesOrderTypeColumn = Schema::hasColumn('components', 'order_type_id');
        $cacheKey = OrderLookupCache::key('components', ($usesOrderTypeColumn ? 'type:' : 'order:').(string) $templateId);

        return Cache::remember($cacheKey, 600, function () use ($templateId, $usesOrderTypeColumn) {
            $query = Component::query();

            if ($usesOrderTypeColumn) {
                $query->with('orderType')
                    ->where('order_type_id', $templateId);
            } else {
                $orderId = OrderType::query()
                    ->whereKey($templateId)
                    ->value('order_id');

                if (! $orderId) {
                    return collect();
                }

                $query->where('order_id', $orderId);
            }

            return $query
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
            return $this->personnelLookup->candidatePersonnelReady($excludeIds, $normalizedSearch);
        }

        return $this->personnelLookup->activePersonnel(
            $excludeIds,
            $this->structureScopeLookup->accessibleStructureIds(),
            $normalizedSearch,
            $nonCandidateDefaultLimit
        );
    }

    public function ranks(?string $search = null): Collection
    {
        $normalized = trim((string) $search);

        if ($normalized !== '') {
            return $this->rankPositionLookup->activeRanks($normalized);
        }

        $cacheKey = OrderLookupCache::key('ranks', 'all');

        return Cache::remember($cacheKey, 600, fn () => $this->rankPositionLookup->activeRanks());
    }

    public function mainStructures(?string $search = null): Collection
    {
        $normalized = trim((string) $search);

        if ($normalized !== '') {
            return $this->structureLookup->mainStructures($normalized);
        }

        $cacheKey = OrderLookupCache::key('main_structures', 'all');

        return Cache::remember($cacheKey, 600, function () {
            return $this->structureLookup->mainStructures();
        });
    }

    public function structures(?string $search = null): Collection
    {
        if ($search) {
            return $this->structureLookup->accessibleStructuresTree($search);
        }

        $accessible = implode('-', $this->structureScopeLookup->accessibleStructureIds());
        $cacheKey = OrderLookupCache::key('structures', md5($accessible ?: 'all'));

        return Cache::remember($cacheKey, 600, fn () => $this->structureLookup->accessibleStructuresTree());
    }

    public function positions(?string $search = null): Collection
    {
        if ($search) {
            return $this->rankPositionLookup->positions($search);
        }

        $cacheKey = OrderLookupCache::key('positions', 'all');

        return Cache::remember($cacheKey, 600, fn () => $this->rankPositionLookup->positions());
    }

}

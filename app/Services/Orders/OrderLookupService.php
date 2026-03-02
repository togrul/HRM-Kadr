<?php

namespace App\Services\Orders;

use App\Models\Component;
use App\Modules\Orders\Domain\Contracts\AccessibleStructureScopeReadRepository;
use App\Modules\Orders\Domain\Contracts\OrderTypeStatusLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\PersonnelLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\RankPositionLookupReadRepository;
use App\Modules\Orders\Domain\Contracts\StructureLookupReadRepository;
use App\Support\OrderLookupCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
            return $this->personnelLookup->candidatePersonnelReady($excludeIds, $normalizedSearch);
        }

        return $this->personnelLookup->activePersonnel(
            $excludeIds,
            $this->structureScopeLookup->accessibleStructureIds(),
            $normalizedSearch,
            $nonCandidateDefaultLimit
        );
    }

    public function ranks(): Collection
    {
        $cacheKey = OrderLookupCache::key('ranks', 'all');

        return Cache::remember($cacheKey, 600, fn () => $this->rankPositionLookup->activeRanks());
    }

    public function mainStructures(): Collection
    {
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

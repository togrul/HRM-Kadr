<?php

namespace App\Livewire\Traits\Orders;

use App\Services\Orders\OrderLookupService;
use Illuminate\Support\Collection;

trait ResolvesOrderLookups
{
    protected OrderLookupService $orderLookupService;
    protected array $staticLookups = [];
    protected array $lookupCache = [];

    protected function cachedLookup(string $key, callable $resolver)
    {
        if (! array_key_exists($key, $this->staticLookups)) {
            $this->staticLookups[$key] = $resolver();
        }

        return $this->staticLookups[$key];
    }

    protected function memoizedLookup(string $key, array $context, callable $resolver)
    {
        $hash = $key.'::'.md5(serialize($context));

        if (! array_key_exists($hash, $this->lookupCache)) {
            $this->lookupCache[$hash] = $resolver();
        }

        return $this->lookupCache[$hash];
    }

    protected function resolveLookupCollections(
        bool $needsPersonnelLookup,
        bool $isCandidateOrder,
        ?int $selectedOrder,
        ?int $selectedTemplate,
        string $searchTemplate,
        string $searchPersonnel,
        string $searchStructure,
        string $searchPosition,
        array $personnelIdList
    ): array {
        $components = $this->memoizedLookup(
            'components',
            [$selectedTemplate],
            fn () => $this->orderLookupService->components($selectedTemplate)
        );

        $lookups = [
            'templates' => $this->memoizedLookup(
                'templates',
                [$selectedOrder, $searchTemplate],
                fn () => $this->orderLookupService->templates($selectedOrder, $searchTemplate)
            ),
            'components' => $components,
            'personnels' => $needsPersonnelLookup
                ? $this->memoizedLookup(
                    'personnels',
                    [$isCandidateOrder, $personnelIdList, $searchPersonnel],
                    fn () => $this->orderLookupService->personnels($isCandidateOrder, $personnelIdList, $searchPersonnel)
                )
                : collect(),
            'ranks' => $this->cachedLookup('ranks', fn () => $this->orderLookupService->ranks()),
            'main_structures' => $this->cachedLookup('main_structures', fn () => $this->orderLookupService->mainStructures()),
            'structures' => $this->memoizedLookup(
                'structures',
                [$searchStructure],
                fn () => $this->orderLookupService->structures($searchStructure)
            ),
            'positions' => $this->memoizedLookup(
                'positions',
                [$searchPosition],
                fn () => $this->orderLookupService->positions($searchPosition)
            ),
        ];

        $this->rememberComponentDefinitions($components);

        return $lookups;
    }

    abstract protected function rememberComponentDefinitions(Collection $components): void;
}

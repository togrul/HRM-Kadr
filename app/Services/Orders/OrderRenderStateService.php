<?php

namespace App\Services\Orders;

class OrderRenderStateService
{
    protected array $staticLookups = [];

    protected array $lookupCache = [];

    public function __construct(
        protected OrderLookupService $orderLookupService,
        protected OrderRenderPayloadBuilder $payloadBuilder
    ) {}

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

    public function resolveLookupCollections(
        bool $isCandidateOrder,
        ?int $selectedOrder,
        ?int $selectedTemplate,
        string $searchTemplate,
        string $searchPersonnel,
        string $searchRank,
        string $searchMainStructure,
        string $searchStructure,
        string $searchPosition,
        array $personnelIdList,
        array $componentIdList,
        array $selectedDropdownValues,
        array $loadedOptionGroups,
        array $visibleFields,
        callable $rememberComponentDefinitions
    ): array {
        $trimmedPersonnelSearch = trim($searchPersonnel);
        $selectedPersonnelIds = collect($personnelIdList)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $selectedComponentIds = collect($componentIdList)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $personnelDefaultLimit = (int) config('orders.form.personnel_default_limit', 15);
        $visibleLookupFields = collect($visibleFields)
            ->map(fn ($field) => trim((string) $field))
            ->filter()
            ->unique()
            ->values();

        $hasField = fn (string $field): bool => $visibleLookupFields->contains($field);
        $wantsGroup = fn (string $group): bool => (bool) ($loadedOptionGroups[$group] ?? false);
        $hasSelectedValue = function (string $field) use ($selectedPersonnelIds, $selectedDropdownValues): bool {
            return match ($field) {
                'personnel_id' => $selectedPersonnelIds !== [],
                default => ! empty($selectedDropdownValues[$field] ?? []),
            };
        };

        $shouldResolveTemplates = $wantsGroup('templates') || trim($searchTemplate) !== '' || ! empty($selectedTemplate);
        $shouldResolveComponents = $hasField('component_id') && ($wantsGroup('components') || ! empty($selectedTemplate) || $selectedComponentIds !== []);
        $shouldResolvePersonnel = $hasField('personnel_id')
            && ($wantsGroup('personnels') || $trimmedPersonnelSearch !== '' || $hasSelectedValue('personnel_id'));
        $shouldResolveRanks = $hasField('rank_id')
            && ($wantsGroup('ranks') || trim($searchRank) !== '' || $hasSelectedValue('rank_id'));
        $shouldResolveMainStructures = $hasField('structure_main_id')
            && ($wantsGroup('main_structures') || trim($searchMainStructure) !== '' || $hasSelectedValue('structure_main_id'));
        $shouldResolveStructures = $hasField('structure_id')
            && ($wantsGroup('structures') || trim($searchStructure) !== '' || $hasSelectedValue('structure_id'));
        $shouldResolvePositions = $hasField('position_id')
            && ($wantsGroup('positions') || trim($searchPosition) !== '' || $hasSelectedValue('position_id'));

        $components = $shouldResolveComponents
            ? $this->memoizedLookup(
                'components',
                [$selectedTemplate],
                fn () => $this->orderLookupService->components($selectedTemplate)
            )
            : collect();

        $lookups = [
            'templates' => $shouldResolveTemplates
                ? $this->memoizedLookup(
                    'templates',
                    [$selectedOrder, $searchTemplate],
                    fn () => $this->orderLookupService->templates($selectedOrder, $searchTemplate)
                )
                : collect(),
            'components' => $components,
            'personnels' => $shouldResolvePersonnel
                ? $this->memoizedLookup(
                    'personnels',
                    [$isCandidateOrder, $selectedPersonnelIds, $trimmedPersonnelSearch],
                    fn () => $this->orderLookupService->personnels(
                        $isCandidateOrder,
                        $selectedPersonnelIds,
                        $trimmedPersonnelSearch,
                        $personnelDefaultLimit
                    )
                )
                : collect(),
            'ranks' => $shouldResolveRanks
                ? (
                    trim($searchRank) !== ''
                        ? $this->memoizedLookup(
                            'ranks',
                            [$searchRank],
                            fn () => $this->orderLookupService->ranks($searchRank)
                        )
                        : $this->cachedLookup('ranks', fn () => $this->orderLookupService->ranks())
                )
                : collect(),
            'main_structures' => $shouldResolveMainStructures
                ? (
                    trim($searchMainStructure) !== ''
                        ? $this->memoizedLookup(
                            'main_structures',
                            [$searchMainStructure],
                            fn () => $this->orderLookupService->mainStructures($searchMainStructure)
                        )
                        : $this->cachedLookup('main_structures', fn () => $this->orderLookupService->mainStructures())
                )
                : collect(),
            'structures' => $shouldResolveStructures
                ? $this->memoizedLookup(
                    'structures',
                    [$searchStructure],
                    fn () => $this->orderLookupService->structures($searchStructure)
                )
                : collect(),
            'positions' => $shouldResolvePositions
                ? $this->memoizedLookup(
                    'positions',
                    [$searchPosition],
                    fn () => $this->orderLookupService->positions($searchPosition)
                )
                : collect(),
        ];

        $rememberComponentDefinitions($components);

        return $lookups;
    }

    public function buildRenderPayload(
        array $lookups,
        ?string $selectedBlade,
        ?string $personnelName,
        array $selectedPersonnelNumbers,
        callable $registerOptionLabels,
        callable $personnelLabelResolver
    ): array {
        return $this->payloadBuilder->build(
            $lookups,
            $selectedBlade,
            $personnelName,
            $selectedPersonnelNumbers,
            $registerOptionLabels,
            $personnelLabelResolver
        );
    }
}

<?php

namespace App\Services\Orders;

use App\Services\OrderCollectionListsService;

class OrderRenderPayloadBuilder
{
    public function build(
        array $lookup,
        ?string $selectedBlade,
        ?string $personnelName,
        array $selectedPersonnelNumbers,
        callable $registerOptionLabels,
        callable $personnelLabelResolver
    ): array {
        $rankOptions = $this->optionsFromCollection($lookup['ranks'], fn ($rank) => trim((string) $rank->name));
        $registerOptionLabels('rank_id', $rankOptions);

        $personnelOptions = $this->optionsFromCollection($lookup['personnels'], $personnelLabelResolver);
        $registerOptionLabels('personnel_id', $personnelOptions);

        $mainStructureOptions = $this->optionsFromCollection($lookup['main_structures'], fn ($structure) => trim((string) $structure->name));
        $registerOptionLabels('structure_main_id', $mainStructureOptions);

        $positionOptions = $this->optionsFromCollection($lookup['positions'], fn ($position) => trim((string) $position->name));
        $registerOptionLabels('position_id', $positionOptions);

        $defaultCollections = [
            '_templates' => $lookup['templates'],
            '_components' => $lookup['components'],
            '_personnels' => $personnelOptions,
            '_ranks' => $rankOptions,
            '_main_structures' => $mainStructureOptions,
            '_structures' => $lookup['structures'],
            '_positions' => $positionOptions,
        ];

        $bladeCollections = (new OrderCollectionListsService(
            selectedBlade: $selectedBlade,
            personnel_name: $personnelName,
            selectedPersonnelNumbers: $selectedPersonnelNumbers
        ))->handle();

        if (isset($bladeCollections['_transportations'])) {
            $transportOptions = collect($bladeCollections['_transportations'])
                ->map(fn ($item) => [
                    'id' => $item['id'],
                    'label' => $item['name'],
                ])
                ->values()
                ->all();

            $bladeCollections['_transportations'] = $transportOptions;
            $registerOptionLabels('transportation', $transportOptions);
        }

        return array_merge($defaultCollections, $bladeCollections);
    }

    private function optionsFromCollection($collection, callable $labelResolver): array
    {
        return collect($collection)
            ->map(fn ($item) => [
                'id' => (int) data_get($item, 'id'),
                'label' => (string) $labelResolver($item),
            ])
            ->unique('id')
            ->values()
            ->all();
    }
}

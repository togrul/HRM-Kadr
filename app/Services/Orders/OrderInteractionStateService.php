<?php

namespace App\Services\Orders;

class OrderInteractionStateService
{
    /**
     * @param  callable(int):array<int,array{id:int,name:string}>  $structureLineageResolver
     * @param  callable(array<int,array{id:int,name:string}>,bool):string  $structureLabelBuilder
     * @return array{list:string,field:string,key:int,id:int,label:string}|null
     */
    public function resolveStructureSelection(
        mixed $id,
        ?string $list,
        ?string $field,
        mixed $key,
        mixed $isCoded,
        callable $structureLineageResolver,
        callable $structureLabelBuilder
    ): ?array {
        if (is_array($id)) {
            $list = (string) ($id['list'] ?? $list);
            $field = (string) ($id['field'] ?? $field);
            $key = $id['row'] ?? $id['key'] ?? $key;
            $isCoded = $id['coded'] ?? $isCoded;
            $id = $id['id'] ?? null;
        }

        if ($id === null || $list === null || $field === null || $key === null) {
            return null;
        }

        $resolvedId = (int) $id;
        $resolvedKey = (int) $key;
        $lineage = $structureLineageResolver($resolvedId);
        $label = $structureLabelBuilder($lineage, (bool) $isCoded);

        if ($field === 'structure_id' && ! $isCoded) {
            $label = (string) (collect($lineage)->last()['name'] ?? $label);
        }

        return [
            'list' => $list,
            'field' => $field,
            'key' => $resolvedKey,
            'id' => $resolvedId,
            'label' => $label,
        ];
    }

    /**
     * @param  array<int,array{id:int,dynamic_fields:string|null}>  $componentDefinitions
     * @param  callable(int):?string  $dynamicFieldsFallbackResolver
     * @return array<int,string>
     */
    public function resolveSelectedComponentFields(
        mixed $componentId,
        array $componentDefinitions,
        callable $dynamicFieldsFallbackResolver
    ): array {
        $resolvedId = $componentId !== null ? (int) $componentId : null;

        if (! $resolvedId) {
            return [];
        }

        $definition = $componentDefinitions[$resolvedId]['dynamic_fields'] ?? null;
        if ($definition === null) {
            $definition = $dynamicFieldsFallbackResolver($resolvedId);
        }

        return $definition
            ? array_filter(explode(',', (string) $definition))
            : [];
    }

    /**
     * @param  callable(int):array{order_id:int|null,selected_blade:string|null}|null  $orderTypeResolver
     * @return array{showComponent:bool,selectedTemplate:int,orderId:int|null,selectedBlade:string|null}
     */
    public function resolveTemplateSelection(
        mixed $value,
        ?int $selectedOrder,
        ?callable $orderTypeResolver
    ): array {
        $resolvedTemplate = (int) $value;

        $orderId = null;
        $selectedBlade = null;
        if (empty($selectedOrder) && $orderTypeResolver !== null) {
            $resolved = $orderTypeResolver($resolvedTemplate);
            $orderId = $resolved['order_id'] ?? null;
            $selectedBlade = $resolved['selected_blade'] ?? null;
        }

        return [
            'showComponent' => $resolvedTemplate > 0,
            'selectedTemplate' => $resolvedTemplate,
            'orderId' => $orderId,
            'selectedBlade' => $selectedBlade,
        ];
    }

    /**
     * @param  callable(int):?object  $candidateResolver
     * @param  callable(int):?object  $personnelResolver
     * @return array{name:string,surname:string}|null
     */
    public function resolvePersonnelName(
        mixed $value,
        int $orderId,
        int $candidateOrderId,
        callable $candidateResolver,
        callable $personnelResolver
    ): ?array {
        $resolvedId = $value !== null ? (int) $value : null;
        if (! $resolvedId) {
            return null;
        }

        $model = $orderId === $candidateOrderId
            ? $candidateResolver($resolvedId)
            : $personnelResolver($resolvedId);

        if (! $model) {
            return null;
        }

        return [
            'name' => (string) data_get($model, 'name', ''),
            'surname' => (string) data_get($model, 'surname', ''),
        ];
    }
}

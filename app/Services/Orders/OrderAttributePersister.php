<?php

namespace App\Services\Orders;

use App\Models\OrderLog;

class OrderAttributePersister
{
    public function persist(OrderLog $order, array $attributes, string $method): void
    {
        foreach ($attributes as $index => $attributeRow) {
            $componentId = $attributeRow['component_id'];
            $rowNumber = $attributeRow['row'] ?? $index;

            unset($attributeRow['component_id']);

            $prepared = $this->prepareAttributePayload($attributeRow);

            $this->upsertRow($order, $componentId, $rowNumber, $prepared, $order->order_no, $method);
        }
    }

    protected function prepareAttributePayload(array $attributeData): array
    {
        $attributes = [];

        foreach ($attributeData as $key => $value) {
            $attributes[$key] = [
                'id' => is_array($value) ? ($value['id'] ?? null) : null,
                'value' => is_array($value) ? ($value['name'] ?? null) : $value,
            ];
        }

        return $attributes;
    }

    protected function upsertRow(OrderLog $order, int $componentId, int $rowNumber, array $attributes, string $orderNo, string $method): void
    {
        if ($method === 'create') {
            $order->attributes()->create([
                'component_id' => $componentId,
                'attributes' => $attributes,
                'row_number' => $rowNumber,
            ]);

            return;
        }

        $order->attributes()->updateOrCreate(
            [
                'component_id' => $componentId,
                'order_no' => $orderNo,
                'row_number' => $rowNumber,
                'attributes->$fullname->value' => $attributes['$fullname']['value'] ?? null,
            ],
            ['attributes' => $attributes]
        );
    }
}

<?php

namespace App\Services;

use App\Models\Order;

class AttributeProcessService
{
    public function __construct(
        protected $orderModel,
        protected array $attributeData,
        protected $method,
        protected $index,
        protected $orderNo
    ) {}

    public function process(): void
    {
        $componentId = $this->attributeData['component_id'];
        $rowNumber = $this->attributeData['row'] ?? $this->index;
        unset($this->attributeData['component_id']);

        $preparedAttributes = $this->prepare($this->attributeData);

        if ($this->method === 'create') {
            $this->create($this->orderModel, $componentId, $preparedAttributes, $rowNumber);
        } else {
            $this->updateOrCreate($this->orderModel, $componentId, $preparedAttributes, $rowNumber);
        }}

    private function prepare($attributeData): array
    {
        $attributes = [];
        foreach ($attributeData as $key => $value) {
            $attributes[$key] = [
                'id' => is_array($value) ? $value['id'] : null,
                'value' => is_array($value) ? $value['name'] : $value,
            ];
        }

        return $attributes;
    }

    private function create($orderModel, $componentId, $attributes, $rowNumber): void
    {
        $orderModel->attributes()->create([
            'component_id' => $componentId,
            'attributes' => $attributes,
            'row_number' => $rowNumber,
        ]);
    }

    private function updateOrCreate($orderModel, $componentId, $attributes, $rowNumber): void
    {
        $orderModel->attributes()->updateOrCreate(
            [
                'component_id' => $componentId,
                'order_no' => $this->orderNo,
                'row_number' => $rowNumber,
                'attributes->$fullname->value' => $attributes['$fullname']['value'],

            ],
            [
                'attributes' => $attributes,
            ]
        );
    }
}

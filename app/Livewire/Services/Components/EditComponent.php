<?php

namespace App\Livewire\Services\Components;

use App\Livewire\Traits\ComponentCrud;
use Livewire\Component;

class EditComponent extends Component
{
    use ComponentCrud;

    public $componentModelData;

    protected function fillComponent()
    {
        $this->componentModelData = \App\Models\Component::with('orderType', 'rank')
            ->where('id', $this->componentModel)
            ->first();

        $this->component = [
            'name' => $this->componentModelData->name,
            'content' => $this->componentModelData->content,
            'dynamic_fields' => $this->componentModelData->dynamic_fields,
            'title' => $this->componentModelData->title,
        ];

        if (! empty($this->componentModelData->rank_id)) {
            $this->component['rank_id'] = [
                'id' => $this->componentModelData->rank->id,
                'name' => $this->componentModelData->rank->name,
            ];
            $this->orderId = $this->component['rank_id']['id'];
            $this->orderName = $this->component['rank_id']['name'];
        }

        if (! empty($this->componentModelData->order_type_id)) {
            $this->component['order_type_id'] = [
                'id' => $this->componentModelData->orderType->id,
                'name' => $this->componentModelData->orderType->name,
            ];
            $this->orderId = $this->component['order_type_id']['id'];
            $this->orderName = $this->component['order_type_id']['name'];
        }
    }

    public function store()
    {
        $this->validate();

        $this->componentModelData->update($this->modifyArray($this->component));

        $this->dispatch('componentAdded', __('Component was updated successfully!'));
    }
}

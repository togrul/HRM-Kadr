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
        $this->componentModelData = \App\Models\Component::with('orderType')
                                        ->where('id',$this->componentModel)
                                        ->first();

        $updatedData = $this->componentModelData->toArray();

        $this->component = [
            'name' => $updatedData['name'],
            'content' => $updatedData['content'],
            'dynamic_fields' => $updatedData['dynamic_fields']
        ];

        if(!empty($updatedData['order_type_id']))
        {
            $this->component['order_type_id'] = [
                'id' => $updatedData['order_type']['id'],
                'name' => $updatedData['order_type']['name'],
            ];
            $this->orderId = $updatedData['order_type']['id'];
            $this->orderName = $updatedData['order_type']['name'];
        }
    }

    public function store()
    {
        $this->validate();

        $this->componentModelData->update($this->modifyArray($this->component));

        $this->dispatch('componentAdded',__('Component was updated successfully!'));
    }
}

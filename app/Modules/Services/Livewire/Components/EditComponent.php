<?php

namespace App\Modules\Services\Livewire\Components;

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

        $this->component['rank_id'] = $this->componentModelData->rank_id;
        $this->component['order_type_id'] = $this->componentModelData->order_type_id;
    }

    public function store()
    {
        $this->validate();

        $this->componentModelData->update($this->modifyArray($this->component));

        $this->dispatch('componentAdded', __('Component was updated successfully!'));
    }
}

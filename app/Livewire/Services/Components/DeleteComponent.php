<?php

namespace App\Livewire\Services\Components;

use Livewire\Attributes\On;
use Livewire\Component;

class DeleteComponent extends Component
{
    public ?\App\Models\Component $component;

    #[On('setDeleteComponent')]
    public function setDeleteComponent($componentId)
    {
        $this->component = \App\Models\Component::where('id', $componentId)->first();

        $this->dispatch('deleteComponentWasSet');
    }

    public function deleteComponent()
    {
        // $this->authorize('delete',$this->component);

        \App\Models\Component::destroy($this->component->id);

        $this->component = null;

        $this->dispatch('componentWasDeleted', __('Component was deleted!'));
    }

    public function render()
    {
        return view('livewire.services.components.delete-component');
    }
}

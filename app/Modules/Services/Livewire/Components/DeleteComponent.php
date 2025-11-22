<?php

namespace App\Modules\Services\Livewire\Components;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteComponent extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $componentId = null;

    #[On('setDeleteComponent')]
    public function setDeleteComponent($componentId)
    {
        $component = \App\Models\Component::query()
            ->select('id')
            ->find($componentId);

        if (! $component) {
            $this->componentId = null;

            return;
        }

        // $this->authorize('delete', $component);

        $this->componentId = (int) $component->id;

        $this->dispatch('deleteComponentWasSet');
    }

    public function deleteComponent()
    {
        if (! $this->componentId) {
            return;
        }

        $component = \App\Models\Component::query()
            ->select('id')
            ->find($this->componentId);

        if (! $component) {
            $this->componentId = null;

            return;
        }

        // $this->authorize('delete', $component);

        $component->delete();

        $this->componentId = null;

        $this->dispatch('componentWasDeleted', __('Component was deleted!'));
    }

    public function render()
    {
        return view('services::livewire.services.components.delete-component');
    }
}

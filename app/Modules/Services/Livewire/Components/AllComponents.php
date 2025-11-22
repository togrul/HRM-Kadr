<?php

namespace App\Modules\Services\Livewire\Components;

use App\Livewire\Traits\SideModalAction;
use App\Models\Component as ComponentModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['componentAdded', 'componentWasDeleted'])]
class AllComponents extends Component
{
    use AuthorizesRequests,SideModalAction,WithPagination;

    #[Url(except: '')]
    public $search = '';

    public function setDeleteComponent($componentId)
    {
        $this->dispatch('setDeleteComponent', $componentId);
    }

    public function render()
    {
        $_components = ComponentModel::with('orderType', 'orderType.order')
            ->when(! empty($this->search), function ($q) {
                $q->where('name', 'LIKE', "%$this->search%");
            })
            ->paginate(14);

        return view('services::livewire.services.components.all-components', compact('_components'));
    }
}

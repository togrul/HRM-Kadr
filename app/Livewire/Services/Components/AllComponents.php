<?php

namespace App\Livewire\Services\Components;

use App\Livewire\Traits\SideModalAction;
use App\Models\Component as ComponentModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Livewire\Component;

#[On(['componentAdded','componentWasDeleted'])]
class AllComponents extends Component
{
    use SideModalAction,AuthorizesRequests,WithPagination;

    #[Url(except: '')]
    public $search = "";

    public function setDeleteComponent($componentId)
    {
        $this->dispatch('setDeleteComponent',$componentId);
    }

    public function render()
    {
        $_components = ComponentModel::with('orderType','orderType.order')
            ->when(!empty($this->search),function($q)
            {
                $q->where('name','LIKE',"%{$this->search}%");
            })
            ->paginate(14);

        return view('livewire.services.components.all-components',compact('_components'));
    }
}

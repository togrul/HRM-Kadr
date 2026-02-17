<?php

namespace App\Modules\Services\Livewire\Components;

use App\Livewire\Traits\SideModalAction;
use App\Models\Component as ComponentModel;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $_components = ComponentModel::with('orderType', 'orderType.order')
            ->when(! empty($this->search), function ($q) {
                $q->where('name', 'LIKE', "%$this->search%");
            })
            ->paginate(14)
            ->withQueryString();

        $_components = $this->decorateComponents($_components);

        return view('services::livewire.services.components.all-components', compact('_components'));
    }

    protected function decorateComponents(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $start = ($paginated->currentPage() - 1) * $paginated->perPage();

        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (ComponentModel $component, int $index) use ($start) {
                $component->row_no = $start + $index + 1;

                return $component;
            })
        );

        return $paginated;
    }
}

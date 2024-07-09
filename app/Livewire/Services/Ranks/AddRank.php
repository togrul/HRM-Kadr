<?php

namespace App\Livewire\Services\Ranks;

use App\Livewire\Forms\RankForm;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AddRank extends Component
{
    use AuthorizesRequests;

    public $title;

    public RankForm $form;

    public function mount()
    {
        // $this->authorize('manage-settings',$this->rank);
        $this->title = __('Add rank');
    }

    public function store()
    {
        $this->form->create();

        $this->dispatch('rankAdded',__('Rank was added successfully!'));
    }

    public function render()
    {
        return view('livewire.services.ranks.add-rank');
    }
}

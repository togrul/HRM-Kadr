<?php

namespace App\Livewire\Services\Ranks;

use App\Livewire\Forms\RankForm;
use App\Models\Rank;
use Livewire\Component;

class EditRank extends Component
{
    public $title;

    public RankForm $form;

    public function mount(Rank $rankModel)
    {
        $this->form->setPost($rankModel);
    }

    public function store()
    {
        $this->form->update();

        $this->dispatch('rankAdded', __('Rank was added successfully!'));
    }

    public function render()
    {
        return view('livewire.services.ranks.edit-rank');
    }
}

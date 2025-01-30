<?php

namespace App\Livewire\Services\Ranks;

use App\Livewire\Forms\RankForm;
use App\Livewire\Traits\SelectListTrait;
use App\Models\RankCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AddRank extends Component
{
    use AuthorizesRequests;
    use SelectListTrait;

    public string $title;

    public RankForm $form;

    public array $data = [];

    public function mount()
    {
        // $this->authorize('manage-settings',$this->rank);
        $this->title = __('Add rank');
    }

    public function store()
    {
        $this->form->create($this->data);

        $this->dispatch('rankAdded', __('Rank was added successfully!'));
    }

    #[Computed]
    public function rankCategory()
    {
        return RankCategory::all();
    }

    public function render()
    {
        return view('livewire.services.ranks.add-rank');
    }
}

<?php

namespace App\Livewire\Services\Ranks;

use App\Livewire\Forms\RankForm;
use App\Livewire\Traits\SelectListTrait;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Rank;
use App\Models\RankCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditRank extends Component
{
    use SelectListTrait;
    use DropdownConstructTrait;
    public string $title;

    public RankForm $form;

    public function mount(Rank $rankModel)
    {
        $rankModel->load('rankCategory');
        $this->form->setPost($rankModel);
    }

    #[Computed]
    public function rankCategoryOptions(): array
    {
        $base = RankCategory::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: null,
            searchTerm: null,
            selectedId: $this->form->rank_category_id,
            limit: 100
        );
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

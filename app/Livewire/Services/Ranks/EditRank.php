<?php

namespace App\Livewire\Services\Ranks;

use App\Livewire\Forms\RankForm;
use App\Livewire\Traits\SelectListTrait;
use App\Models\Rank;
use App\Models\RankCategory;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditRank extends Component
{
    use SelectListTrait;
    public string $title;

    public RankForm $form;

    public array $data = [];

    public function mount(Rank $rankModel)
    {
        $rankModel->load('rankCategory');
        $this->data['rank_category_id'] = $rankModel->rankCategory
            ? ['id' => $rankModel->rankCategory->id, 'name' => $rankModel->rankCategory->name]
            : ['id' => -1, 'name' => '---'];

        $this->form->setPost($rankModel);
    }

    #[Computed]
    public function rankCategory()
    {
        return RankCategory::all();
    }

    public function store()
    {
        $this->form->update($this->data);

        $this->dispatch('rankAdded', __('Rank was added successfully!'));
    }

    public function render()
    {
        return view('livewire.services.ranks.edit-rank');
    }
}

<?php

namespace App\Modules\Services\Livewire\Ranks;

use App\Livewire\Forms\RankForm;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\RankCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AddRank extends Component
{
    use AuthorizesRequests;
    use DropdownConstructTrait;

    public string $title;

    public RankForm $form;

    public function mount()
    {
        // $this->authorize('manage-settings',$this->rank);
        $this->title = __('Add rank');
    }

    public function store()
    {
        $this->form->create();

        $this->dispatch('rankAdded', __('Rank was added successfully!'));
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

    public function render()
    {
        return view('services::livewire.services.ranks.add-rank');
    }
}

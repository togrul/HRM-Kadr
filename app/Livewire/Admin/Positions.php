<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Position;
use App\Models\RankCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['positionUpdated', 'deleted'])]
class Positions extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use DropdownConstructTrait;

    public string $searchRankCategory = '';

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:positions,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
            'form.rank_category_id' => 'nullable|integer|exists:rank_categories,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
            'form.rank_category_id' => __('Rank category'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'id' => null,
            'name' => '',
            'rank_category_id' => null,
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? Position::with('rankCategory:id,name')->find($id)
            : null;

        $this->form = $this->formDefaults();

        if ($this->model) {
            $this->form['id'] = $this->model->id;
            $this->form['name'] = $this->model->name;
            $this->form['rank_category_id'] = $this->model->rank_category_id;
        }
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = Position::find($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->model
            ? $this->model->update($this->form)
            : Position::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('positionUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $positions = Position::all();
        return view('livewire.admin.positions', compact('positions'));
    }

    public function rankCategoryOptions(): array
    {
        $base = RankCategory::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchRankCategory'),
            selectedId: data_get($this->form, 'rank_category_id'),
            limit: 80
        );
    }
}

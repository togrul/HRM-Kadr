<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Models\RankCategory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['rankCategoryUpdated', 'deleted'])]
class RankCategories extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:positions,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
            'form.vacation_days_count' => 'required|integer|min:0',
            'form.contract_duration' => 'required|integer|min:0',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
            'form.vacation_days_count' => __('Vacation days count'),
            'form.contract_duration' => __('Contract duration'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? RankCategory::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];

        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = RankCategory::find($id);

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
            : RankCategory::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('rankCategoryUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $rankCategories = RankCategory::all();
        return view('admin::livewire.admin.rank-categories', compact('rankCategories'));
    }
}

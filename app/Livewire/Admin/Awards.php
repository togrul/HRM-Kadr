<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\SelectListTrait;
use App\Models\Award;
use App\Models\AwardType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['awardsUpdated', 'deleted', 'awardTypeUpdated'])]
class Awards extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use SelectListTrait;
    use WithPagination;

    public string $selectedType;

    public bool $showChild = false;
    public $childModel = null;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1unique:awards,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
            'form.award_type_id.id' => 'required|integer|exists:award_types,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
            'form.award_type_id.id' => __('Type'),
        ];
    }

    public function setAwardType(int $awardType): void
    {
        $this->selectedType = $awardType;
        $this->closeCrud();
        $this->resetPage();
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? Award::with('type')->findOrFail($id)
            : null;

        if ($this->model) {
            $this->form = $this->model->toArray();
            $this->form['award_type_id'] = $this->form['type'];
            $this->form['is_foreign'] = $this->form['is_foreign'] ? true : false;
        } else {
            $this->form = [];
        }

        unset($this->form['type']);
        $this->isAdded = true;
        $this->showChild = false;
    }

    public function loadChildComponent(?int $modelId = null): void
    {
        $this->childModel = $modelId;
        $this->showChild = true;
        $this->closeCrud();
    }

    #[On('close-child')]
    public function closeChildComponent(): void
    {
        $this->showChild = false;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = Award::findOrFail($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->form['award_type_id'] = $this->form['award_type_id']['id'];
        $this->form['is_foreign'] = $this->form['is_foreign'] ?? false;

        $this->model
            ? $this->model->update($this->form)
            : Award::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('awardsUpdated');
        $this->closeCrud();
    }

    public function mount()
    {
        $this->selectedType = '-1';
        $this->isAdded = false;
    }


    public function render()
    {
        $award_types = AwardType::all();

        $awards = Award::with('type')
            ->when($this->selectedType > 0, function ($query) {
                $query->where('award_type_id', $this->selectedType);
            })
            ->paginate(20);

        return view('livewire.admin.awards', compact('awards', 'award_types'));
    }
}

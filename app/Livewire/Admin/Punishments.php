<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\SelectListTrait;
use App\Models\Punishment;
use App\Models\PunishmentType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['punishmentsUpdated', 'deleted', 'punishmentTypeUpdated'])]
class Punishments extends Component
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
            'form.id' => 'required|integer|min:1|unique:punishments,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
            'form.punishment_type_id.id' => 'required|integer|exists:punishment_types,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
            'form.punishment_type_id.id' => __('Type'),
        ];
    }

    public function setPunishmentType(int $punishmentType): void
    {
        $this->selectedType = $punishmentType;
        $this->closeCrud();
        $this->resetPage();
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? Punishment::with('type')->findOrFail($id)
            : null;

        if ($this->model) {
            $this->form = $this->model->toArray();
            $this->form['punishment_type_id'] = $this->form['type'];
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
            $this->model = Punishment::findOrFail($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->form['punishment_type_id'] = $this->form['punishment_type_id']['id'];

        $this->model
            ? $this->model->update($this->form)
            : Punishment::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('punishmentsUpdated');
        $this->closeCrud();
    }

    public function mount()
    {
        $this->selectedType = '-1';
        $this->isAdded = false;
    }

    public function render()
    {
        $punishment_types = PunishmentType::all();

        $punishments = Punishment::with('type')
            ->when($this->selectedType > 0, function ($query) {
                $query->where('punishment_type_id', $this->selectedType);
            })
            ->paginate(20);

        return view('livewire.admin.punishments', compact('punishments', 'punishment_types'));
    }
}

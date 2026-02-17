<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Punishment;
use App\Models\PunishmentType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['punishmentsUpdated', 'deleted', 'punishmentTypeUpdated'])]
class Punishments extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use DropdownConstructTrait;
    use WithPagination;

    public string $selectedType;
    public string $searchPunishmentType = '';

    public bool $showChild = false;
    public $childModel = null;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:punishments,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
            'form.punishment_type_id' => 'required|integer|exists:punishment_types,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
            'form.punishment_type_id' => __('Type'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'id' => null,
            'name' => '',
            'punishment_type_id' => null,
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

        $this->form = $this->formDefaults();

        if ($this->model) {
            $this->form['id'] = $this->model->id;
            $this->form['name'] = $this->model->name;
            $this->form['punishment_type_id'] = $this->model->punishment_type_id;
        }

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

    public function punishmentTypeOptions(): array
    {
        $selected = data_get($this->form, 'punishment_type_id');

        $base = PunishmentType::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchPunishmentType'),
            selectedId: $selected,
            limit: 80
        );
    }

    public function render()
    {
        $punishment_types = PunishmentType::all();

        $punishments = Punishment::with('type')
            ->when($this->selectedType > 0, function ($query) {
                $query->where('punishment_type_id', $this->selectedType);
            })
            ->paginate(20);

        $punishments = $this->decoratePunishments($punishments);

        return view('admin::livewire.admin.punishments', compact('punishments', 'punishment_types'));
    }

    protected function decoratePunishments(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (Punishment $punishment) {
                $punishment->type_label = $punishment->type?->name ?? '';

                return $punishment;
            })
        );

        return $paginated;
    }
}

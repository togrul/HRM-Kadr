<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Award;
use App\Models\AwardType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['awardsUpdated', 'deleted', 'awardTypeUpdated'])]
class Awards extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use DropdownConstructTrait;
    use WithPagination;

    public string $selectedType;
    public string $searchAwardType = '';

    public bool $showChild = false;
    public $childModel = null;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:awards,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
            'form.award_type_id' => 'required|integer|exists:award_types,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
            'form.award_type_id' => __('Type'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'id' => null,
            'name' => '',
            'award_type_id' => null,
            'is_foreign' => false,
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

        $this->form = $this->formDefaults();

        if ($this->model) {
            $this->form['id'] = $this->model->id;
            $this->form['name'] = $this->model->name;
            $this->form['award_type_id'] = $this->model->award_type_id;
            $this->form['is_foreign'] = (bool) $this->model->is_foreign;
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
            $this->model = Award::findOrFail($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();

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

    public function awardTypeOptions(): array
    {
        $selected = data_get($this->form, 'award_type_id');

        $base = AwardType::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            $base,
            'name',
            $this->dropdownSearch('searchAwardType'),
            $selected,
            80
        );
    }

    public function render()
    {
        $award_types = AwardType::all();

        $awards = Award::with('type')
            ->when($this->selectedType > 0, function ($query) {
                $query->where('award_type_id', $this->selectedType);
            })
            ->paginate(20);

        $awards = $this->decorateAwards($awards);

        return view('admin::livewire.admin.awards', compact('awards', 'award_types'));
    }

    protected function decorateAwards(LengthAwarePaginator $paginated): LengthAwarePaginator
    {
        $paginated->setCollection(
            $paginated->getCollection()->values()->map(function (Award $award) {
                $award->type_label = $award->type?->name ?? '';

                return $award;
            })
        );

        return $paginated;
    }
}

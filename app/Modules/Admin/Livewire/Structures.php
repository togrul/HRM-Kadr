<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\DropdownConstructTrait;
use App\Models\Structure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['structureUpdated', 'deleted'])]
class Structures extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;
    use DropdownConstructTrait;

    public string $searchParent = '';

    public function rules(): array
    {
        return [
            'form.name' => 'required|string|min:2',
            'form.shortname' => 'required|string|min:2',
            'form.coefficient' => 'required|int',
            'form.code' => 'required|int|min:0',
            'form.level' => 'required|int|min:0',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('Name'),
            'form.shortname' => __('Shortname'),
            'form.coefficient' => __('Coefficient'),
            'form.code' => __('Code'),
            'form.level' => __('Level'),
            'form.parent_id' => __('Parent'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'id' => null,
            'name' => '',
            'shortname' => '',
            'coefficient' => null,
            'code' => null,
            'level' => null,
            'parent_id' => null,
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? Structure::find($id)
            : null;

        $this->form = $this->formDefaults();

        if ($this->model) {
            $this->form['id'] = $this->model->id;
            $this->form['name'] = $this->model->name;
            $this->form['shortname'] = $this->model->shortname;
            $this->form['coefficient'] = $this->model->coefficient;
            $this->form['code'] = $this->model->code;
            $this->form['level'] = $this->model->level;
            $this->form['parent_id'] = $this->model->parent_id;
            $this->form['name_with_parent'] = $this->model->name_with_parent ?? null;
        }

        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = Structure::findOrFail($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->form['parent_id'] = $this->form['parent_id'] ?? null;
        if ($this->model) {
            unset($this->form['name_with_parent']);
            $this->model->update($this->form);
        } else {
            Structure::create($this->form);
        }

        $this->flushStructureCaches();

        $this->callSuccessSwal();

        $this->dispatch('structureUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $structureList = Cache::rememberForever('structures', function () {
            return Structure::withRecursive('subs', false)
                ->whereNull('parent_id')
                ->orderBy('code')
                ->get();
        });

        return view('admin::livewire.admin.structures', compact('structureList'));
    }

    public function parentStructureOptions(): array
    {
        $base = Structure::query()
            ->select('id', 'name as label')
            ->orderBy('name');

        return $this->optionsWithSelected(
            base: $base,
            searchCol: 'name',
            searchTerm: $this->dropdownSearch('searchParent'),
            selectedId: data_get($this->form, 'parent_id'),
            limit: 100
        );
    }

    protected function flushStructureCaches(): void
    {
        foreach ([
            'structures',
            'staff:structures',
            'candidate:structures',
            'businessTrips:structures',
            'order_lookup:main_structures',
        ] as $cacheKey) {
            Cache::forget($cacheKey);
        }
    }
}

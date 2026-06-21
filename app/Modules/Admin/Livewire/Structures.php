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
            'form.coefficient' => 'nullable|int|min:1',
            'form.code' => 'nullable|int|min:1',
            'form.level' => 'nullable|int|min:1',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('admin::references.fields.name'),
            'form.shortname' => __('admin::references.fields.shortname'),
            'form.coefficient' => __('admin::references.fields.coefficient'),
            'form.code' => __('admin::references.fields.code'),
            'form.level' => __('admin::references.fields.level'),
            'form.parent_id' => __('admin::references.fields.parent'),
        ];
    }

    protected function formDefaults(): array
    {
        return [
            'id' => null,
            'name' => '',
            'shortname' => '',
            'coefficient' => 1,
            'code' => 1,
            'level' => 1,
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
        if (! $id) {
            return;
        }

        $this->model = Structure::findOrFail($id);

        // Warn (via the app's confirm modal) — a plain "delete?" when the structure is
        // unused, or an "it is in use, everything linked to it will be deleted" warning
        // when it is referenced anywhere.
        $used = app(\App\Services\Structures\StructureDeletionService::class)->isUsed((int) $id);

        $this->dispatch('confirm-structure-delete', message: $used
            ? __('admin::references.structure_delete.in_use')
            : __('admin::references.structure_delete.confirm'));
    }

    public function performDelete(): void
    {
        if (! $this->model) {
            return;
        }

        // Cascade is fully handled (+ audited + caches flushed via StructureObserver) by
        // the service; we just reset the form and confirm.
        app(\App\Services\Structures\StructureDeletionService::class)->cascadeDelete((int) $this->model->id);

        $this->resetForm();
        $this->dispatch('deleted');
        $this->callSuccessSwal();
    }

    public function store(): void
    {
        $this->form['code'] = blank($this->form['code'] ?? null) ? 1 : (int) $this->form['code'];
        $this->form['level'] = blank($this->form['level'] ?? null) ? 1 : (int) $this->form['level'];
        $this->form['coefficient'] = blank($this->form['coefficient'] ?? null) ? 1 : (int) $this->form['coefficient'];

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
        ] as $cacheKey) {
            Cache::forget($cacheKey);
        }

        \App\Support\PersonnelDropdownCache::forgetStructures();
        \App\Support\OrderLookupCache::bump('main_structures');
        \App\Support\OrderLookupCache::bump('structures');
    }
}

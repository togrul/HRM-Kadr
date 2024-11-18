<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Livewire\Traits\SelectListTrait;
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
    use SelectListTrait;

    public string $searchParent;

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
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? Structure::find($id)
            : null;

        if ($this->model) {
            $this->form = $this->model->toArray();
            $this->form['parent_id'] = $this->form['parent'] ?? [
                'id' => null,
                'name' => '---',
            ];

            unset($this->form['parent']);
        } else {
            $this->form = [];
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

        $this->form['parent_id'] = array_key_exists('parent_id', $this->form) ? $this->form['parent_id']['id'] : null;
        if ($this->model) {
            unset($this->form['name_with_parent']);
            $this->model->update($this->form);
        } else {
            Structure::create($this->form);
        }

        $this->callSuccessSwal();

        $this->dispatch('structureUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $structureList = Cache::rememberForever('structures', function () {
            return Structure::withRecursive('subs')->whereNull('parent_id')->get();
        });

        $allStructures = Structure::query()
            ->when(! empty($this->searchParent), function ($query) {
                $query->where('name', 'LIKE', "%$this->searchParent%");
            })
            ->get();

        return view('livewire.admin.structures', compact('structureList', 'allStructures'));
    }
}

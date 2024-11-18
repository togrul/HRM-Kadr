<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Models\ScientificDegreeAndName;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['scientificDegreeUpdated', 'deleted'])]
class ScientificDegrees extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:scientific_degree_and_names,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? ScientificDegreeAndName::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = ScientificDegreeAndName::find($id);

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
            : ScientificDegreeAndName::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('scientificDegreeUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $scientificDegrees = ScientificDegreeAndName::all();
        return view('livewire.admin.scientific-degrees', compact('scientificDegrees'));
    }
}

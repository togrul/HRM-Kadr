<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Models\EducationDegree;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['educationDegreeUpdated', 'deleted'])]
class EducationDegrees extends Component
{
    use AuthorizesRequests;
    use AdminCrudTrait;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:education_degrees,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.title_az' => 'required|string|min:2',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.title_az' => __('Name'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? EducationDegree::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = EducationDegree::find($id);

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
            : EducationDegree::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('educationDegreeUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $educationDegrees = EducationDegree::all();

        return view('admin::livewire.admin.education-degrees', compact('educationDegrees'));
    }
}

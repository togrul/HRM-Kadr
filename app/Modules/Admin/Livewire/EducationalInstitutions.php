<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Models\EducationalInstitution;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['educationalInstitutionsUpdated', 'deleted'])]
class EducationalInstitutions extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:educational_institutions,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name' => 'required|string|min:2',
            'form.shortname' => 'required|string|min:2',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.id' => __('ID'),
            'form.name' => __('Name'),
            'form.shortname' => __('Shortname'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? EducationalInstitution::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = EducationalInstitution::find($id);

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
            : EducationalInstitution::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('educationalInstitutionsUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $educationalInstitutions = EducationalInstitution::all();
        return view('admin::livewire.admin.educational-institutions', compact('educationalInstitutions'));
    }
}

<?php

namespace App\Modules\Admin\Livewire;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Models\EducationDocumentType;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['documentTypesUpdated', 'deleted'])]
class DocumentTypes extends Component
{
    use AuthorizesRequests;
    use AdminCrudTrait;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:education_document_types,id'.($this->model ? ','.$this->form['id'] : ''),
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
            ? EducationDocumentType::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = EducationDocumentType::find($id);

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
            : EducationDocumentType::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('documentTypesUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $documentTypes = EducationDocumentType::all();
        return view('admin::livewire.admin.document-types', compact('documentTypes'));
    }
}

<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Models\WorkNorm;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['workNormUpdated', 'deleted'])]
class WorkNorms extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:work_norms,id'.($this->model ? ','.$this->form['id'] : ''),
            'form.name_az' => 'required|string|min:2',
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
            ? WorkNorm::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = WorkNorm::find($id);

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
            : WorkNorm::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('workNormUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $workNorms = WorkNorm::all();
        return view('livewire.admin.work-norms', compact('workNorms'));
    }
}

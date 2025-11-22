<?php

namespace App\Modules\Admin\Livewire;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Models\Kinship;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['kinshipUpdated', 'deleted'])]
class Kinships extends Component
{
   use AdminCrudTrait;
   use AuthorizesRequests;
   use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:kinships,id'.($this->model ? ','.$this->form['id'] : ''),
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
            ? Kinship::find($id)
            : null;

        if($this->model)
        {
            $this->form = $this->model->toArray();
            $this->form['is_active'] = (bool)$this->form['is_active'];
        }
        else
        {
            $this->form = [];
        }

        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = Kinship::find($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();
        $this->form['is_active'] = $this->form['is_active'] ?? false;

        $this->model
            ? $this->model->update($this->form)
            : Kinship::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('kinshipUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $kinships = Kinship::all();

        return view('admin::livewire.admin.kinships', compact('kinships'));
    }
}

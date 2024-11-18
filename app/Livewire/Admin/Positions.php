<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\AdminCrudTrait;
use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Models\Position;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['positionUpdated', 'deleted'])]
class Positions extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.id' => 'required|integer|min:1|unique:positions,id'.($this->model ? ','.$this->form['id'] : ''),
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
            ? Position::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = Position::find($id);

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
            : Position::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('positionUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $positions = Position::all();
        return view('livewire.admin.positions', compact('positions'));
    }
}

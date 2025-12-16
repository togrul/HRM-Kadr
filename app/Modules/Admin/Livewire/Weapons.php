<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Admin\Support\Traits\Admin\AdminCrudTrait;
use App\Modules\Admin\Support\Traits\Admin\CallSwalTrait;
use App\Models\Weapon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\Component;

#[On(['weaponUpdated', 'deleted'])]
class Weapons extends Component
{
    use AdminCrudTrait;
    use AuthorizesRequests;
    use CallSwalTrait;

    public function rules(): array
    {
        return [
            'form.name' => 'required|string|min:2',
            'form.serial_number' => 'required|string|min:2',
            'form.capacity' => 'required|int|min:0',
            'form.production_year' => 'required|int|min:0',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('Name'),
            'form.serial_number' => __('Serial number'),
            'form.capacity' => __('Capacity'),
            'form.production_year' => __('Production year'),
        ];
    }

    public function openCrud(?int $id = null): void
    {
        $this->model = $id
            ? Weapon::find($id)
            : null;

        $this->form = $this->model ? $this->model->toArray() : [];
        $this->isAdded = true;
    }

    public function deleteModel(?int $id = null): void
    {
        if ($id) {
            $this->model = Weapon::find($id);

            if ($this->model) {
                $this->callDeletePromptSwal();
            }
        }
    }

    public function store(): void
    {
        $this->validate();
        $this->model
            ? $this->model->update(Arr::except($this->form,'id'))
            : Weapon::create($this->form);

        $this->callSuccessSwal();

        $this->dispatch('weaponUpdated');
        $this->closeCrud();
    }

    public function render()
    {
        $weapons = Weapon::all();
        return view('admin::livewire.admin.weapons', compact('weapons'));
    }
}

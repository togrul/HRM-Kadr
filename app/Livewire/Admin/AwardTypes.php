<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\Admin\CallSwalTrait;
use App\Models\AwardType;
use Livewire\Attributes\On;
use Livewire\Component;

class AwardTypes extends Component
{
    use CallSwalTrait;

    public array $childForm = [];

    public $model;

    public function rules(): array
    {
        return [
            'childForm.id' => 'required|integer|min:1|unique:award_types,id'.($this->model ? ','.$this->childForm['id'] : ''),
            'childForm.name' => 'required|string|min:2',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'childForm.id' => __('ID'),
            'childForm.name' => __('Name'),
        ];
    }

    public function mount()
    {
        if ($this->model) {
            $this->model = AwardType::findOrFail($this->model);
            $this->childForm = $this->model->toArray();
        }
    }

    public function store(): void
    {
        $this->validate();

        $this->model
            ? $this->model->update($this->childForm)
            : AwardType::create($this->childForm);

        $this->callSuccessSwal();

        $this->dispatch('awardTypeUpdated');
        $this->dispatch('close-child');
    }

    public function deleteModel(): void
    {
        if ($this->model) {
            $this->callDeletePromptSwal();
        }
    }

    #[On('goOn-Delete')]
    public function delete(): void
    {
        if ($this->model) {
            $this->model->delete();
            $this->dispatch('awardTypeUpdated');
            $this->dispatch('deleted');
            $this->dispatch('close-child');
        }
    }

    public function render()
    {
        return view('livewire.admin.award-types');
    }
}

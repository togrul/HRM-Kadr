<?php

namespace App\Modules\Admin\Support\Traits\Admin;

use Livewire\Attributes\On;

trait AdminCrudTrait
{
    public bool $isAdded;

    public array $form = [];

    public $model;

    #[On('goOn-Delete')]
    public function delete(): void
    {
        if ($this->model) {
            $this->model->delete();
            $this->dispatch('deleted');
            $this->resetForm();
        }
    }

    #[On('close-crud')]
    public function closeCrud(): void
    {
        $this->isAdded = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->reset(['form', 'model']);
        $this->resetValidation();
    }
}

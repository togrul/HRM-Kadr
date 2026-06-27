<?php

namespace App\Modules\Admin\Support\Traits\Admin;

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

trait AdminCrudTrait
{
    public bool $isAdded;

    public array $form = [];

    public $model;

    #[On('goOn-Delete')]
    public function delete(): void
    {
        // Defense-in-depth: the /admin route group already gates on `can:access-admin`
        // (re-applied to Livewire updates), but re-assert it on the destructive path so
        // reference-data deletion can never run for an unprivileged caller.
        Gate::authorize('access-admin');

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

<?php

namespace App\Modules\Services\Livewire\Components;

use App\Livewire\Traits\ComponentCrud;
use Livewire\Component;

class AddComponent extends Component
{
    use ComponentCrud;

    public function store() : void
    {
        $this->validate();

        \App\Models\Component::create($this->modifyArray($this->component));

        $this->dispatch('candidateAdded', __('Candidate was added successfully!'));
    }
}

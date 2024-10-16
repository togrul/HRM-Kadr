<?php

namespace App\Livewire\Structure;

use Livewire\Component;

class Services extends Component
{
    public $selectedService;

    public function selectService($service)
    {
        $this->selectedService = $service;
        $this->dispatch('selectService', $service);
    }

    public function mount()
    {
        $this->selectedService = request()->get('selectedService');
    }

    public function render()
    {
        return view('livewire.structure.services');
    }
}

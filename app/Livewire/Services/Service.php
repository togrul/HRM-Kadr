<?php

namespace App\Livewire\Services;

use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Service extends Component
{
    #[Url]
    public $selectedService;

    #[On('selectService')]
    public function selectService($service)
    {
        $this->selectedService = $service;
    }

    public function render()
    {
        return view('livewire.services.service');
    }
}

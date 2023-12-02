<?php

namespace App\Livewire\Services;
use Livewire\Attributes\Url;

use Livewire\Component;

class Service extends Component
{
    #[Url]
    public $selectedService;
    protected $listeners = ['selectService'];

    public function selectService($service)
    {
        $this->selectedService = $service;
    }

    public function render()
    {
        return view('livewire.services.service');
    }
}

<?php

namespace App\Modules\Services\Livewire;

use App\Modules\Services\Livewire\Concerns\AuthorizesSettingsAccess;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class Service extends Component
{
    use AuthorizesSettingsAccess;

    #[Url]
    public $selectedService;

    /**
     * Bölmə seçilməyibsə boş ekran əvəzinə ilk bölmə (Ümumi) açılır.
     */
    public function mount(): void
    {
        if (blank($this->selectedService)) {
            $this->selectedService = 'general';
        }
    }

    #[On('selectService')]
    public function selectService($service): void
    {
        $this->selectedService = $service;
    }

    public function render(): View
    {
        return view('services::livewire.services.service');
    }
}

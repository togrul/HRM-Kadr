<?php

namespace App\Modules\Personnel\Livewire;

use App\Modules\Personnel\Application\Services\HomeOverviewService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Home extends Component
{
    /**
     * @return array<string,mixed>
     */
    #[Computed]
    public function payload(): array
    {
        return app(HomeOverviewService::class)->payload(auth()->user());
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.home');
    }
}

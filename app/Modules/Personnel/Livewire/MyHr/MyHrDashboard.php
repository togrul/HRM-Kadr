<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use App\Modules\Personnel\Support\MyHr\MyHrTabs;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrDashboard extends Component
{
    public ?int $personnelId = null;

    public string $activeTab = 'overview';

    public function mount(MyHrAccess $access): void
    {
        $access->authorize(auth()->user());

        $this->personnelId = $access->resolvePersonnelId(auth()->user());

        $requestedTab = (string) request()->query('tab', 'overview');
        if (in_array($requestedTab, MyHrTabs::all(), true)) {
            $this->activeTab = $requestedTab;
        }
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, MyHrTabs::all(), true)) {
            return;
        }

        $this->activeTab = $tab;
    }

    #[Computed]
    public function hasPersonnelLink(): bool
    {
        return $this->personnelId !== null && $this->personnelId > 0;
    }

    /**
     * @return array<int, string>
     */
    public function tabs(): array
    {
        return MyHrTabs::all();
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.dashboard');
    }
}

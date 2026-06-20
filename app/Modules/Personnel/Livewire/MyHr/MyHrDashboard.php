<?php

namespace App\Modules\Personnel\Livewire\MyHr;

use App\Modules\Personnel\Support\MyHr\MyHrAccess;
use App\Modules\Personnel\Support\MyHr\MyHrTabs;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Livewire\Attributes\Computed;
use Livewire\Component;

class MyHrDashboard extends Component
{
    use InteractsWithTabbedWorkspace;

    public ?int $personnelId = null;

    public string $activeTab = 'overview';

    public function mount(MyHrAccess $access): void
    {
        $access->authorize(auth()->user());

        $this->personnelId = $access->resolvePersonnelId(auth()->user());
        $this->bootActiveTabFromRequest();
    }

    public function setActiveTab(string $tab): void
    {
        $this->switchTab($tab);
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

    protected function allowedTabs(): array
    {
        return MyHrTabs::all();
    }

    public function render()
    {
        return view('personnel::livewire.personnel.my-hr.dashboard');
    }
}

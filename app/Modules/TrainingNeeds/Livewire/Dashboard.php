<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsQueries;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Livewire\Component;

class Dashboard extends Component
{
    use InteractsWithTrainingNeedsAccess;
    use InteractsWithTrainingNeedsQueries;
    use InteractsWithTabbedWorkspace;
    use WithRuntimeMemo;

    public string $activeTab = 'overview';

    public int $reportsVersion = 0;

    /**
     * @var array<int, string>
     */
    public array $tabs = ['overview', 'catalogs', 'matrix', 'profiles', 'planning', 'calendar', 'results', 'analytics', 'reports', 'lists'];

    public function mount(): void
    {
        $this->authorizeTrainingNeedsView();
        $this->bootActiveTabFromRequest();
    }

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('training_needs', $this->tabs);
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.dashboard');
    }
}

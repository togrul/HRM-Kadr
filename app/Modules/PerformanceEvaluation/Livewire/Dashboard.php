<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationQueries;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Livewire\Component;

class Dashboard extends Component
{
    use InteractsWithPerformanceEvaluationAccess;
    use InteractsWithPerformanceEvaluationQueries;
    use InteractsWithTabbedWorkspace;

    public string $activeTab = 'overview';

    /**
     * @var array<int, string>
     */
    public array $tabs = ['overview', 'goals', 'succession', 'feedback', 'cycles', 'templates', 'evaluations', 'tests', 'reports', 'lists'];

    public function mount(): void
    {
        $this->authorizePerformanceEvaluationView();
        $this->bootActiveTabFromRequest();
    }

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('performance_evaluation', $this->tabs);
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.dashboard');
    }
}

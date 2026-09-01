<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Livewire\Concerns\WithRuntimeMemo;
use App\Models\TrainingAnnualPlan;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsAccess;
use App\Modules\TrainingNeeds\Livewire\Concerns\InteractsWithTrainingNeedsQueries;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    use InteractsWithTabbedWorkspace;
    use InteractsWithTrainingNeedsAccess;
    use InteractsWithTrainingNeedsQueries;
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

    /**
     * The newest annual plan, for the panel's İLLİK PLAN block. Progress is the share of
     * plan lines HR has approved — the only completion signal a plan actually carries.
     *
     * @return array{title:string, status:string, items:int, approved:int, percent:int}|null
     */
    #[Computed]
    public function annualPlan(): ?array
    {
        $plan = TrainingAnnualPlan::query()
            ->select(['id', 'title', 'plan_year', 'status'])
            ->withCount([
                'items',
                'items as approved_items_count' => fn ($query) => $query->where('review_status', 'approved'),
            ])
            ->orderByDesc('plan_year')
            ->orderByDesc('id')
            ->first();

        if (! $plan) {
            return null;
        }

        $items = (int) $plan->getAttribute('items_count');
        $approved = (int) $plan->getAttribute('approved_items_count');

        return [
            'title' => (string) $plan->title,
            'status' => (string) $plan->status,
            'items' => $items,
            'approved' => $approved,
            'percent' => $items > 0 ? (int) round($approved / $items * 100) : 0,
        ];
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

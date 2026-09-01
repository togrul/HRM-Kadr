<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Models\PerformanceCycle;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationAccess;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\InteractsWithPerformanceEvaluationQueries;
use App\Services\HrPolicies\HrPolicyPackService;
use App\Support\Livewire\InteractsWithTabbedWorkspace;
use Livewire\Attributes\Computed;
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

    /**
     * The running cycle for the panel's AKTİV DÖVR block. Progress is the share of its
     * forms that carry a final score — the only completion signal a form records.
     *
     * @return array{name:string, period:string, forms:int, scored:int, percent:int}|null
     */
    #[Computed]
    public function activeCycle(): ?array
    {
        $cycle = PerformanceCycle::query()
            ->select(['id', 'name', 'status', 'period_start', 'period_end'])
            ->withCount([
                'forms',
                'forms as scored_forms_count' => fn ($query) => $query->whereNotNull('final_score'),
            ])
            ->orderByRaw("case status when 'active' then 0 when 'draft' then 1 else 2 end")
            ->orderByDesc('period_start')
            ->first();

        if (! $cycle) {
            return null;
        }

        $forms = (int) $cycle->getAttribute('forms_count');
        $scored = (int) $cycle->getAttribute('scored_forms_count');

        return [
            'name' => (string) $cycle->name,
            'period' => trim(implode(' – ', array_filter([
                $cycle->period_start?->format('d.m.Y'),
                $cycle->period_end?->format('d.m.Y'),
            ]))),
            'forms' => $forms,
            'scored' => $scored,
            'percent' => $forms > 0 ? (int) round($scored / $forms * 100) : 0,
        ];
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

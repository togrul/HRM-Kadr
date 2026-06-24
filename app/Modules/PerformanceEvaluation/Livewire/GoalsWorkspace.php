<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\PerformanceCycle;
use App\Models\PerformanceGoal;
use App\Modules\PerformanceEvaluation\Application\Services\PerformanceGoalService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

/**
 * Goals / OKR workspace for a performance cycle: cascade-aligned objectives and key
 * results with weighted roll-up progress, inline creation and progress check-ins.
 */
class GoalsWorkspace extends Component
{
    use AuthorizesRequests;
    use SideModalAction;

    public ?int $cycleId = null;


    public array $form = [
        'title' => '',
        'goal_type' => 'objective',
        'personnel_id' => null,
        'personnel_label' => null,
        'parent_goal_id' => null,
        'weight_percent' => 0,
        'unit' => '%',
        'target_value' => null,
        'due_date' => null,
    ];

    public bool $showCycleForm = false;

    public array $cycleForm = [
        'name' => '',
        'period_start' => null,
        'period_end' => null,
    ];

    public ?int $checkinGoalId = null;

    public $checkinValue = null;

    public string $checkinNote = '';

    public function mount(): void
    {
        $this->authorize('show-performance-evaluation');
        $this->cycleId = PerformanceCycle::query()->latest('period_start')->value('id');
    }

    public function updatedCycleId(): void
    {
        $this->resetGoalForm();
        $this->cancelCheckin();
    }

    public function getCyclesProperty()
    {
        return PerformanceCycle::query()
            ->orderByDesc('period_start')
            ->get(['id', 'name', 'status']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTreeProperty(): array
    {
        return $this->cycleId ? app(PerformanceGoalService::class)->tree($this->cycleId) : [];
    }

    public function getSummaryProperty(): array
    {
        return $this->cycleId
            ? app(PerformanceGoalService::class)->summary($this->cycleId)
            : ['total' => 0, 'active' => 0, 'at_risk' => 0, 'done' => 0, 'avg_progress' => 0];
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getParentOptionsProperty(): array
    {
        if (! $this->cycleId) {
            return [];
        }

        return PerformanceGoal::query()
            ->where('performance_cycle_id', $this->cycleId)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (PerformanceGoal $g): array => ['id' => $g->id, 'label' => $g->title])
            ->all();
    }

    /** The reusable PersonnelPicker child reports the chosen owner up via these events. */
    #[\Livewire\Attributes\On('personnel-picked')]
    public function onPersonnelPicked(string $target, int $id, string $label): void
    {
        if ($target !== 'goal-owner') {
            return;
        }
        $this->form['personnel_id'] = $id;
        $this->form['personnel_label'] = $label;
    }

    #[\Livewire\Attributes\On('personnel-cleared')]
    public function onPersonnelCleared(string $target): void
    {
        if ($target !== 'goal-owner') {
            return;
        }
        $this->form['personnel_id'] = null;
        $this->form['personnel_label'] = null;
    }

    public function openGoalForm(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->resetGoalForm();
        $this->openSideMenu('goal-form');
    }

    public function createCycle(): void
    {
        $this->authorize('manage-performance-evaluation');

        $data = $this->validate([
            'cycleForm.name' => ['required', 'string', 'max:255'],
            'cycleForm.period_start' => ['required', 'date'],
            'cycleForm.period_end' => ['required', 'date', 'after_or_equal:cycleForm.period_start'],
        ])['cycleForm'];

        $cycle = PerformanceCycle::create([
            'name' => $data['name'],
            'cycle_type' => 'annual',
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'status' => 'active',
        ]);

        $this->cycleId = $cycle->id;
        $this->showCycleForm = false;
        $this->cycleForm = ['name' => '', 'period_start' => null, 'period_end' => null];
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::goals.messages.cycle_created'));
    }

    public function saveGoal(): void
    {
        $this->authorize('manage-performance-evaluation');

        $validated = $this->validate([
            'cycleId' => ['required', 'integer', 'exists:performance_cycles,id'],
            'form.title' => ['required', 'string', 'max:255'],
            'form.goal_type' => ['required', 'in:objective,kpi,goal'],
            'form.personnel_id' => ['nullable', 'integer', 'exists:personnels,id'],
            'form.parent_goal_id' => ['nullable', 'integer', 'exists:performance_goals,id'],
            'form.weight_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'form.unit' => ['nullable', 'string', 'max:32'],
            'form.target_value' => ['nullable', 'numeric'],
            'form.due_date' => ['nullable', 'date'],
        ]);

        app(PerformanceGoalService::class)->createGoal([
            'performance_cycle_id' => $this->cycleId,
            ...$validated['form'],
        ]);

        $this->resetGoalForm();
        $this->closeSideMenu();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::goals.messages.created'));
    }

    public function startCheckin(int $goalId): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->checkinGoalId = $goalId;
        $this->checkinValue = null;
        $this->checkinNote = '';
    }

    public function saveCheckin(): void
    {
        $this->authorize('manage-performance-evaluation');

        $this->validate([
            'checkinGoalId' => ['required', 'integer', 'exists:performance_goals,id'],
            'checkinValue' => ['required', 'numeric'],
            'checkinNote' => ['nullable', 'string', 'max:500'],
        ]);

        $goal = PerformanceGoal::findOrFail($this->checkinGoalId);
        app(PerformanceGoalService::class)->addCheckin($goal, (float) $this->checkinValue, $this->checkinNote ?: null);

        $this->cancelCheckin();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::goals.messages.checkin_saved'));
    }

    public function cancelCheckin(): void
    {
        $this->checkinGoalId = null;
        $this->checkinValue = null;
        $this->checkinNote = '';
    }

    public function setStatus(int $goalId, string $status): void
    {
        $this->authorize('manage-performance-evaluation');
        if (! in_array($status, PerformanceGoal::STATUSES, true)) {
            return;
        }
        PerformanceGoal::whereKey($goalId)->update(['status' => $status]);
    }

    public function deleteGoal(int $goalId): void
    {
        $this->authorize('manage-performance-evaluation');
        PerformanceGoal::whereKey($goalId)->delete();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::goals.messages.deleted'));
    }

    public function resetGoalForm(): void
    {
        $this->form = [
            'title' => '',
            'goal_type' => 'objective',
            'personnel_id' => null,
            'personnel_label' => null,
            'parent_goal_id' => null,
            'weight_percent' => 0,
            'unit' => '%',
            'target_value' => null,
            'due_date' => null,
        ];
        $this->resetValidation();
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.goals-workspace');
    }
}

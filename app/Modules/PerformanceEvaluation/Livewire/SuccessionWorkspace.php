<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Traits\SideModalAction;
use App\Models\PerformanceCycle;
use App\Models\Position;
use App\Modules\PerformanceEvaluation\Application\Services\SuccessionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Succession workspace: the 9-box grid (performance × potential) and succession plans
 * with ranked successors. Assessments are scoped to the selected performance cycle.
 */
class SuccessionWorkspace extends Component
{
    use AuthorizesRequests;
    use SideModalAction;

    public ?int $cycleId = null;

    public string $section = 'grid'; // grid | plans | pools

    public string $personnelTarget = ''; // which inline picker (candidate:{id} / member:{id}) is open

    public array $assessForm = [
        'personnel_id' => null,
        'personnel_label' => null,
        'performance_level' => 2,
        'potential_level' => 2,
        'note' => null,
    ];

    public array $planForm = [
        'role_title' => '',
        'position_id' => null,
        'incumbent_personnel_id' => null,
        'incumbent_label' => null,
        'risk_of_loss' => 'medium',
        'impact_of_loss' => 'high',
        'notes' => null,
    ];

    public array $poolForm = [
        'name' => '',
        'pool_type' => 'hipo',
        'description' => null,
    ];

    public function mount(): void
    {
        $this->authorize('show-performance-evaluation');
        $this->cycleId = PerformanceCycle::query()->latest('period_start')->value('id');
    }

    public function setSection(string $section): void
    {
        $this->section = in_array($section, ['grid', 'plans', 'pools'], true) ? $section : 'grid';
    }

    public function getPoolsProperty()
    {
        return app(SuccessionService::class)->pools();
    }

    public function getCyclesProperty()
    {
        return PerformanceCycle::query()->orderByDesc('period_start')->get(['id', 'name']);
    }

    public function getNineBoxProperty(): array
    {
        return app(SuccessionService::class)->nineBox($this->cycleId);
    }

    public function getPlansProperty()
    {
        return app(SuccessionService::class)->plans();
    }

    public function getSummaryProperty(): array
    {
        return app(SuccessionService::class)->summary($this->cycleId);
    }

    /**
     * @return array<int, array{id:int,label:string}>
     */
    public function getPositionOptionsProperty(): array
    {
        return Position::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Position $p): array => ['id' => $p->id, 'label' => (string) $p->name])
            ->all();
    }

    /** The reusable PersonnelPicker child reports its choice up via these events. */
    #[On('personnel-picked')]
    public function onPersonnelPicked(string $target, int $id, string $label): void
    {
        $this->authorize('manage-performance-evaluation');

        if (str_starts_with($target, 'candidate:')) {
            $this->addCandidate((int) substr($target, strlen('candidate:')), $id);
            $this->personnelTarget = '';
        } elseif (str_starts_with($target, 'member:')) {
            $this->addMember((int) substr($target, strlen('member:')), $id);
            $this->personnelTarget = '';
        } elseif ($target === 'assess') {
            $this->assessForm['personnel_id'] = $id;
            $this->assessForm['personnel_label'] = $label;
        } elseif ($target === 'incumbent') {
            $this->planForm['incumbent_personnel_id'] = $id;
            $this->planForm['incumbent_label'] = $label;
        }
    }

    #[On('personnel-cleared')]
    public function onPersonnelCleared(string $target): void
    {
        if ($target === 'assess') {
            $this->assessForm['personnel_id'] = null;
            $this->assessForm['personnel_label'] = null;
        } elseif ($target === 'incumbent') {
            $this->planForm['incumbent_personnel_id'] = null;
            $this->planForm['incumbent_label'] = null;
        }
    }

    public function openAssess(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->resetAssessForm();
        $this->openSideMenu('assess');
    }

    public function saveAssessment(): void
    {
        $this->authorize('manage-performance-evaluation');

        $data = $this->validate([
            'assessForm.personnel_id' => ['required', 'integer', 'exists:personnels,id'],
            'assessForm.performance_level' => ['required', 'integer', 'between:1,3'],
            'assessForm.potential_level' => ['required', 'integer', 'between:1,3'],
            'assessForm.note' => ['nullable', 'string', 'max:500'],
        ])['assessForm'];

        app(SuccessionService::class)->upsertAssessment(
            (int) $data['personnel_id'],
            $this->cycleId,
            (int) $data['performance_level'],
            (int) $data['potential_level'],
            $data['note'] ?? null,
        );

        $this->resetAssessForm();
        $this->closeSideMenu();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::succession.messages.assessed'));
    }

    public function removeAssessment(int $assessmentId): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->removeAssessment($assessmentId);
    }

    public function openPlan(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->resetPlanForm();
        $this->openSideMenu('plan');
    }

    public function savePlan(): void
    {
        $this->authorize('manage-performance-evaluation');

        $data = $this->validate([
            'planForm.role_title' => ['required', 'string', 'max:255'],
            'planForm.position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'planForm.incumbent_personnel_id' => ['nullable', 'integer', 'exists:personnels,id'],
            'planForm.risk_of_loss' => ['required', 'in:low,medium,high'],
            'planForm.impact_of_loss' => ['required', 'in:low,medium,high'],
            'planForm.notes' => ['nullable', 'string', 'max:1000'],
        ])['planForm'];

        app(SuccessionService::class)->createPlan($data);

        $this->resetPlanForm();
        $this->closeSideMenu();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::succession.messages.plan_created'));
    }

    public function deletePlan(int $planId): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->deletePlan($planId);
    }

    public function startAddCandidate(int $planId): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->personnelTarget = 'candidate:'.$planId;
    }

    public function addCandidate(int $planId, int $personnelId): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->addCandidate($planId, $personnelId, '1_2_years');
    }

    public function setCandidateReadiness(int $candidateId, string $readiness): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->setCandidateReadiness($candidateId, $readiness);
    }

    public function removeCandidate(int $candidateId): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->removeCandidate($candidateId);
    }

    public function openPool(): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->resetPoolForm();
        $this->openSideMenu('pool');
    }

    public function savePool(): void
    {
        $this->authorize('manage-performance-evaluation');

        $data = $this->validate([
            'poolForm.name' => ['required', 'string', 'max:255'],
            'poolForm.pool_type' => ['required', 'in:hipo,successor,critical_role'],
            'poolForm.description' => ['nullable', 'string', 'max:1000'],
        ])['poolForm'];

        app(SuccessionService::class)->createPool($data);

        $this->resetPoolForm();
        $this->closeSideMenu();
        $this->dispatch('notify', type: 'success', message: __('performance_evaluation::succession.messages.pool_created'));
    }

    public function deletePool(int $poolId): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->deletePool($poolId);
    }

    public function startAddMember(int $poolId): void
    {
        $this->authorize('manage-performance-evaluation');
        $this->personnelTarget = 'member:'.$poolId;
    }

    public function addMember(int $poolId, int $personnelId): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->addMember($poolId, $personnelId);
    }

    public function removeMember(int $memberId): void
    {
        $this->authorize('manage-performance-evaluation');
        app(SuccessionService::class)->removeMember($memberId);
    }

    public function resetPoolForm(): void
    {
        $this->poolForm = ['name' => '', 'pool_type' => 'hipo', 'description' => null];
        $this->personnelTarget = '';
        $this->resetValidation();
    }

    public function resetAssessForm(): void
    {
        $this->assessForm = [
            'personnel_id' => null,
            'personnel_label' => null,
            'performance_level' => 2,
            'potential_level' => 2,
            'note' => null,
        ];
        $this->resetValidation();
    }

    public function resetPlanForm(): void
    {
        $this->planForm = [
            'role_title' => '',
            'position_id' => null,
            'incumbent_personnel_id' => null,
            'incumbent_label' => null,
            'risk_of_loss' => 'medium',
            'impact_of_loss' => 'high',
            'notes' => null,
        ];
        $this->personnelTarget = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.succession-workspace');
    }
}

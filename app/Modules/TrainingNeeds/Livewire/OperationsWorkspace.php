<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingSession;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingCalendarMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingPlanWorkbenchMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingSessionWorkbenchMutations;
use App\Services\HrPolicies\HrPolicyPackService;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;

#[Isolate]
class OperationsWorkspace extends AbstractTrainingNeedsWorkspace
{
    use HandlesTrainingCalendarMutations;
    use HandlesTrainingPlanWorkbenchMutations;
    use HandlesTrainingSessionWorkbenchMutations;

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('training_needs', ['planning', 'calendar']);
    }

    public function confirmRemoveSelectedParticipants(): void
    {
        $validated = $this->validate([
            'selectedSessionId' => 'required|exists:training_sessions,id',
            'bulkParticipantIds' => 'required|array|min:1',
        ], attributes: [
            'selectedSessionId' => __('training_needs::dashboard.fields.session'),
            'bulkParticipantIds' => __('training_needs::dashboard.fields.selected_participants'),
        ]);

        $this->confirmDeletion(
            action: 'removeSelectedParticipants',
            message: __('training_needs::dashboard.confirmations.remove_selected_participants'),
            description: __('training_needs::dashboard.fields.selected_participants').': '.count((array) $validated['bulkParticipantIds']),
            confirmLabel: __('training_needs::dashboard.actions.remove_selected_participants'),
        );
    }

    public function confirmDeletePlan(int $planId): void
    {
        $plan = TrainingAnnualPlan::query()->findOrFail($planId);

        $details = array_filter([
            $plan->title,
            __('training_needs::dashboard.labels.plan_scope', [
                'year' => $plan->plan_year,
                'quarter' => $plan->plan_quarter ? 'Q'.$plan->plan_quarter : __('training_needs::dashboard.labels.all_year'),
            ]),
        ]);

        $this->confirmDeletion(
            action: 'deletePlan',
            parameters: [$planId],
            message: __('training_needs::dashboard.confirmations.delete_plan'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete'),
        );
    }

    public function confirmDeleteSession(int $sessionId): void
    {
        $session = TrainingSession::query()->with(['program:id,title'])->findOrFail($sessionId);

        $details = array_filter([
            $session->title,
            $session->program?->title,
            optional($session->scheduled_start_at)->format('d.m.Y H:i'),
        ]);

        $this->confirmDeletion(
            action: 'deleteSession',
            parameters: [$sessionId],
            message: __('training_needs::dashboard.confirmations.delete_session'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete'),
        );
    }

    #[On('training-needs:calendar-mutated')]
    public function handleCalendarMutated(): void
    {
        $this->refreshRuntimeCaches();
        $this->sessionDetailWorkspaceVersion++;
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.operations-workspace');
    }
}

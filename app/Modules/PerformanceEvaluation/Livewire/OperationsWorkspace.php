<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Models\PerformanceForm;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceEvaluationFlowMutations;
use App\Modules\PerformanceEvaluation\Livewire\Concerns\HandlesPerformanceTestingMutations;
use App\Services\HrPolicies\HrPolicyPackService;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;

#[Isolate]
class OperationsWorkspace extends AbstractPerformanceWorkspace
{
    use HandlesPerformanceEvaluationFlowMutations;
    use HandlesPerformanceTestingMutations;

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('performance_evaluation', ['evaluations', 'tests']);
    }

    public function confirmDeleteEvaluationForm(int $id): void
    {
        $form = PerformanceForm::query()->with(['personnel', 'cycle:id,name'])->findOrFail($id);

        $details = array_filter([
            $form->personnel?->fullname,
            $form->cycle?->name,
        ]);

        $this->confirmDeletion(
            action: 'deleteEvaluationForm',
            parameters: [$id],
            message: __('performance_evaluation::dashboard.confirmations.delete_form'),
            description: implode(' • ', $details),
            confirmLabel: __('performance_evaluation::dashboard.actions.delete'),
        );
    }

    #[On('performance-evaluation:edit-form')]
    public function handleEditEvaluationForm(int $formId): void
    {
        $this->editEvaluationForm($formId);
    }

    #[On('performance-evaluation:confirm-delete-form')]
    public function handleConfirmDeleteEvaluationForm(int $formId): void
    {
        $this->confirmDeleteEvaluationForm($formId);
    }

    public function render()
    {
        return view('performance-evaluation::livewire.performance-evaluation.operations-workspace');
    }
}

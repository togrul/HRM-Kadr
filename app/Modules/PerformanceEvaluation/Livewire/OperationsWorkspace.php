<?php

namespace App\Modules\PerformanceEvaluation\Livewire;

use App\Livewire\Traits\SideModalAction;
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
    use SideModalAction;

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
        $this->openSideMenu('form-assign');
    }

    public function openAssignForm(): void
    {
        $this->authorizePerformanceEvaluationManage();
        $this->cancelEvaluationEdit();
        $this->openSideMenu('form-assign');
    }

    public function saveAssignment(): void
    {
        $this->storeEvaluationForm();
        $this->closeSideMenu();
    }

    /** Opens score entry for one form, from its row in the list or from the toolbar. */
    #[On('performance-evaluation:score-form')]
    public function openScoreForm(?int $formId = null): void
    {
        $this->authorizePerformanceEvaluationManage();
        $this->reset('searchPerformanceForm', 'searchTemplateItem');
        $this->scoreForm = [...$this->scoreDefaults(), 'performance_form_id' => $formId];
        $this->resetValidation();
        $this->openSideMenu('form-score');
    }

    public function saveScore(): void
    {
        $this->storeScore();
        $this->closeSideMenu();
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

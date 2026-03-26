<?php

namespace App\Modules\TrainingNeeds\Livewire;

use App\Models\TrainingDeliveryRecord;
use App\Models\TrainingFeedbackForm;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingDeliveryMutations;
use App\Modules\TrainingNeeds\Livewire\Concerns\HandlesTrainingFeedbackMutations;
use App\Services\HrPolicies\HrPolicyPackService;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

#[Isolate]
class ResultsWorkspace extends AbstractTrainingNeedsWorkspace
{
    use HandlesTrainingDeliveryMutations;
    use HandlesTrainingFeedbackMutations;
    use WithFileUploads;

    protected function allowedTabs(): array
    {
        return app(HrPolicyPackService::class)->workflowTabs('training_needs', ['results']);
    }

    public function confirmDeleteDeliveryCertificate(int $deliveryRecordId): void
    {
        $record = TrainingDeliveryRecord::query()->with(['personnel', 'program'])->findOrFail($deliveryRecordId);

        $details = array_filter([
            $record->personnel?->fullname,
            $record->program?->title,
            $record->certificate_name,
        ]);

        $this->confirmDeletion(
            action: 'deleteDeliveryCertificate',
            parameters: [$deliveryRecordId],
            message: __('training_needs::dashboard.confirmations.delete_certificate'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete_certificate'),
        );
    }

    public function confirmDeleteFeedbackForm(int $feedbackFormId): void
    {
        $form = TrainingFeedbackForm::query()->with(['session:id,title'])->findOrFail($feedbackFormId);

        $details = array_filter([
            $form->title,
            $form->session?->title,
        ]);

        $this->confirmDeletion(
            action: 'deleteFeedbackForm',
            parameters: [$feedbackFormId],
            message: __('training_needs::dashboard.confirmations.delete_feedback_form'),
            description: implode(' • ', $details),
            confirmLabel: __('training_needs::dashboard.actions.delete'),
        );
    }

    #[On('training-needs:edit-feedback-form')]
    public function handleEditFeedbackForm(int $feedbackFormId): void
    {
        $this->editFeedbackForm($feedbackFormId);
    }

    #[On('training-needs:confirm-delete-feedback-form')]
    public function handleConfirmDeleteFeedbackForm(int $feedbackFormId): void
    {
        $this->confirmDeleteFeedbackForm($feedbackFormId);
    }

    #[On('trainingNeedsSaved')]
    public function handleTrainingNeedsSaved(): void
    {
        $this->refreshRuntimeCaches();
        $this->resultsSummaryVersion++;
    }

    public function refreshResultsSummary(): void
    {
        $this->resultsSummaryVersion++;
    }

    public function render()
    {
        return view('training-needs::livewire.training-needs.results-workspace');
    }
}

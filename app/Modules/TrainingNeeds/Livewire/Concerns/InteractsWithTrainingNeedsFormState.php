<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait InteractsWithTrainingNeedsFormState
{
    use HasTrainingNeedsFormDefaults;

    protected function resetForms(): void
    {
        $this->groupForm = $this->groupDefaults();
        $this->levelForm = $this->levelDefaults();
        $this->competencyForm = $this->competencyDefaults();
        $this->programForm = $this->programDefaults();
        $this->programMapForm = $this->programMapDefaults();
        $this->requirementForm = $this->requirementDefaults();

        $this->profileForm = $this->profileDefaults();
        $this->needForm = $this->needDefaults();
        $this->planForm = $this->planDefaults();
        $this->planItemReviewForm = $this->planItemReviewDefaults();
        $this->editingPlanId = null;

        $this->sessionForm = $this->sessionDefaults();
        $this->participantForm = $this->participantDefaults();
        $this->selectedSessionId = null;
        $this->selectedPlanItemId = null;
        $this->selectedSessionProposalPlanItemId = null;
        $this->editingSessionId = null;
        $this->bulkParticipantIds = [];
        $this->bulkProposalPlanItemIds = [];
        $this->bulkAttendanceStatus = 'confirmed';
        $this->searchSelectedParticipant = '';
        $this->selectedParticipantAttendanceFilter = 'all';
        $this->selectedParticipantSourceFilter = 'all';

        $this->feedbackForm = $this->feedbackFormDefaults();
        $this->feedbackResponseForm = $this->feedbackResponseDefaults();
        $this->deliveryDocumentForm = $this->deliveryDocumentDefaults();
        $this->editingFeedbackFormId = null;

        $this->refreshRuntimeCaches();
    }
}

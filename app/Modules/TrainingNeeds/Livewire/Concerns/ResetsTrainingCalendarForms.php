<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait ResetsTrainingCalendarForms
{
    protected function resetTrainingCalendarForms(): void
    {
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
    }
}

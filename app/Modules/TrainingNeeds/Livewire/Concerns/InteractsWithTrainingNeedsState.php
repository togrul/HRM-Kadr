<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

use App\Models\TrainingAnnualPlan;
use App\Models\TrainingPlanItem;

trait InteractsWithTrainingNeedsState
{
    public function selectSessionDetail(int $sessionId): void
    {
        $this->selectedSessionId = $sessionId;
        $this->participantForm['training_session_id'] = $sessionId;
        $this->bulkParticipantIds = [];
    }

    public function selectDeliveryRecord(int $deliveryRecordId): void
    {
        $this->deliveryDocumentForm['training_delivery_record_id'] = $deliveryRecordId;
    }

    public function selectPlanItemForReview(int $planItemId): void
    {
        $item = TrainingPlanItem::query()->findOrFail($planItemId);

        $this->selectedPlanItemId = $item->id;
        $this->planItemReviewForm = [
            'participant_count' => $item->participant_count,
            'estimated_budget' => $item->estimated_budget,
            'priority' => $item->priority,
            'review_note' => $item->review_note,
        ];
    }

    public function cancelPlanItemReview(): void
    {
        $this->selectedPlanItemId = null;
        $this->planItemReviewForm = $this->planItemReviewDefaults();
    }

    protected function refreshRuntimeCaches(): void
    {
        $this->memo = [];
        $this->resetDropdownRuntimeCache();
    }
}

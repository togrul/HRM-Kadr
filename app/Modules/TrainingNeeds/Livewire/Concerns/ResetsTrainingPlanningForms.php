<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait ResetsTrainingPlanningForms
{
    protected function resetTrainingPlanningForms(): void
    {
        $this->profileForm = $this->profileDefaults();
        $this->needForm = $this->needDefaults();
        $this->planForm = $this->planDefaults();
        $this->planItemReviewForm = $this->planItemReviewDefaults();
        $this->editingPlanId = null;
    }
}

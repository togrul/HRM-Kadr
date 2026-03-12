<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait ResetsTrainingResultForms
{
    protected function resetTrainingResultForms(): void
    {
        $this->feedbackForm = $this->feedbackFormDefaults();
        $this->feedbackResponseForm = $this->feedbackResponseDefaults();
        $this->deliveryDocumentForm = $this->deliveryDocumentDefaults();
    }
}

<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

trait ResetsPerformanceTestingForms
{
    protected function resetPerformanceTestingForms(): void
    {
        $this->bankForm = $this->bankDefaults();
        $this->questionForm = $this->questionDefaults();
        $this->sessionForm = $this->sessionDefaults();
        $this->attemptAnswerForm = $this->attemptAnswerDefaults();
        $this->attemptSubmitForm = $this->attemptSubmitDefaults();
        $this->reviewForm = $this->reviewDefaults();
    }
}

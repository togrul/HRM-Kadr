<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

trait ResetsPerformanceEvaluationFlowForms
{
    protected function resetPerformanceEvaluationFlowForms(): void
    {
        $this->evaluationForm = $this->evaluationDefaults();
        $this->scoreForm = $this->scoreDefaults();
    }
}

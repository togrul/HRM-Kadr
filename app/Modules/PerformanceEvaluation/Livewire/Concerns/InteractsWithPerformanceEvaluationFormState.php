<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

trait InteractsWithPerformanceEvaluationFormState
{
    use HasPerformanceEvaluationFormDefaults;
    use ResetsPerformanceEvaluationFlowForms;
    use ResetsPerformanceFoundationForms;
    use ResetsPerformanceTestingForms;

    protected function resetForms(): void
    {
        $this->resetPerformanceFoundationForms();
        $this->resetPerformanceEvaluationFlowForms();
        $this->resetPerformanceTestingForms();
    }
}

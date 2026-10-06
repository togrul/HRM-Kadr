<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

trait InteractsWithPerformanceEvaluationFormState
{
    use HasPerformanceEvaluationFormDefaults;

    protected function resetForms(): void
    {
        $this->cycleForm = $this->cycleDefaults();
        $this->templateForm = $this->templateDefaults();
        $this->sectionForm = $this->sectionDefaults();
        $this->itemForm = $this->itemDefaults();

        $this->evaluationForm = $this->evaluationDefaults();
        $this->scoreForm = $this->scoreDefaults();

        $this->bankForm = $this->bankDefaults();
        $this->questionForm = $this->questionDefaults();
        $this->sessionForm = $this->sessionDefaults();
        $this->attemptAnswerForm = $this->attemptAnswerDefaults();
        $this->attemptSubmitForm = $this->attemptSubmitDefaults();
        $this->reviewForm = $this->reviewDefaults();
        $this->testQuestionImportForm = $this->testQuestionImportDefaults();
    }
}

<?php

namespace App\Modules\PerformanceEvaluation\Livewire\Concerns;

trait ResetsPerformanceFoundationForms
{
    protected function resetPerformanceFoundationForms(): void
    {
        $this->cycleForm = $this->cycleDefaults();
        $this->templateForm = $this->templateDefaults();
        $this->sectionForm = $this->sectionDefaults();
        $this->itemForm = $this->itemDefaults();
    }
}

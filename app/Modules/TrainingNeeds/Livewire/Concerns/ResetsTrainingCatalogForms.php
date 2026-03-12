<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait ResetsTrainingCatalogForms
{
    protected function resetTrainingCatalogForms(): void
    {
        $this->groupForm = $this->groupDefaults();
        $this->levelForm = $this->levelDefaults();
        $this->competencyForm = $this->competencyDefaults();
        $this->programForm = $this->programDefaults();
        $this->programMapForm = $this->programMapDefaults();
        $this->requirementForm = $this->requirementDefaults();
    }
}

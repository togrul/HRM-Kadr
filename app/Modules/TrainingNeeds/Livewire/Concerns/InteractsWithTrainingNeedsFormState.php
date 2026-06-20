<?php

namespace App\Modules\TrainingNeeds\Livewire\Concerns;

trait InteractsWithTrainingNeedsFormState
{
    use HasTrainingNeedsFormDefaults;
    use ResetsTrainingCalendarForms;
    use ResetsTrainingCatalogForms;
    use ResetsTrainingPlanningForms;
    use ResetsTrainingResultForms;

    protected function resetForms(): void
    {
        $this->resetTrainingCatalogForms();
        $this->resetTrainingPlanningForms();
        $this->resetTrainingCalendarForms();
        $this->resetTrainingResultForms();
        $this->refreshRuntimeCaches();
    }
}

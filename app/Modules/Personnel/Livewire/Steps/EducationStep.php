<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Livewire\Forms\Personnel\EducationForm;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;

#[Isolate]
class EducationStep extends AbstractPersonnelWizardStep
{
    public EducationForm $educationForm;

    protected function hydrateFromState(array $state): void
    {
        $this->educationForm->education = array_replace($this->educationForm->education ?? [], $state['education'] ?? []);
        $this->educationForm->extraEducation = array_replace($this->educationForm->extraEducation ?? [], $state['extraEducation'] ?? []);
        $this->educationForm->extraEducationList = $state['extraEducationList'] ?? [];
        $this->educationForm->hasExtraEducation = (bool) ($state['hasExtraEducation'] ?? false);
        $this->recalculateEducationDurations();
    }

    protected function stepPayloadForParent(): array
    {
        return [
            'education' => $this->educationForm->education ?? [],
            'extraEducation' => $this->educationForm->extraEducation ?? [],
            'extraEducationList' => $this->educationForm->extraEducationList ?? [],
            'hasExtraEducation' => (bool) ($this->educationForm->hasExtraEducation ?? false),
        ];
    }

    protected function stepNumber(): int
    {
        return 3;
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.steps.education-step', [
            'stepSearchModels' => $this->activeStepSearchModels(),
            'stepSearchPlaceholders' => $this->activeStepSearchPlaceholders(),
        ]);
    }
}

<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Livewire\Forms\Personnel\LaborActivityForm;
use Livewire\Attributes\Isolate;

#[Isolate]
class LaborActivityStep extends AbstractPersonnelWizardStep
{
    public LaborActivityForm $laborActivityForm;

    protected function hydrateFromState(array $state): void
    {
        $this->laborActivityForm->fillFromArrays(
            $state['laborActivity'] ?? [],
            $state['laborActivityList'] ?? [],
            $state['rank'] ?? [],
            $state['rankList'] ?? []
        );
        $this->isSpecialService = (bool) ($state['isSpecialService'] ?? false);
        $this->calculateSeniority();
    }

    protected function stepPayloadForParent(): array
    {
        return [
            'laborActivity' => $this->laborActivityForm->laborActivity ?? [],
            'laborActivityList' => $this->laborActivityForm->laborActivityList ?? [],
            'rank' => $this->laborActivityForm->rank ?? [],
            'rankList' => $this->laborActivityForm->rankList ?? [],
            'isSpecialService' => (bool) $this->isSpecialService,
        ];
    }

    protected function stepNumber(): int
    {
        return 4;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.steps.labor-activity-step', [
            'stepSearchModels' => $this->activeStepSearchModels(),
            'stepSearchPlaceholders' => $this->activeStepSearchPlaceholders(),
        ]);
    }
}

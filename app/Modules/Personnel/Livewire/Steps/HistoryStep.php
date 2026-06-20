<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Livewire\Forms\Personnel\ServiceHistoryForm;
use Livewire\Attributes\Isolate;

#[Isolate]
class HistoryStep extends AbstractPersonnelWizardStep
{
    public ServiceHistoryForm $historyForm;

    protected function hydrateFromState(array $state): void
    {
        $this->historyForm->fillFromArrays(
            $state['military'] ?? [],
            $state['militaryList'] ?? [],
            $state['injury'] ?? [],
            $state['injuryList'] ?? [],
            $state['captivity'] ?? [],
            $state['captivityList'] ?? []
        );
        $this->personnelExtra = array_replace(
            $this->personnelExtra,
            $state['personnelExtra'] ?? []
        );
    }

    protected function stepPayloadForParent(): array
    {
        return [
            'military' => $this->historyForm->military ?? [],
            'militaryList' => $this->historyForm->militaryList ?? [],
            'injury' => $this->historyForm->injury ?? [],
            'injuryList' => $this->historyForm->injuryList ?? [],
            'captivity' => $this->historyForm->captivity ?? [],
            'captivityList' => $this->historyForm->captivityList ?? [],
            'personnelExtra' => $this->personnelExtra,
        ];
    }

    protected function stepNumber(): int
    {
        return 5;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.steps.history-step', [
            'stepSearchModels' => $this->activeStepSearchModels(),
            'stepSearchPlaceholders' => $this->activeStepSearchPlaceholders(),
        ]);
    }
}

<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Livewire\Forms\Personnel\MiscellaneousForm;
use Livewire\Attributes\Isolate;

#[Isolate]
class MiscStep extends AbstractPersonnelWizardStep
{
    public MiscellaneousForm $miscForm;

    protected function hydrateFromState(array $state): void
    {
        $this->miscForm->fillFromArrays(
            $state['language'] ?? [],
            $state['languageList'] ?? [],
            $state['event'] ?? [],
            $state['eventList'] ?? [],
            $state['degree'] ?? [],
            $state['degreeList'] ?? [],
            $state['election'] ?? [],
            $state['electionList'] ?? [],
            (bool) ($state['hasElectedElectorals'] ?? false)
        );
    }

    protected function stepPayloadForParent(): array
    {
        return [
            'language' => $this->miscForm->language ?? [],
            'languageList' => $this->miscForm->languageList ?? [],
            'event' => $this->miscForm->event ?? [],
            'eventList' => $this->miscForm->eventList ?? [],
            'degree' => $this->miscForm->degree ?? [],
            'degreeList' => $this->miscForm->degreeList ?? [],
            'election' => $this->miscForm->election ?? [],
            'electionList' => $this->miscForm->electionList ?? [],
            'hasElectedElectorals' => (bool) ($this->miscForm->hasElectedElectorals ?? false),
        ];
    }

    protected function stepNumber(): int
    {
        return 8;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.steps.misc-step', [
            'stepSearchModels' => $this->activeStepSearchModels(),
            'stepSearchPlaceholders' => $this->activeStepSearchPlaceholders(),
        ]);
    }
}

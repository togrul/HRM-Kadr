<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Livewire\Forms\Personnel\KinshipForm;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Isolate;

#[Isolate]
class KinshipStep extends AbstractPersonnelWizardStep
{
    public KinshipForm $kinshipForm;

    protected function hydrateFromState(array $state): void
    {
        $this->kinshipForm->fillFromArrays(
            $state['kinship'] ?? [],
            $state['kinshipList'] ?? [],
            $state['editingKinshipKey'] ?? null
        );
    }

    protected function stepPayloadForParent(): array
    {
        return [
            'kinship' => $this->kinshipForm->kinship ?? [],
            'kinshipList' => $this->kinshipForm->kinshipList ?? [],
            'editingKinshipKey' => $this->kinshipForm->editingKinshipKey,
        ];
    }

    protected function stepNumber(): int
    {
        return 7;
    }

    public function render(): View
    {
        return view('personnel::livewire.personnel.steps.kinship-step', [
            'stepSearchModels' => $this->activeStepSearchModels(),
            'stepSearchPlaceholders' => $this->activeStepSearchPlaceholders(),
        ]);
    }
}

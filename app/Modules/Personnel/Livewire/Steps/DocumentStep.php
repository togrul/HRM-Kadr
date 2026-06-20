<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Livewire\Forms\Personnel\DocumentForm;
use Livewire\Attributes\Isolate;

#[Isolate]
class DocumentStep extends AbstractPersonnelWizardStep
{
    public DocumentForm $documentForm;

    protected function hydrateFromState(array $state): void
    {
        $this->documentForm->fillFromArrays(
            $state['document'] ?? [],
            $state['serviceCards'] ?? [],
            $state['serviceCardsList'] ?? [],
            $state['passports'] ?? [],
            $state['passportsList'] ?? []
        );
    }

    protected function stepPayloadForParent(): array
    {
        return [
            'document' => $this->documentForm->document ?? [],
            'serviceCards' => $this->documentForm->serviceCards ?? [],
            'serviceCardsList' => $this->documentForm->serviceCardsList ?? [],
            'passports' => $this->documentForm->passports ?? [],
            'passportsList' => $this->documentForm->passportsList ?? [],
        ];
    }

    protected function stepNumber(): int
    {
        return 2;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.steps.document-step', [
            'stepSearchModels' => $this->activeStepSearchModels(),
            'stepSearchPlaceholders' => $this->activeStepSearchPlaceholders(),
        ]);
    }
}

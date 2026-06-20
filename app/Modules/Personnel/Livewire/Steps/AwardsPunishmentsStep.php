<?php

namespace App\Modules\Personnel\Livewire\Steps;

use App\Livewire\Forms\Personnel\AwardsPunishmentsForm;
use Livewire\Attributes\Isolate;

#[Isolate]
class AwardsPunishmentsStep extends AbstractPersonnelWizardStep
{
    public AwardsPunishmentsForm $awardsPunishmentsForm;

    protected function hydrateFromState(array $state): void
    {
        $this->awardsPunishmentsForm->fillFromArrays(
            $state['award'] ?? [],
            $state['awardList'] ?? [],
            $state['punishment'] ?? [],
            $state['punishmentList'] ?? []
        );
        $this->personnelExtra = array_replace(
            $this->personnelExtra,
            $state['personnelExtra'] ?? []
        );
    }

    protected function stepPayloadForParent(): array
    {
        return [
            'award' => $this->awardsPunishmentsForm->award ?? [],
            'awardList' => $this->awardsPunishmentsForm->awardList ?? [],
            'punishment' => $this->awardsPunishmentsForm->punishment ?? [],
            'punishmentList' => $this->awardsPunishmentsForm->punishmentList ?? [],
            'personnelExtra' => $this->personnelExtra,
        ];
    }

    protected function stepNumber(): int
    {
        return 6;
    }

    public function render()
    {
        return view('personnel::livewire.personnel.steps.awards-punishments-step', [
            'stepSearchModels' => $this->activeStepSearchModels(),
            'stepSearchPlaceholders' => $this->activeStepSearchPlaceholders(),
        ]);
    }
}

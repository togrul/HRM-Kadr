<?php

namespace App\Modules\Personnel\Support\Traits\Information;

use App\Models\PersonnelPensionCard;

trait PensionCardTrait {
    public array $pensionCards = [];

    protected function getPensionCardsRules(): array
    {
        return [
            'pensionCards.card_no' => 'required|min:1',
            'pensionCards.given_date' => 'required|date',
            'pensionCards.expiry_date' => 'required|date',
        ];
    }

    public function addPensionCard(): void
    {
        $this->validate($this->validationRules()['pensionCard']);

        $modelInstance = new PersonnelPensionCard;
        $pensionCardData = $this->modifyArray($this->pensionCards, $modelInstance->dateList());

        $this->personnelModelData->pensionCards()->create($pensionCardData);

        $this->dispatch('contractAdded', __('Pension card was added successfully!'));
        $this->reset('pensionCards');
    }

    public function forceDeletePensionCard(PersonnelPensionCard $pensionCard): void
    {
        $pensionCard->delete();
        $this->dispatch('contractAdded', __('Pension card was deleted successfully!'));
    }
}

<?php

namespace App\Modules\Personnel\Support\Traits\Information;

use App\Models\PersonnelDisposal;

trait DisposalTrait {
    public array $disposals = [];

    public int $selectedDisposal;

    protected function getDisposalRules(): array
    {
        return [
            'disposals.disposal_date' => 'required|date',
        ];
    }

    public function addDisposal(): void
    {
        $this->validate($this->validationRules()['disposal']);

        $modelInstance = new PersonnelDisposal;
        $disposalData = $this->modifyArray($this->disposals, $modelInstance->dateList());
        $this->personnelModelData->disposals()->updateOrCreate(
            ['disposal_date' => $disposalData['disposal_date']],
            $disposalData
        );

        $this->dispatch('contractAdded', __('Disposal was added successfully!'));
        $this->dispatchModalCloseEvent();
        $this->reset('disposals');
    }

    public function updateDisposal(PersonnelDisposal $disposalModel): void
    {
        $this->resetValidation();
        $this->selectedDisposal = $disposalModel->id;
        $this->disposals = $disposalModel->only(['disposal_date', 'disposal_end_date', 'disposal_reason']);

        foreach (['disposal_date', 'disposal_end_date'] as $dateField) {
            if (! empty($this->disposals[$dateField])) {
                $this->disposals[$dateField] = $this->formatDate($this->disposals[$dateField]);
            }
        }
    }

    public function forceDeleteDisposal(PersonnelDisposal $disposalModel): void
    {
        $disposalModel->delete();
        $this->dispatch('contractAdded', __('Disposal was deleted successfully!'));
        $this->dispatchModalCloseEvent();
    }
}

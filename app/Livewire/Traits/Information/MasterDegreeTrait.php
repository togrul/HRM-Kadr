<?php

namespace App\Livewire\Traits\Information;

use App\Models\PersonnelMasterDegree;

trait MasterDegreeTrait {
    public array $masterDegrees = [];
    public int $selectedDegree;

    protected function getMasterDegreesRules(): array
    {
        return [
            'masterDegrees.degree' => 'required',
            'masterDegrees.given_date' => 'required|date',
            'masterDegrees.approved_date' => 'required|date',
        ];
    }

    public function addMasterDegree(): void
    {
        $this->validate($this->validationRules()['masterDegree']);

        $modelInstance = new PersonnelMasterDegree;
        $masterDegreeData = $this->modifyArray($this->masterDegrees, $modelInstance->dateList());
        $this->personnelModelData->masterDegrees()->updateOrCreate(
            ['given_date' => $masterDegreeData['given_date']],
            $masterDegreeData,
        );

        $this->dispatch('contractAdded', __('Master degree was added successfully!'));
        $this->reset('masterDegrees', 'selectedDegree');
    }

    public function updateMasterDegree(PersonnelMasterDegree $masterDegree): void
    {
        $this->selectedDegree = $masterDegree->id;
        $this->masterDegrees = $masterDegree->only(['degree', 'given_date', 'approved_date', 'redemption_date']);

        foreach (['given_date', 'approved_date', 'redemption_date'] as $dateField) {
            if (! empty($this->masterDegrees[$dateField])) {
                $this->masterDegrees[$dateField] = $this->formatDate($this->masterDegrees[$dateField]);
            }
        }
    }

    public function forceDeleteMasterDegree(PersonnelMasterDegree $masterDegree): void
    {
        $masterDegree->delete();
        $this->dispatch('contractAdded', __('Master degree was deleted successfully!'));
    }
}

<?php

namespace App\Modules\Personnel\Support\Traits\Information;

use App\Models\PersonnelContract;

trait ContractTrait {
    public array $contracts = [
        'rank_id' => null,
    ];

    protected function getContractRules(): array
    {
        return [
            'contracts.rank_id' => 'required|int|exists:ranks,id',
            'contracts.contract_date' => 'required|date',
            'contracts.contract_refresh_date' => 'required|date',
            'contracts.contract_duration' => 'required|int',
            'contracts.contract_ends_at' => 'required|date',
        ];
    }

    public function addContract(): void
    {
        $this->validate($this->validationRules()['contract']);

        $modelInstance = new PersonnelContract;
        $contractData = $this->modifyArray($this->contracts, $modelInstance->dateList());
        $this->personnelModelData->contracts()->create($contractData);

        $this->dispatch('contractAdded', __('Contract was added successfully!'));
        $this->dispatchModalCloseEvent();
        $this->reset('contracts');
    }

    public function forceDeleteContract(PersonnelContract $contractModel): void
    {
        $contractModel->delete();
        $this->dispatch('contractAdded', __('Contract was deleted successfully!'));
        $this->dispatchModalCloseEvent();
    }
}

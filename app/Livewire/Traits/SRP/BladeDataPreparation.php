<?php

namespace App\Livewire\Traits\SRP;

use App\Models\Personnel;

trait BladeDataPreparation
{
    private function prepareDefaultBladeData(): array
    {
        $_attrData = $this->components;
        $_personnel_ids_list = collect($_attrData)->pluck('personnel_id.id')->toArray();
        $_personnel_ids = Personnel::find($_personnel_ids_list)->pluck('tabel_no')->toArray();

        $_component_ids = collect($_attrData)->pluck('component_id.id')->toArray();

        return $this->returnResponse($_attrData, $_personnel_ids, $_component_ids);
    }

    private function prepareBusinessTripBladeData(): array
    {
        $_attrData = $this->fillPersonnelsToComponents($this->selectedBlade);
        $_personnel_ids = $this->selected_personnel_list['personnels'];
        $_component_ids = collect($_attrData)
            ->unique('row')
            ->values()
            ->pluck('component_id.id')
            ->all();

        return $this->returnResponse($_attrData, $_personnel_ids, $_component_ids);
    }

    private function prepareVacationBladeData(): array
    {
        $_attrData = $this->fillPersonnelsToComponents($this->selectedBlade);
        $_personnel_ids = $this->selected_personnel_list['personnels'];
        $_component_ids = collect($_attrData)
            ->unique('days')
            ->values()
            ->pluck('component_id.id')
            ->all();

        return $this->returnResponse($_attrData, $_personnel_ids, $_component_ids);
    }

    private function returnResponse($_attrData, $_personnel_ids, $_component_ids): array
    {
        return [
            'attributes' => $this->modifyComponentList($_attrData),
            'personnel_ids' => $_personnel_ids,
            'component_ids' => $_component_ids,
        ];
    }
}

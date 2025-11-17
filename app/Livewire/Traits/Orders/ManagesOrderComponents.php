<?php

namespace App\Livewire\Traits\Orders;

use App\Models\Order;
use App\Models\PersonnelBusinessTrip;

trait ManagesOrderComponents
{
    public array $components = [];
    public array $selectedComponents = [];
    public array $selected_personnel_list = ['personnels' => []];
    public array $coded_list = [];
    public int $componentRows = 0;

    protected function resetComponentState(): void
    {
        $this->components = [];
        $this->componentRows = 1;
    }

    protected function fillEmptyComponent(): void
    {
        $list = match ($this->selectedBlade) {
            Order::BLADE_VACATION => ['component_id'],
            Order::BLADE_DEFAULT => ['rank_id', 'component_id', 'personnel_id', 'structure_main_id', 'structure_id', 'position_id'],
            Order::BLADE_BUSINESS_TRIP => ['component_id'],
            default => [],
        };

        $this->generateFilledArray($list);

        $this->coded_list[] = false;
    }

    protected function generateFilledArray(array $array): void
    {
        $data = [];
        foreach ($array as $arr) {
            if ($arr === 'component_id') {
                $data[$arr] = null;
                continue;
            }

            if ($this->isDropdownField($arr)) {
                $data[$arr] = null;
                continue;
            }

            $data[$arr] = '';
        }

        $this->components[] = $data;

        if (
            in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP]) &&
            ($this->componentRows > 0 && ! empty($this->components[0]['component_id']))
        ) {
            $this->components[$this->componentRows]['component_id'] = $this->components[0]['component_id'];
        }
    }

    public function addRow(): void
    {
        $this->fillEmptyComponent();

        $this->componentRows++;
    }

    public function deleteRow(): void
    {
        if ($this->componentRows > 1) {
            unset($this->components[$this->componentRows - 1]);
            unset($this->selectedComponents[$this->componentRows - 1]);
            $this->resetValidation();
            $this->componentRows--;
        }
    }

    protected function modifyCodedList(): void
    {
        $this->coded_list = array_map(function ($value) {
            $id = is_array($value) ? ($value['id'] ?? null) : $value;
            return (int) $id === 1;
        }, collect($this->components)->pluck('structure_main_id')->toArray());
    }
}

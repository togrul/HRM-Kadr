<?php

namespace App\Modules\Orders\Support\Traits\Orders;

use App\Models\Order;

trait HandlesComponentRows
{
    public array $componentForms = [];
    public array $selectedComponents = [];
    public array $coded_list = [];
    public int $componentRows = 0;

    protected function resetComponentState(): void
    {
        $this->componentForms = [];
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

    protected function generateFilledArray(array $fields): void
    {
        $row = [];
        foreach ($fields as $field) {
            if ($field === 'component_id' || $this->isDropdownField($field)) {
                $row[$field] = null;
                continue;
            }

            $row[$field] = '';
        }

        $this->componentForms[] = $row;

        if (
            in_array($this->selectedBlade, [Order::BLADE_VACATION, Order::BLADE_BUSINESS_TRIP], true)
            && ($this->componentRows > 0 && ! empty($this->componentForms[0]['component_id']))
        ) {
            $this->componentForms[$this->componentRows]['component_id'] = $this->componentForms[0]['component_id'];
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
            unset($this->componentForms[$this->componentRows - 1]);
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
        }, collect($this->componentForms)->pluck('structure_main_id')->toArray());
    }
}

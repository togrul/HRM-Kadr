<?php

namespace App\Modules\Admin\Livewire;

use App\Models\RankCategory;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['rankCategoryUpdated', 'deleted'])]
class RankCategories extends ReferenceCrudComponent
{
    protected string $modelClass = RankCategory::class;

    protected string $savedEvent = 'rankCategoryUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_category');
    }

    protected function fields(): array
    {
        return [
            'id' => $this->idField(),
            'name' => ['label' => __('admin::references.fields.name'), 'rules' => 'required|string|min:2'],
            'vacation_days_count' => ['label' => __('admin::references.fields.vacation_days_count'), 'type' => 'number', 'rules' => 'required|integer|min:0'],
            'contract_duration' => ['label' => __('admin::references.fields.contract_duration'), 'type' => 'number', 'rules' => 'required|integer|min:0'],
            'next_contract_duration' => ['label' => __('admin::references.fields.next_contract_duration'), 'type' => 'number', 'rules' => 'nullable|integer|min:0'],
            'vacation_days_per_month' => ['label' => __('admin::references.fields.vacation_days_per_month'), 'type' => 'number', 'rules' => 'required|numeric|min:0'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => __('admin::references.fields.id'), 'attr' => 'id'],
            ['label' => __('admin::references.fields.name'), 'attr' => 'name', 'class' => 'text-sm font-medium text-blue-500 bg-slate-100 rounded-sm px-3 py-1'],
            ['label' => __('admin::references.fields.vacation_days'), 'attr' => 'vacation_days_count', 'unit' => __('admin::references.units.day')],
            ['label' => __('admin::references.fields.contract_duration'), 'attr' => 'contract_duration', 'unit' => __('admin::references.units.month')],
        ];
    }
}

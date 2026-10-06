<?php

namespace App\Modules\Admin\Livewire;

use App\Models\Weapon;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

/**
 * Auto-increment key: no `id` input, and the id is never mass-assigned.
 */
#[On(['weaponUpdated', 'deleted'])]
class Weapons extends ReferenceCrudComponent
{
    protected string $modelClass = Weapon::class;

    protected string $savedEvent = 'weaponUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_weapon');
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => __('admin::references.fields.name'), 'rules' => 'required|string|min:2'],
            'serial_number' => ['label' => __('admin::references.fields.serial_number'), 'rules' => 'required|string|min:2'],
            'capacity' => ['label' => __('admin::references.fields.capacity'), 'type' => 'number', 'rules' => 'required|int|min:0'],
            'production_year' => ['label' => __('admin::references.fields.production_year'), 'type' => 'number', 'rules' => 'required|int|min:0'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => __('admin::references.fields.id'), 'attr' => 'id'],
            ['label' => __('admin::references.fields.name'), 'attr' => 'name', 'class' => 'text-xs font-medium flex justify-center items-center px-1 py-1 rounded-md border border-gray-300 bg-gray-50 text-gray-700'],
            ['label' => __('admin::references.fields.serial_number'), 'attr' => 'serial_number'],
            ['label' => __('admin::references.fields.capacity'), 'attr' => 'capacity'],
            ['label' => __('admin::references.fields.production_year'), 'attr' => 'production_year'],
        ];
    }
}

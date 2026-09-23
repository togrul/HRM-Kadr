<?php

namespace App\Modules\Admin\Livewire;

use App\Models\Kinship;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['kinshipUpdated', 'deleted'])]
class Kinships extends ReferenceCrudComponent
{
    protected string $modelClass = Kinship::class;

    protected string $savedEvent = 'kinshipUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_kinship');
    }

    protected function fields(): array
    {
        return ['id' => $this->idField()]
            + $this->localeFields('name', __('admin::references.fields.name'))
            + ['is_active' => ['label' => __('admin::references.fields.is_active'), 'type' => 'checkbox']];
    }

    protected function columns(): array
    {
        return [
            ['label' => __('admin::references.fields.id'), 'attr' => 'id'],
            $this->localeColumn('name', __('admin::references.fields.name')),
            ['label' => __('admin::references.fields.is_active'), 'check' => 'is_active'],
        ];
    }
}

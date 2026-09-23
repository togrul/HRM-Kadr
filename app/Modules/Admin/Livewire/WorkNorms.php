<?php

namespace App\Modules\Admin\Livewire;

use App\Models\WorkNorm;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['workNormUpdated', 'deleted'])]
class WorkNorms extends ReferenceCrudComponent
{
    protected string $modelClass = WorkNorm::class;

    protected string $savedEvent = 'workNormUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_work_norm');
    }

    protected function fields(): array
    {
        return ['id' => $this->idField()] + $this->localeFields('name', __('admin::references.fields.name'));
    }

    protected function columns(): array
    {
        return [
            ['label' => __('admin::references.fields.id'), 'attr' => 'id'],
            $this->localeColumn('name', __('admin::references.fields.name')),
        ];
    }
}

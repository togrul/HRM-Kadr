<?php

namespace App\Modules\Admin\Livewire;

use App\Models\EducationDegree;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['educationDegreeUpdated', 'deleted'])]
class EducationDegrees extends ReferenceCrudComponent
{
    protected string $modelClass = EducationDegree::class;

    protected string $savedEvent = 'educationDegreeUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_degree');
    }

    protected function fields(): array
    {
        return ['id' => $this->idField()] + $this->localeFields('title', __('admin::references.fields.name'));
    }

    protected function columns(): array
    {
        return [
            ['label' => __('admin::references.fields.id'), 'attr' => 'id'],
            $this->localeColumn('title', __('admin::references.fields.name')),
        ];
    }
}

<?php

namespace App\Modules\Admin\Livewire;

use App\Models\EducationForm;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['educationFormUpdated', 'deleted'])]
class EducationForms extends ReferenceCrudComponent
{
    protected string $modelClass = EducationForm::class;

    protected string $savedEvent = 'educationFormUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_education_form');
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

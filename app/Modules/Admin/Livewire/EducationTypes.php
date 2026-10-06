<?php

namespace App\Modules\Admin\Livewire;

use App\Models\EducationType;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['educationTypesUpdated', 'deleted'])]
class EducationTypes extends ReferenceCrudComponent
{
    protected string $modelClass = EducationType::class;

    protected string $savedEvent = 'educationTypesUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_education_type');
    }
}

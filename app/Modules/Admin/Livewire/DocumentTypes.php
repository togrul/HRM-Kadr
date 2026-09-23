<?php

namespace App\Modules\Admin\Livewire;

use App\Models\EducationDocumentType;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['documentTypesUpdated', 'deleted'])]
class DocumentTypes extends ReferenceCrudComponent
{
    protected string $modelClass = EducationDocumentType::class;

    protected string $savedEvent = 'documentTypesUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_document_type');
    }
}

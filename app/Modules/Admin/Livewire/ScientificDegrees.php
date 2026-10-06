<?php

namespace App\Modules\Admin\Livewire;

use App\Models\ScientificDegreeAndName;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['scientificDegreeUpdated', 'deleted'])]
class ScientificDegrees extends ReferenceCrudComponent
{
    protected string $modelClass = ScientificDegreeAndName::class;

    protected string $savedEvent = 'scientificDegreeUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_degree');
    }
}

<?php

namespace App\Modules\Admin\Livewire;

use App\Models\EducationalInstitution;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['educationalInstitutionsUpdated', 'deleted'])]
class EducationalInstitutions extends ReferenceCrudComponent
{
    protected string $modelClass = EducationalInstitution::class;

    protected string $savedEvent = 'educationalInstitutionsUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_institutions');
    }

    protected function fields(): array
    {
        $oldName = __('admin::references.fields.old_name');

        return [
            'id' => $this->idField(),
            'name' => ['label' => __('admin::references.fields.name'), 'rules' => 'required|string|min:2'],
            'shortname' => ['label' => __('admin::references.fields.shortname'), 'rules' => 'required|string|min:2'],
            'old_name_1' => ['label' => "{$oldName} 1"],
            'old_name_2' => ['label' => "{$oldName} 2"],
            'old_name_3' => ['label' => "{$oldName} 3"],
        ];
    }

    protected function columns(): array
    {
        $oldName = __('admin::references.fields.old_name');

        return [
            ['label' => __('admin::references.fields.id'), 'attr' => 'id'],
            ['label' => __('admin::references.fields.name'), 'attr' => 'name'],
            ['label' => __('admin::references.fields.shortname'), 'attr' => 'shortname'],
            ['label' => __('admin::references.fields.old_names'), 'lines' => [
                "{$oldName} 1" => 'old_name_1',
                "{$oldName} 2" => 'old_name_2',
                "{$oldName} 3" => 'old_name_3',
            ]],
        ];
    }
}

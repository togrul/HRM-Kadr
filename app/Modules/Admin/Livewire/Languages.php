<?php

namespace App\Modules\Admin\Livewire;

use App\Models\Language;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['languageUpdated', 'deleted'])]
class Languages extends ReferenceCrudComponent
{
    protected string $modelClass = Language::class;

    protected string $savedEvent = 'languageUpdated';

    protected function addLabel(): string
    {
        return __('admin::languages.actions.add');
    }

    protected function saveLabel(): string
    {
        return __('admin::languages.actions.save');
    }

    protected function actionsLabel(): string
    {
        return __('admin::languages.table.actions');
    }

    protected function fields(): array
    {
        return [
            'id' => $this->idField(__('admin::languages.fields.id')),
            'name' => ['label' => __('admin::languages.fields.name'), 'rules' => 'required|string|min:2'],
        ];
    }
}

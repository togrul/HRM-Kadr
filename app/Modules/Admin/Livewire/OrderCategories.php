<?php

namespace App\Modules\Admin\Livewire;

use App\Models\OrderCategory;
use App\Modules\Admin\Support\ReferenceCrudComponent;
use Livewire\Attributes\On;

#[On(['orderCategoryUpdated', 'deleted'])]
class OrderCategories extends ReferenceCrudComponent
{
    protected string $modelClass = OrderCategory::class;

    protected string $savedEvent = 'orderCategoryUpdated';

    protected function addLabel(): string
    {
        return __('admin::references.buttons.add_category');
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

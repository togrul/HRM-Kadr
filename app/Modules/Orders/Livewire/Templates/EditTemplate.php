<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\Helpers\FillComplexArrayTrait;
use App\Livewire\Traits\TemplateCrud;
use App\Models\Order;
use Livewire\Component;

class EditTemplate extends Component
{
    use FillComplexArrayTrait;
    use TemplateCrud;

    public $templateModelData;

    public $fileUpdated = false;

    public function updated($value, $key)
    {
        if ($value == 'template_data.content') {
            $this->fileUpdated = true;
        }
    }

    protected function fillTemplate()
    {
        $this->templateModelData = Order::with('category')
            ->where('id', $this->templateModel)
            ->first();

        $updatedData = $this->templateModelData->toArray();

        $this->template_data = $this->mapAttributes(
            attributes: [
                'id', 'name', 'content', 'order_model', 'blade',
            ],
            getFrom: $updatedData
        );

        $this->template_data['order_category_id'] = $this->templateModelData->order_category_id;
    }

    public function store()
    {
        $this->validate();

        if ($this->fileUpdated) {
            $filename = "{$this->template_data['name']}.docx";

            $this->template_data['content'] = $this->template_data['content']->storeAs('templates', $filename, 'public');
        }

        $this->templateModelData->update($this->modifyArray($this->template_data));

        $this->dispatch('templateAdded', __('Template was added successfully!'));
    }
}

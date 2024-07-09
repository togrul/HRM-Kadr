<?php

namespace App\Livewire\Orders\Templates;

use App\Livewire\Traits\TemplateCrud;
use App\Models\Order;
use Livewire\Component;

class EditTemplate extends Component
{
    use TemplateCrud;
    public $templateModelData;
    public $fileUpdated = false;
    public function updated($value,$key)
    {
        if($value == 'template_data.content')
        {
            $this->fileUpdated = true;
        }
    }

    protected function fillTemplate()
    {
        $this->templateModelData = Order::with('category')
                                    ->where('id', $this->templateModel)
                                    ->first();

        $updatedData = $this->templateModelData->toArray();


        $this->template_data = [
            'id' => $updatedData['id'],
            'name' => $updatedData['name'],
            'content' => $updatedData['content'],
            'order_model' => $updatedData['order_model'],
            'blade' => $updatedData['blade']
        ];

        if(!empty($updatedData['order_category_id']))
        {
            $this->template_data['order_category_id'] = [
                'id' => $updatedData['category']['id'],
                'name' => $updatedData['category']['name_'.config('app.locale')],
            ];
            $this->categoryId = $this->template_data['order_category_id']['id'];
            $this->categoryName = $this->template_data['order_category_id']['name'];
        }
    }

    public function store()
    {
        $this->validate();

        if($this->fileUpdated)
        {
            $filename = "{$this->template_data['name']}.docx";

            $this->template_data['content'] = $this->template_data['content']->storeAs('templates', $filename,'public');
        }

        $this->templateModelData->update($this->modifyArray($this->template_data));

        $this->dispatch('templateAdded',__('Template was added successfully!'));
    }
}

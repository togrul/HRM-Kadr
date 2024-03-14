<?php

namespace App\Livewire\Orders\Templates;

use App\Livewire\Traits\TemplateCrud;
use App\Models\Order;
use Livewire\Component;

class AddTemplate extends Component
{
    use TemplateCrud;

    public function store()
    {
        $this->validate();

        $filename = "{$this->template_data['name']}.docx";

        $this->template_data['content'] = $this->template_data['content']->storeAs('templates', $filename,'public');

        Order::create($this->modifyArray($this->template_data));

        $this->dispatch('templateAdded',__('Template was added successfully!'));
    }
}

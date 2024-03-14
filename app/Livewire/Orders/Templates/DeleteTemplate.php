<?php

namespace App\Livewire\Orders\Templates;

use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\On;

class DeleteTemplate extends Component
{
    public ?Order $template;

    #[On('setDeleteTemplate')]
    public function setDeleteTemplate($templateId)
    {
        $this->template = Order::where('id',$templateId)->first();

        $this->dispatch('deleteTemplateWasSet');
    }

    public function deleteTemplate()
    {
        // $this->authorize('delete',$this->template);

        Order::destroy($this->template->id);

        $this->template = null;

        $this->dispatch('templateWasDeleted', __('Template was deleted!'));
    }

    public function render()
    {
        return view('livewire.orders.templates.delete-template');
    }
}

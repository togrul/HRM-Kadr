<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteTemplate extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $templateId = null;

    #[On('setDeleteTemplate')]
    public function setDeleteTemplate($templateId)
    {
        $template = Order::query()
            ->select('id')
            ->find($templateId);

        if (! $template) {
            $this->templateId = null;

            return;
        }

        // $this->authorize('delete', $template);

        $this->templateId = (int) $template->id;

        $this->dispatch('deleteTemplateWasSet');
    }

    public function deleteTemplate()
    {
        if (! $this->templateId) {
            return;
        }

        $template = Order::query()
            ->select('id')
            ->find($this->templateId);

        if (! $template) {
            $this->templateId = null;

            return;
        }

        // $this->authorize('delete', $template);

        $template->delete();

        $this->templateId = null;

        $this->dispatch('templateWasDeleted', __('Template was deleted!'));
    }

    public function render()
    {
        return view('orders::livewire.orders.templates.delete-template');
    }
}

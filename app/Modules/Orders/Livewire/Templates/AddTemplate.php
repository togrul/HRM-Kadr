<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\TemplateCrud;
use App\Modules\Orders\Domain\Contracts\OrderTemplateAdmin;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use RuntimeException;

class AddTemplate extends Component
{
    use AuthorizesRequests;
    use TemplateCrud;

    public function store()
    {
        $this->authorize('edit-orders');

        $this->validate();

        $this->template_data['content'] = $this->storeUploadedTemplate();

        try {
            app(OrderTemplateAdmin::class)->create($this->modifyArray($this->template_data));
        } catch (RuntimeException $exception) {
            $this->dispatch('addError', $exception->getMessage());

            return;
        }

        $this->dispatch('templateAdded', __('orders::templates_list.messages.template_added'));
    }
}

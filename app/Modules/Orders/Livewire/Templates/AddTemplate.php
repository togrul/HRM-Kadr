<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\TemplateCrud;
use App\Services\Orders\TemplateAdminService;
use Livewire\Component;
use RuntimeException;

class AddTemplate extends Component
{
    use TemplateCrud;

    public function store()
    {
        $this->validate();

        $filename = "{$this->template_data['name']}.docx";

        $this->template_data['content'] = $this->template_data['content']->storeAs('templates', $filename, 'public');

        try {
            app(TemplateAdminService::class)->create($this->modifyArray($this->template_data));
        } catch (RuntimeException $exception) {
            $this->dispatch('addError', __($exception->getMessage()));

            return;
        }

        $this->dispatch('templateAdded', __('Template was added successfully!'));
    }
}

<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\SideModalAction;
use App\Modules\Orders\Domain\Contracts\OrderTemplateReadRepository;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[On(['templateAdded', 'templateWasDeleted'])]
class AllTemplates extends Component
{
    use AuthorizesRequests,SideModalAction,WithPagination;

    #[Url]
    public $status = 'active';

    public function setDeleteTemplate($templateId)
    {
        $this->dispatch('setDeleteTemplate', $templateId);
    }

    #[On('openSetTypeFromTemplateEdit')]
    public function openSetTypeFromTemplateEdit(int $templateId): void
    {
        $this->openSideMenu('set-type', $templateId);
    }

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function restoreData($id)
    {
        $template = app(OrderTemplateReadRepository::class)->findTemplateWithTrashed((int) $id);
        if (! $template) {
            return;
        }
        $template->restore();
        $template->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('templateAdded', __('orders::templates_list.messages.template_updated'));
    }

    public function forceDeleteData($id)
    {
        $model = app(OrderTemplateReadRepository::class)->findTemplateWithTrashed((int) $id);
        if (! $model) {
            return;
        }
        $model->forceDelete();
        $this->dispatch('templateWasDeleted', __('orders::templates_list.messages.template_deleted'));
    }

    public function fillFilter()
    {
        $this->status = request()->query('status')
                        ? request()->query('status')
                        : 'active';
    }

    public function render()
    {
        $templates = app(OrderTemplateReadRepository::class)->paginateTemplates((string) $this->status, 24);

        return view('orders::livewire.orders.templates.all-templates', compact('templates'));
    }
}

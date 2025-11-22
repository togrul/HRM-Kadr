<?php

namespace App\Modules\Orders\Livewire\Templates;

use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
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

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function restoreData($id)
    {
        $template = Order::withTrashed()->where('id', $id)->first();
        $template->restore();
        $template->update([
            'deleted_by' => null,
        ]);
        $this->dispatch('templateAdded', __('Template was updated successfully!'));
    }

    public function forceDeleteData($id)
    {
        $model = Order::withTrashed()->where('id', $id)->first();
        $model->forceDelete();
        $this->dispatch('templateWasDeleted', __('Template was deleted!'));
    }

    public function fillFilter()
    {
        $this->status = request()->query('status')
                        ? request()->query('status')
                        : 'active';
    }

    public function render()
    {
        $templates = Order::with('category')
            ->when($this->status == 'deleted', function ($q) {
                $q->onlyTrashed();
            })
            ->paginate(24);

        return view('orders::livewire.orders.templates.all-templates', compact('templates'));
    }
}

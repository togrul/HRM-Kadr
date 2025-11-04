<?php

namespace App\Livewire\Orders;

use App\Models\OrderLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteOrder extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public ?int $orderLogId = null;

    #[On('setDeleteOrder')]
    public function setDeleteOrder($order_no)
    {
        $orderLog = OrderLog::query()
            ->select('id', 'order_no')
            ->where('order_no', $order_no)
            ->first();

        if (! $orderLog) {
            $this->orderLogId = null;

            return;
        }

        $this->authorize('delete-orders', $orderLog);

        $this->orderLogId = (int) $orderLog->id;

        $this->dispatch('deleteOrderWasSet');
    }

    public function deleteOrder()
    {
        if (! $this->orderLogId) {
            return;
        }

        $orderLog = OrderLog::query()
            ->select('id')
            ->find($this->orderLogId);

        if (! $orderLog) {
            $this->orderLogId = null;

            return;
        }

        $this->authorize('delete-orders', $orderLog);

        $orderLog->delete();

        $this->orderLogId = null;

        $this->dispatch('orderWasDeleted', __('Order was deleted!'));
    }

    public function render()
    {
        return view('livewire.orders.delete-order');
    }
}

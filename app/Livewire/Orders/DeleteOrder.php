<?php

namespace App\Livewire\Orders;

use App\Models\OrderLog;
use Livewire\Attributes\On;
use Livewire\Component;

class DeleteOrder extends Component
{
    public ?OrderLog $orderLog;

    #[On('setDeleteOrder')]
    public function setDeleteOrder($order_no)
    {
        $this->orderLog = OrderLog::where('order_no', $order_no)->first();

        $this->dispatch('deleteOrderWasSet');
    }

    public function deleteOrder()
    {
        $this->authorize('delete-orders', $this->orderLog);

        OrderLog::destroy($this->orderLog->id);

        $this->orderLog = null;

        $this->dispatch('orderWasDeleted', __('Order was deleted!'));
    }

    public function render()
    {
        return view('livewire.orders.delete-order');
    }
}

<?php

namespace App\Livewire\Structure;

use App\Models\OrderCategory;
use Livewire\Attributes\Url;
use Livewire\Component;

class Orders extends Component
{
    #[Url]
    public $selectedOrder;

    public function selectOrder($orderKey)
    {
        $this->selectedOrder = $orderKey;
        $this->dispatch('selectOrder', $orderKey);
    }

    public function render()
    {
        $_order_categories = OrderCategory::with('orders')
            ->get();

        return view('livewire.structure.orders', compact('_order_categories'));
    }
}

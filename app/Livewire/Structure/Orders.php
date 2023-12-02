<?php

namespace App\Livewire\Structure;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\OrderCategory;
use Illuminate\Support\Facades\DB;

class Orders extends Component
{
    #[Url]
    public $selectedOrder;

    public function selectOrder($orderKey)
    {
        $this->selectedOrder = $orderKey;
        $this->dispatch('selectOrder',$orderKey);
    }


    public function render()
    {
        $_order_categories = OrderCategory::with(['orders' => function($q){
            $q->select('id','order_category_id',DB::raw('name_'.config('app.locale').' as name'),'shortname');
        }])
            ->select('id',DB::raw('name_'.config('app.locale').' as name'))
            ->get();


        return view('livewire.structure.orders',compact('_order_categories'));
    }
}

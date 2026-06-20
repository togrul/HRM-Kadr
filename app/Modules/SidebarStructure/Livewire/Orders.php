<?php

namespace App\Modules\SidebarStructure\Livewire;

use App\Models\OrderCategory;
use Illuminate\Support\Facades\Cache;
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
        $locale = (string) config('app.locale');
        $cacheKey = "sidebar_structure:orders:categories:{$locale}";

        $_order_categories = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return OrderCategory::query()
                ->select(['id', 'name_az', 'name_en', 'name_ru'])
                ->with([
                    'orders' => fn ($query) => $query
                        ->select(['id', 'order_category_id', 'name'])
                        ->orderBy('id'),
                ])
                ->orderBy('id')
                ->get();
        });

        return view('structure::livewire.structure.orders', compact('_order_categories'));
    }
}

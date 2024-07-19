<?php

namespace App\Livewire\Traits;

use App\Models\Order;
use App\Models\OrderType;
use App\Models\Rank;
use Illuminate\Support\Str;

trait ComponentCrud
{
    use SelectListTrait;

    public $component = [];

    public $orderId;
    public $orderName;
    public $searchOrder;

    public $title;

    public $componentModel;

    public function rules()
    {
        return [
            'component.name' => 'required|string|min:2',
            'component.content' => 'required',
            'component.order_type_id.id' => 'required|int|exists:order_types,id',
        ];
    }

    protected function validationAttributes()
    {
        return [
            'component.name' => __('Name'),
            'component.content' => __('Content'),
            'component.order_type_id.id' => __('Category'),
        ];
    }

    public function updated($name,$value)
    {
        $data = isset($this->component['title'])
            ? "{$this->component['title']} {$this->component['content']}"
            : $this->component['content'];
        $dollarStrings = array_filter(explode(' ', str_replace(['“','”'],'',$data)), function ($string) {
            return Str::startsWith($string, '$');
        });

        $this->component['dynamic_fields'] = implode(',',array_unique($dollarStrings));
    }

    public function mount()
    {
        $this->orderName = '---';
        if(!empty($this->componentModel))
        {
            $this->fillComponent();
            $this->title = __('Edit component');
        }
        else
        {
            $this->title = __('Add component');
        }
    }

    public function render()
    {
        $_orders = OrderType::when(!empty($this->searchOrder), function ($q) {
            $q->where('name', 'LIKE', "%{$this->searchOrder}%");
        })
            ->get();

        $_ranks = Rank::query()
            ->where('is_active',1)
            ->get();

        $view_name = !empty($this->candidateModel)
            ? 'livewire.services.components.edit-component'
            : 'livewire.services.components.add-component';

        return view($view_name, compact('_orders','_ranks'));
    }
}

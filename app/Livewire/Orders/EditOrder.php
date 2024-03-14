<?php

namespace App\Livewire\Orders;

use App\Livewire\Traits\SelectListTrait;
use Livewire\Component;

class EditOrder extends Component
{
    use SelectListTrait;


    public function render()
    {
        return view('livewire.orders.edit-order');
    }
}

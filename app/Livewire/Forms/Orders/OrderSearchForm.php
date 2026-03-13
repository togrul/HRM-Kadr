<?php

namespace App\Livewire\Forms\Orders;

use Livewire\Form;

class OrderSearchForm extends Form
{
    public ?string $template = null;
    public ?string $personnel = null;
    public ?string $rank = null;
    public ?string $mainStructure = null;
    public ?string $structure = null;
    public ?string $position = null;
}

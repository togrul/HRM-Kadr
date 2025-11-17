<?php

namespace App\Livewire\Forms\Orders;

use Livewire\Form;

class OrderComponentForm extends Form
{
    public ?int $rank_id = null;
    public ?int $component_id = null;
    public ?int $personnel_id = null;
    public ?int $structure_main_id = null;
    public ?int $structure_id = null;
    public ?int $position_id = null;
    public ?int $row = null;
    public array $extra = [];

    public function fillDefaults(array $fields): void
    {
        $this->fill(array_merge($this->defaults(), $fields));
    }

    protected function defaults(): array
    {
        return [
            'rank_id' => null,
            'component_id' => null,
            'personnel_id' => null,
            'structure_main_id' => null,
            'structure_id' => null,
            'position_id' => null,
            'row' => null,
            'extra' => [],
        ];
    }
}

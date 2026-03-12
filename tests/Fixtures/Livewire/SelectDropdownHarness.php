<?php

namespace Tests\Fixtures\Livewire;

use Livewire\Component;

class SelectDropdownHarness extends Component
{
    public array $options = [
        ['id' => 1, 'label' => 'One'],
        ['id' => 2, 'label' => 'Two'],
    ];

    public int|string|null $selected = 1;

    public string $search = '';

    public function swapOptions(): void
    {
        $this->options = [
            ['id' => 2, 'label' => 'Second'],
            ['id' => 'archived', 'label' => 'Archived'],
        ];

        $this->selected = 'archived';
    }

    public function render()
    {
        return <<<'BLADE'
        <div>
            <x-ui.select-dropdown
                label="State"
                instance="test-select-dropdown"
                search-model="search"
                wire:model.live="selected"
                :model="$options"
            />
        </div>
        BLADE;
    }
}

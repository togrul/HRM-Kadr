<?php

namespace Tests\Fixtures\Livewire;

use App\Modules\Orders\Support\Traits\Orders\DropdownLabelCache;
use App\Modules\Orders\Support\Traits\Orders\HandlesOrderComponentFieldState;
use Illuminate\Support\Collection;
use Livewire\Component;

class OrderRadioTreeHarness extends Component
{
    use DropdownLabelCache;
    use HandlesOrderComponentFieldState;

    public array $componentForms = [
        [
            'structure_main_id' => 10,
            'structure_id' => 11,
        ],
    ];

    public array $coded_list = [true];

    public function modelTree(): Collection
    {
        return collect([
            (object) [
                'id' => 11,
                'parent_id' => 10,
                'name' => '1-ci şöbə',
                'code' => 1,
                'level' => 2,
                'subs' => collect(),
            ],
        ]);
    }

    protected function structureLineage(int $structureId): array
    {
        if ($structureId !== 11) {
            return [];
        }

        return [
            ['id' => 1, 'parent_id' => null, 'name' => 'Kök struktur', 'code' => 0, 'level' => 0],
            ['id' => 10, 'parent_id' => 1, 'name' => '10-cu idarə', 'code' => 10, 'level' => 1],
            ['id' => 11, 'parent_id' => 10, 'name' => '1-ci şöbə', 'code' => 1, 'level' => 2],
        ];
    }

    public function render()
    {
        return <<<'BLADE'
        <div>
            <x-dynamic-input
                :list="$componentForms"
                field="structure_id"
                title="İdarə seç"
                type="$structure"
                :model="$this->modelTree()"
                :key="0"
                :selected-label="$this->componentFieldLabel(0, 'structure_id')"
                :selected-value="$this->componentFieldValue(0, 'structure_id')"
                :is-coded="true"
                :row="0"
            />
        </div>
        BLADE;
    }
}

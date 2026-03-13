<?php

namespace Tests\Unit\Orders;

use App\Modules\Orders\Support\Traits\Orders\DropdownLabelCache;
use App\Modules\Orders\Support\Traits\Orders\HandlesOrderComponentFieldState;
use Tests\TestCase;

class HandlesOrderComponentFieldStateTest extends TestCase
{
    public function test_it_uses_full_plain_structure_label_for_selected_structure_field(): void
    {
        $subject = new class
        {
            use DropdownLabelCache;
            use HandlesOrderComponentFieldState;

            public array $componentForms = [
                ['structure_id' => 11],
            ];

            public array $coded_list = [true];

            protected array $fakeLineage = [
                11 => [
                    ['id' => 1, 'parent_id' => null, 'name' => 'Azərbaycan Respublikası Prezidentinin Təhlükəsizlik Xidməti', 'code' => 0, 'level' => 0],
                    ['id' => 10, 'parent_id' => 1, 'name' => '10-cu idarə', 'code' => 10, 'level' => 1],
                    ['id' => 11, 'parent_id' => 10, 'name' => '1-ci şöbə', 'code' => 1, 'level' => 2],
                ],
            ];

            protected function structureLineage(int $structureId): array
            {
                return $this->fakeLineage[$structureId] ?? [];
            }
        };

        $this->assertSame('10-cu idarənin 1-ci şöbəsinin', $subject->componentFieldLabel(0, 'structure_id'));
    }
}

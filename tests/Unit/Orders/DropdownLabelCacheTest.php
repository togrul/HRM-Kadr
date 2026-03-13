<?php

namespace Tests\Unit\Orders;

use App\Modules\Orders\Support\Traits\Orders\DropdownLabelCache;
use Tests\TestCase;

class DropdownLabelCacheTest extends TestCase
{
    public function test_it_builds_full_plain_structure_label_for_selected_structure(): void
    {
        $subject = new class
        {
            use DropdownLabelCache;

            public function plainLabel(array $lineage): string
            {
                return $this->buildStructureValue($lineage, false);
            }

            public function selectedLabel(array $lineage, bool $coded): string
            {
                return $this->buildStructureSelectedValue($lineage, $coded);
            }
        };

        $label = $subject->plainLabel([
            ['id' => 1, 'parent_id' => null, 'name' => 'Azərbaycan Respublikası Prezidentinin Təhlükəsizlik Xidməti', 'code' => 0, 'level' => 0],
            ['id' => 10, 'parent_id' => 1, 'name' => '10-cu idarə', 'code' => 10, 'level' => 1],
            ['id' => 11, 'parent_id' => 10, 'name' => '1-ci şöbə', 'code' => 1, 'level' => 2],
        ]);

        $this->assertSame('10-cu idarənin 1-ci şöbəsi', $label);

        $selected = $subject->selectedLabel([
            ['id' => 1, 'parent_id' => null, 'name' => 'Azərbaycan Respublikası Prezidentinin Təhlükəsizlik Xidməti', 'code' => 0, 'level' => 0],
            ['id' => 10, 'parent_id' => 1, 'name' => '10-cu idarə', 'code' => 10, 'level' => 1],
            ['id' => 11, 'parent_id' => 10, 'name' => '1-ci şöbə', 'code' => 1, 'level' => 2],
        ], true);

        $this->assertSame('10-cu idarənin 1-ci şöbəsinin', $selected);

        $single = $subject->selectedLabel([
            ['id' => 1, 'parent_id' => null, 'name' => 'Azərbaycan Respublikası Prezidentinin Təhlükəsizlik Xidməti', 'code' => 0, 'level' => 0],
            ['id' => 7, 'parent_id' => 1, 'name' => '7-ci idarə', 'code' => 7, 'level' => 1],
        ], true);

        $this->assertSame('7-ci idarənin', $single);
    }
}

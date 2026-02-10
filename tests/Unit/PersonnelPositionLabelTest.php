<?php

namespace Tests\Unit;

use App\Models\Personnel;
use App\Models\PersonnelDisposal;
use App\Models\PersonnelLaborActivity;
use App\Models\Position;
use PHPUnit\Framework\TestCase;

class PersonnelPositionLabelTest extends TestCase
{
    public function test_position_label_does_not_lazy_query_when_relations_are_missing(): void
    {
        $personnel = new Personnel;
        $personnel->setRelation('position', new Position(['name' => 'Officer']));

        $this->assertSame('Officer', $personnel->position_label);
    }

    public function test_position_label_appends_vmie_when_current_work_overlaps_disposal(): void
    {
        $personnel = new Personnel;
        $personnel->setRelation('position', new Position(['name' => 'Officer']));
        $currentWork = new PersonnelLaborActivity([
            'position' => 'Officer',
            'join_date' => '2024-01-01',
            'is_current' => true,
            'leave_date' => null,
        ]);
        $currentWork->id = 1;

        $personnel->setRelation('currentWork', $currentWork);
        $personnel->setRelation('latestDisposal', new PersonnelDisposal([
            'disposal_date' => '2024-06-01',
            'disposal_end_date' => null,
        ]));

        $this->assertSame('Officer VMİE', $personnel->position_label);
    }
}

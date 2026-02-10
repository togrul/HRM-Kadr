<?php

namespace Tests\Unit\Services;

use App\Services\Orders\OrderAttributeMappingService;
use Tests\TestCase;

class OrderAttributeMappingServiceTest extends TestCase
{
    public function test_it_maps_component_attribute_keys_and_resolved_values(): void
    {
        $service = new OrderAttributeMappingService;

        $componentForms = [[
            'component_id' => 1,
            'row' => 0,
            'rank_id' => 80,
            'personnel_id' => 28,
            'structure_main_id' => 1,
            'structure_id' => 18,
            'position_id' => 1000,
            'day' => '22',
        ]];

        $result = $service->mapComponentAttributes(
            componentForms: $componentForms,
            attributeValueResolver: fn (string $field, $value, ?int $row) => "{$field}:{$value}:{$row}"
        );

        $this->assertArrayHasKey('$rank', $result[0]);
        $this->assertArrayHasKey('$fullname', $result[0]);
        $this->assertArrayHasKey('$structure_main', $result[0]);
        $this->assertArrayHasKey('$structure', $result[0]);
        $this->assertArrayHasKey('$position', $result[0]);
        $this->assertArrayHasKey('$day', $result[0]);
        $this->assertArrayHasKey('component_id', $result[0]);
        $this->assertArrayHasKey('row', $result[0]);

        $this->assertSame('rank_id:80:0', $result[0]['$rank']);
        $this->assertSame('component_id:1:0', $result[0]['component_id']);
        $this->assertSame('day:22:0', $result[0]['$day']);
    }
}

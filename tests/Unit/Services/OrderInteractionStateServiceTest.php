<?php

namespace Tests\Unit\Services;

use App\Services\Orders\OrderInteractionStateService;
use Tests\TestCase;

class OrderInteractionStateServiceTest extends TestCase
{
    public function test_it_resolves_structure_selection_payload(): void
    {
        $service = new OrderInteractionStateService;

        $selection = $service->resolveStructureSelection(
            id: ['id' => 18, 'list' => 'componentForms', 'field' => 'structure_id', 'row' => 0, 'coded' => false],
            list: null,
            field: null,
            key: null,
            isCoded: null,
            structureLineageResolver: fn (int $id) => [['id' => 1, 'name' => 'Main'], ['id' => $id, 'name' => 'Child']],
            structureLabelBuilder: fn (array $lineage, bool $coded) => $coded ? 'Coded' : 'Composed'
        );

        $this->assertSame('componentForms', $selection['list']);
        $this->assertSame('structure_id', $selection['field']);
        $this->assertSame(0, $selection['key']);
        $this->assertSame(18, $selection['id']);
        $this->assertSame('Child', $selection['label']);
    }

    public function test_it_resolves_dynamic_fields_using_fallback(): void
    {
        $service = new OrderInteractionStateService;

        $fields = $service->resolveSelectedComponentFields(
            componentId: 10,
            componentDefinitions: [],
            dynamicFieldsFallbackResolver: fn (int $id) => $id === 10 ? 'rank_id,personnel_id' : null
        );

        $this->assertSame(['rank_id', 'personnel_id'], array_values($fields));
    }

    public function test_it_resolves_template_selection_context(): void
    {
        $service = new OrderInteractionStateService;

        $selection = $service->resolveTemplateSelection(
            value: 9,
            selectedOrder: null,
            orderTypeResolver: fn (int $id) => [
                'order_id' => $id + 100,
                'selected_blade' => 'default',
            ]
        );

        $this->assertTrue($selection['showComponent']);
        $this->assertSame(9, $selection['selectedTemplate']);
        $this->assertSame(109, $selection['orderId']);
        $this->assertSame('default', $selection['selectedBlade']);
    }

    public function test_it_resolves_personnel_name_for_candidate_order(): void
    {
        $service = new OrderInteractionStateService;

        $payload = $service->resolvePersonnelName(
            value: 42,
            orderId: 1010,
            candidateOrderId: 1010,
            candidateResolver: fn () => (object) ['name' => 'Ada', 'surname' => 'Lovelace'],
            personnelResolver: fn () => null
        );

        $this->assertSame(['name' => 'Ada', 'surname' => 'Lovelace'], $payload);
    }
}

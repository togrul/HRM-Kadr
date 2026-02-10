<?php

namespace Tests\Unit\Services;

use App\Services\Orders\OrderLookupService;
use App\Services\Orders\OrderRenderPayloadBuilder;
use App\Services\Orders\OrderRenderStateService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class OrderRenderStateServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_resolves_lookup_collections_and_calls_remember_callback(): void
    {
        $components = collect([(object) ['id' => 10, 'dynamic_fields' => 'rank_id']]);
        $templates = collect([(object) ['id' => 20, 'name' => 'Template']]);
        $personnels = collect([(object) ['id' => 30, 'name' => 'A']]);
        $ranks = collect([(object) ['id' => 40, 'name' => 'Rank']]);
        $mainStructures = collect([(object) ['id' => 50, 'name' => 'Main']]);
        $structures = collect([(object) ['id' => 60, 'name' => 'Structure']]);
        $positions = collect([(object) ['id' => 70, 'name' => 'Position']]);

        $lookupService = Mockery::mock(OrderLookupService::class);
        $lookupService->shouldReceive('components')->once()->with(11)->andReturn($components);
        $lookupService->shouldReceive('templates')->once()->with(5, 'tpl')->andReturn($templates);
        $lookupService->shouldReceive('personnels')->once()->with(true, [101], 'pers')->andReturn($personnels);
        $lookupService->shouldReceive('ranks')->once()->andReturn($ranks);
        $lookupService->shouldReceive('mainStructures')->once()->andReturn($mainStructures);
        $lookupService->shouldReceive('structures')->once()->with('str')->andReturn($structures);
        $lookupService->shouldReceive('positions')->once()->with('pos')->andReturn($positions);

        $payloadBuilder = Mockery::mock(OrderRenderPayloadBuilder::class);
        $service = new OrderRenderStateService($lookupService, $payloadBuilder);

        $remembered = null;
        $lookups = $service->resolveLookupCollections(
            needsPersonnelLookup: true,
            isCandidateOrder: true,
            selectedOrder: 5,
            selectedTemplate: 11,
            searchTemplate: 'tpl',
            searchPersonnel: 'pers',
            searchStructure: 'str',
            searchPosition: 'pos',
            personnelIdList: [101],
            rememberComponentDefinitions: function (Collection $collection) use (&$remembered): void {
                $remembered = $collection;
            }
        );

        $this->assertSame($components, $lookups['components']);
        $this->assertSame($templates, $lookups['templates']);
        $this->assertSame($personnels, $lookups['personnels']);
        $this->assertSame($ranks, $lookups['ranks']);
        $this->assertSame($mainStructures, $lookups['main_structures']);
        $this->assertSame($structures, $lookups['structures']);
        $this->assertSame($positions, $lookups['positions']);
        $this->assertSame($components, $remembered);
    }

    public function test_it_delegates_render_payload_building(): void
    {
        $lookupService = Mockery::mock(OrderLookupService::class);
        $payloadBuilder = Mockery::mock(OrderRenderPayloadBuilder::class);

        $lookups = ['templates' => collect()];
        $expected = ['payload' => true];

        $register = fn (string $field, array $options) => null;
        $resolver = fn ($person) => (string) data_get($person, 'name');

        $payloadBuilder->shouldReceive('build')
            ->once()
            ->with($lookups, 'default', 'Name', [1, 2], $register, $resolver)
            ->andReturn($expected);

        $service = new OrderRenderStateService($lookupService, $payloadBuilder);

        $result = $service->buildRenderPayload(
            lookups: $lookups,
            selectedBlade: 'default',
            personnelName: 'Name',
            selectedPersonnelNumbers: [1, 2],
            registerOptionLabels: $register,
            personnelLabelResolver: $resolver
        );

        $this->assertSame($expected, $result);
    }
}

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
        $lookupService->shouldReceive('personnels')->once()->with(true, [101], 'pers', 15)->andReturn($personnels);
        $lookupService->shouldReceive('ranks')->once()->with('rank')->andReturn($ranks);
        $lookupService->shouldReceive('mainStructures')->once()->with('main')->andReturn($mainStructures);
        $lookupService->shouldReceive('structures')->once()->with('str')->andReturn($structures);
        $lookupService->shouldReceive('positions')->once()->with('pos')->andReturn($positions);

        $payloadBuilder = Mockery::mock(OrderRenderPayloadBuilder::class);
        $service = new OrderRenderStateService($lookupService, $payloadBuilder);

        $remembered = null;
        $lookups = $service->resolveLookupCollections(
            isCandidateOrder: true,
            selectedOrder: 5,
            selectedTemplate: 11,
            searchTemplate: 'tpl',
            searchPersonnel: 'pers',
            searchRank: 'rank',
            searchMainStructure: 'main',
            searchStructure: 'str',
            searchPosition: 'pos',
            personnelIdList: [101],
            componentIdList: [10],
            selectedDropdownValues: [
                'rank_id' => [40],
                'structure_main_id' => [50],
                'structure_id' => [60],
                'position_id' => [70],
            ],
            loadedOptionGroups: [
                'templates' => true,
                'components' => true,
                'personnels' => true,
                'ranks' => true,
                'main_structures' => true,
                'structures' => true,
                'positions' => true,
            ],
            visibleFields: ['component_id', 'personnel_id', 'rank_id', 'structure_main_id', 'structure_id', 'position_id'],
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

    public function test_it_loads_limited_personnel_options_for_non_candidate_when_search_is_empty(): void
    {
        $components = collect([(object) ['id' => 10, 'dynamic_fields' => 'rank_id']]);
        $templates = collect([(object) ['id' => 20, 'name' => 'Template']]);
        $personnels = collect([(object) ['id' => 31, 'name' => 'B']]);
        $lookupService = Mockery::mock(OrderLookupService::class);
        $lookupService->shouldReceive('components')->once()->with(11)->andReturn($components);
        $lookupService->shouldReceive('templates')->once()->with(5, '')->andReturn($templates);
        $lookupService->shouldReceive('personnels')->once()->with(false, [], '', 15)->andReturn($personnels);

        $payloadBuilder = Mockery::mock(OrderRenderPayloadBuilder::class);
        $service = new OrderRenderStateService($lookupService, $payloadBuilder);

        $lookups = $service->resolveLookupCollections(
            isCandidateOrder: false,
            selectedOrder: 5,
            selectedTemplate: 11,
            searchTemplate: '',
            searchPersonnel: '',
            searchRank: '',
            searchMainStructure: '',
            searchStructure: '',
            searchPosition: '',
            personnelIdList: [],
            componentIdList: [10],
            selectedDropdownValues: [],
            loadedOptionGroups: [
                'templates' => true,
                'personnels' => true,
            ],
            visibleFields: ['component_id', 'personnel_id'],
            rememberComponentDefinitions: function () {
            }
        );

        $this->assertSame($personnels, $lookups['personnels']);
    }

    public function test_it_resolves_main_structures_when_structure_search_is_used(): void
    {
        $mainStructures = collect([(object) ['id' => 50, 'name' => 'Main']]);

        $lookupService = Mockery::mock(OrderLookupService::class);
        $lookupService->shouldReceive('mainStructures')->once()->with('dep')->andReturn($mainStructures);

        $payloadBuilder = Mockery::mock(OrderRenderPayloadBuilder::class);
        $service = new OrderRenderStateService($lookupService, $payloadBuilder);

        $lookups = $service->resolveLookupCollections(
            isCandidateOrder: false,
            selectedOrder: null,
            selectedTemplate: null,
            searchTemplate: '',
            searchPersonnel: '',
            searchRank: '',
            searchMainStructure: 'dep',
            searchStructure: 'dep',
            searchPosition: '',
            personnelIdList: [],
            componentIdList: [],
            selectedDropdownValues: [],
            loadedOptionGroups: [],
            visibleFields: ['structure_main_id'],
            rememberComponentDefinitions: function () {
            }
        );

        $this->assertSame($mainStructures, $lookups['main_structures']);
    }

    public function test_it_resolves_ranks_when_rank_search_is_used(): void
    {
        $ranks = collect([(object) ['id' => 40, 'name' => 'General']]);

        $lookupService = Mockery::mock(OrderLookupService::class);
        $lookupService->shouldReceive('ranks')->once()->with('gen')->andReturn($ranks);

        $payloadBuilder = Mockery::mock(OrderRenderPayloadBuilder::class);
        $service = new OrderRenderStateService($lookupService, $payloadBuilder);

        $lookups = $service->resolveLookupCollections(
            isCandidateOrder: false,
            selectedOrder: null,
            selectedTemplate: null,
            searchTemplate: '',
            searchPersonnel: '',
            searchRank: 'gen',
            searchMainStructure: '',
            searchStructure: '',
            searchPosition: '',
            personnelIdList: [],
            componentIdList: [],
            selectedDropdownValues: [],
            loadedOptionGroups: [],
            visibleFields: ['rank_id'],
            rememberComponentDefinitions: function () {
            }
        );

        $this->assertSame($ranks, $lookups['ranks']);
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

<?php

namespace Tests\Feature\Personnel;

use App\Modules\Personnel\Services\PersonnelListStateNormalizer;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Modules\Personnel\Services\PersonnelRowActionService;
use Tests\TestCase;

class PersonnelListRegressionTest extends TestCase
{
    public function test_all_status_query_does_not_filter_pending_records(): void
    {
        $query = app(PersonnelQueryService::class)->build(
            status: 'all',
            filters: [],
            selectedStructureIds: [1],
            accessibleStructureIds: [1],
            selectedPosition: null,
            withStructureTree: false,
        );

        $sql = $query->toSql();

        $this->assertStringNotContainsString('personnels.is_pending', $sql);
        $this->assertStringNotContainsString('`personnels`.`is_pending`', $sql);
    }

    public function test_current_status_query_excludes_pending_records(): void
    {
        $query = app(PersonnelQueryService::class)->build(
            status: 'current',
            filters: [],
            selectedStructureIds: [1],
            accessibleStructureIds: [1],
            selectedPosition: null,
            withStructureTree: false,
        );

        $sql = $query->toSql();

        $this->assertStringContainsString('leave_work_date', $sql);
        $this->assertStringContainsString('is_pending', $sql);
        $this->assertContains(false, $query->getBindings());
    }

    public function test_export_query_stays_lightweight(): void
    {
        $query = app(PersonnelQueryService::class)->buildExport(
            status: 'all',
            filters: [],
            selectedStructureIds: [1],
            accessibleStructureIds: [1],
            selectedPosition: null,
        );

        $this->assertSame([], $query->getEagerLoads());
        $this->assertNull($query->getQuery()->joins);
    }

    public function test_listing_query_eager_loads_active_travel_and_vacation_relations(): void
    {
        $query = app(PersonnelQueryService::class)->build(
            status: 'current',
            filters: [],
            selectedStructureIds: [1],
            accessibleStructureIds: [1],
            selectedPosition: null,
            withStructureTree: false,
        );

        $eagerLoads = array_keys($query->getEagerLoads());

        $this->assertContains('hasActiveVacation', $eagerLoads);
        $this->assertContains('hasActiveBusinessTrip', $eagerLoads);
    }

    public function test_state_normalizer_is_shared_and_stable(): void
    {
        $normalizer = app(PersonnelListStateNormalizer::class);

        $this->assertSame('current', $normalizer->normalizeStatus('bogus', ['current', 'all']));
        $this->assertSame([3, 5], $normalizer->normalizeStructure('3,5'));
        $this->assertSame(['foo' => 'bar'], $normalizer->normalizeFilters([
            '__identity' => 'x',
            'foo' => 'bar',
            'empty' => '',
        ]));
        $this->assertSame(7, $normalizer->normalizePosition('7'));
        $this->assertNull($normalizer->normalizePosition(''));
    }

    public function test_row_action_service_accepts_precomputed_capabilities(): void
    {
        $service = app(PersonnelRowActionService::class);
        $personnel = (new \App\Models\Personnel())->forceFill([
            'id' => 10,
            'tabel_no' => 'T-10',
        ]);

        $actions = $service->build($personnel, 'current', [
            'can_edit' => true,
            'can_delete' => true,
        ]);

        $this->assertNotEmpty($actions);
        $this->assertSame(['edit', 'files', 'print', 'cv', 'information', 'vacations', 'delete'], array_map(
            fn ($action) => $action->id,
            $actions
        ));
    }
}

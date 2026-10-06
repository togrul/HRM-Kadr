<?php

namespace Tests\Feature\Personnel;

use App\Models\Personnel;
use App\Models\SelfServiceApprovalRoute;
use App\Modules\Personnel\Application\Services\MyHr\ApprovalRouteResolverService;
use App\Modules\Personnel\Application\Services\MyHr\MyHrHierarchyReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pins how approvers are picked up the org line: nearest unit first, then lowest
 * approval_rank above the requester's, then id; inactive people, non-targets and the
 * requester themselves never qualify.
 */
class ApprovalRouteResolverHierarchyTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, Personnel> */
    private array $people = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedChart();
    }

    public function test_chain_walks_nearest_unit_first_then_rank_then_id(): void
    {
        $preview = app(ApprovalRouteResolverService::class)->preview($this->people['employee'], 'leave', 5);

        $this->assertSame(
            $this->ids('chief_a', 'chief_b', 'deputy', 'head', 'director'),
            array_column($preview['chain'], 'id'),
        );
        $this->assertSame('HQ / Department / Section', $preview['chain'][0]['structure']);
        $this->assertSame('HQ / Department', $preview['chain'][3]['structure']);
        $this->assertSame('HQ', $preview['chain'][4]['structure']);
        $this->assertSame($this->people['chief_a']->id, $preview['route']['approver_personnel_id']);
        $this->assertSame($this->people['chief_b']->id, $preview['route']['fallback_approver_personnel_id']);
        $this->assertSame('hierarchy_policy', $preview['route']['approval_route_source']);
    }

    public function test_manager_chain_climbs_by_rank(): void
    {
        $chain = app(ApprovalRouteResolverService::class)->managerChain($this->people['employee']);

        $this->assertSame($this->ids('chief_a', 'deputy', 'head', 'director'), array_column($chain, 'id'));
    }

    public function test_direct_reports_are_those_whose_approver_is_the_manager(): void
    {
        $resolver = app(ApprovalRouteResolverService::class);

        $this->assertSame($this->ids('deputy'), array_column($resolver->directReports($this->people['head']), 'id'));
        $this->assertSame($this->ids('employee'), array_column($resolver->directReports($this->people['chief_a']), 'id'));
        $this->assertSame($this->ids('head'), array_column($resolver->directReports($this->people['director']), 'id'));
    }

    public function test_top_of_the_chart_has_no_approver(): void
    {
        $route = app(ApprovalRouteResolverService::class)->resolve($this->people['director'], 'leave');

        $this->assertNull($route['approver_personnel_id']);
        $this->assertSame('hr_only_policy', $route['approval_route_source']);
    }

    public function test_hierarchy_payload_labels_units_with_the_root(): void
    {
        $payload = app(MyHrHierarchyReadService::class)->build($this->people['employee']);

        $this->assertSame('HQ / Department / Section', $payload['summary']['structure']);
        $this->assertSame('HQ / Department / Section', $payload['self']['structure']);
        $this->assertSame($this->people['chief_a']->id, $payload['summary']['manager']['id']);
    }

    public function test_resolving_the_chain_does_not_query_per_org_level(): void
    {
        $resolver = app(ApprovalRouteResolverService::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $resolver->preview($this->people['employee'], 'leave', 5);
        $resolver->directReports($this->people['head']);

        // Two subjects' positions, policy override, chart map, approver pool + positions,
        // report pool + positions — flat however deep the chart is (was 16 on three levels).
        $this->assertLessThanOrEqual(8, count(DB::getQueryLog()));
    }

    /**
     * @return list<int>
     */
    private function ids(string ...$keys): array
    {
        return array_map(fn (string $key): int => (int) $this->people[$key]->id, $keys);
    }

    private function seedChart(): void
    {
        DB::table('countries')->insert(['id' => 1, 'code' => 'AZ']);
        DB::table('education_degrees')->insert(['id' => 1, 'title_az' => 'Bakalavr', 'title_en' => 'Bachelor', 'title_ru' => 'Bachelor']);
        DB::table('work_norms')->insert(['id' => 1, 'name_az' => 'Tam iş günü', 'name_en' => 'Full time', 'name_ru' => 'Full time']);
        DB::table('structures')->insert([
            ['id' => 1, 'name' => 'HQ', 'shortname' => 'HQ', 'parent_id' => null, 'coefficient' => 1, 'code' => 10, 'level' => 1],
            ['id' => 2, 'name' => 'Department', 'shortname' => 'D', 'parent_id' => 1, 'coefficient' => 1, 'code' => 11, 'level' => 2],
            ['id' => 3, 'name' => 'Section', 'shortname' => 'S', 'parent_id' => 2, 'coefficient' => 1, 'code' => 12, 'level' => 3],
        ]);
        DB::table('positions')->insert([
            ['id' => 1, 'name' => 'Officer', 'approval_rank' => 10, 'is_approval_target' => false],
            ['id' => 2, 'name' => 'Section Chief', 'approval_rank' => 20, 'is_approval_target' => true],
            ['id' => 3, 'name' => 'Department Head', 'approval_rank' => 30, 'is_approval_target' => true],
            ['id' => 4, 'name' => 'Director', 'approval_rank' => 40, 'is_approval_target' => true],
            ['id' => 5, 'name' => 'Deputy', 'approval_rank' => 25, 'is_approval_target' => true],
            ['id' => 6, 'name' => 'Advisor', 'approval_rank' => 50, 'is_approval_target' => false],
        ]);

        // Created out of rank order so id ordering is observable.
        $this->people['director'] = $this->makePersonnel('Director', 1, 4);
        $this->people['head'] = $this->makePersonnel('Head', 2, 3);
        $this->people['deputy'] = $this->makePersonnel('Deputy', 3, 5);
        $this->people['chief_a'] = $this->makePersonnel('ChiefA', 3, 2);
        $this->people['chief_b'] = $this->makePersonnel('ChiefB', 3, 2);
        $this->people['employee'] = $this->makePersonnel('Employee', 3, 1);
        $this->people['advisor'] = $this->makePersonnel('Advisor', 3, 6);
        $this->people['retired'] = $this->makePersonnel('Retired', 3, 3, leftAt: '2026-01-01');

        SelfServiceApprovalRoute::query()->create([
            'request_type' => 'leave',
            'include_primary_approver' => true,
            'include_upper_approver' => true,
            'hr_always_included' => true,
            'is_active' => true,
            'created_by' => 1,
        ]);
    }

    private function makePersonnel(string $surname, int $structureId, int $positionId, ?string $leftAt = null): Personnel
    {
        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => $surname,
            'name' => 'N',
            'patronymic' => 'P',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'email' => Str::lower($surname).'@example.test',
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => $structureId,
            'position_id' => $positionId,
            'work_norm_id' => 1,
            'join_work_date' => '2020-03-01',
            'leave_work_date' => $leftAt,
            'added_by' => 1,
            'is_pending' => false,
        ]));
    }
}

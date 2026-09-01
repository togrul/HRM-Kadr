<?php

namespace Tests\Feature\Personnel;

use App\Models\User;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Services\PersonnelQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PersonnelListingCountsAndSearchTest extends TestCase
{
    use RefreshDatabase;

    private const STRUCTURE_ID = 5;

    public function test_status_counts_split_the_roster_by_state(): void
    {
        $this->seedRoster();

        $counts = $this->counts();

        // 1 at work, 1 on vacation, 1 resigned, 1 pending, 1 trashed.
        $this->assertSame(4, $counts['all']);       // trashed excluded
        $this->assertSame(2, $counts['current']);   // at work + on vacation
        $this->assertSame(1, $counts['leaves']);
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(1, $counts['deleted']);
        $this->assertSame(1, $counts['on_vacation']);
        $this->assertSame(1, $counts['at_work']);
    }

    public function test_header_totals_add_up_to_the_live_roster(): void
    {
        $this->seedRoster();

        $counts = $this->counts();

        $this->assertSame(
            $counts['all'],
            $counts['at_work'] + $counts['on_vacation'] + $counts['pending'] + $counts['leaves'],
        );
    }

    public function test_quick_search_matches_name_tabel_number_and_pin(): void
    {
        $this->seedRoster();

        $this->assertSame(['T-001'], $this->searchTabelNos('Məmmədov'));
        $this->assertSame(['T-003'], $this->searchTabelNos('T-003'));
        $this->assertSame(['T-002'], $this->searchTabelNos('PIN-002'));
        $this->assertSame([], $this->searchTabelNos('yoxdur'));
    }

    public function test_quick_search_narrows_the_counts_alongside_the_list(): void
    {
        $this->seedRoster();

        $counts = $this->counts('Məmmədov');

        $this->assertSame(1, $counts['all']);
        $this->assertSame(1, $counts['at_work']);
        $this->assertSame(0, $counts['on_vacation']);
    }

    public function test_a_wildcard_term_is_matched_literally(): void
    {
        $this->seedRoster();

        // '%' must not behave as "match everything".
        $this->assertSame([], $this->searchTabelNos('%'));
    }

    public function test_status_nav_is_rendered_inside_the_component_so_its_clicks_bind(): void
    {
        $this->seedRoster();

        $user = User::factory()->create();
        Permission::findOrCreate('show-personnels', 'web');
        $user->givePermissionTo('show-personnels');
        $this->actingAs($user);

        // Same guard as the profile: the panel must live in the component's own output,
        // not in the layout's sidebar slot, or setStatus() is unreachable from the UI.
        Livewire::test(AllPersonnel::class)
            ->assertSee('setStatus', escape: false)
            ->assertSee(__('personnel::common.labels.active'));
    }

    /**
     * @return array<string,int>
     */
    private function counts(string $search = ''): array
    {
        return app(PersonnelQueryService::class)->statusCounts(
            filters: [],
            selectedStructureIds: [],
            accessibleStructureIds: [self::STRUCTURE_ID],
            search: $search,
        );
    }

    /**
     * @return list<string>
     */
    private function searchTabelNos(string $search): array
    {
        return app(PersonnelQueryService::class)->build(
            status: 'all',
            filters: [],
            selectedStructureIds: [],
            accessibleStructureIds: [self::STRUCTURE_ID],
            withStructureTree: false,
            search: $search,
        )->pluck('personnels.tabel_no')->sort()->values()->all();
    }

    private function seedRoster(): void
    {
        $today = today();

        // tabel_no, surname, leave date, pending flag, soft-deleted
        $people = [
            ['T-001', 'Məmmədov', null, 0, false],
            ['T-002', 'Hüseynova', null, 0, false],   // on vacation below
            ['T-003', 'Əliyev', $today->copy()->subMonth()->toDateString(), 0, false],
            ['T-004', 'Quliyeva', null, 1, false],
            ['T-005', 'Səfərov', null, 0, true],
        ];

        foreach ($people as [$tabelNo, $surname, $leaveDate, $pending, $trashed]) {
            DB::table('personnels')->insert([
                'tabel_no' => $tabelNo,
                'surname' => $surname,
                'name' => 'Test',
                'patronymic' => 'Test oğlu',
                'birthdate' => '1990-01-01',
                'mobile' => '0500000000',
                'nationality_id' => 1,
                'pin' => 'PIN-'.substr($tabelNo, 2),
                'residental_address' => 'Baku',
                'education_degree_id' => 1,
                'structure_id' => self::STRUCTURE_ID,
                'position_id' => 1,
                'join_work_date' => '2020-01-01',
                'added_by' => 1,
                'work_norm_id' => 1,
                'leave_work_date' => $leaveDate,
                'is_pending' => $pending,
                'deleted_at' => $trashed ? $today->toDateTimeString() : null,
            ]);
        }

        DB::table('personnel_vacations')->insert([
            'tabel_no' => 'T-002',
            'vacation_places' => 'Baku',
            'duration' => 14,
            'start_date' => $today->copy()->subDays(3)->toDateString(),
            'end_date' => $today->copy()->addDays(10)->toDateString(),
            'return_work_date' => $today->copy()->addDays(11)->toDateString(),
            'order_given_by' => 'Komandir',
            'added_by' => 1,
        ]);
    }
}

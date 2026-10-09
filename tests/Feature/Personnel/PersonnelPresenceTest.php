<?php

namespace Tests\Feature\Personnel;

use App\Enums\OrderStatusEnum;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Personnel\Application\Services\PersonnelPresenceResolver;
use App\Modules\Personnel\Application\Services\PersonnelProfileReadService;
use App\Modules\Personnel\Livewire\AllPersonnel;
use App\Modules\Personnel\Livewire\Home;
use App\Modules\Personnel\Livewire\TablePanel;
use App\Modules\Personnel\Services\PersonnelQueryService;
use App\Modules\Personnel\Support\Presence\PersonnelPresenceStatus;
use App\Services\StructureService;
use App\Support\Database\InstalledTables;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * PersonnelPresenceResolver: işçinin bu günkü vəziyyəti (üstünlük sırası, qayıdış tarixi),
 * siyahıdakı "bu günkü vəziyyət" filtri və ana səhifədəki "Bu gün işdə yoxdur" paneli.
 */
class PersonnelPresenceTest extends TestCase
{
    use RefreshDatabase;

    /** Wednesday; Friday is 2026-10-09, Monday 2026-10-12. */
    private const TODAY = '2026-10-07';

    private int $sickType;

    private int $unpaidType;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(self::TODAY.' 10:00:00');
        CarbonImmutable::setTestNow(self::TODAY.' 10:00:00');

        $this->sickType = (int) DB::table('leave_types')->insertGetId(['name' => 'Xəstəlik', 'max_days' => 3]);
        $this->unpaidType = (int) DB::table('leave_types')->insertGetId(['name' => 'Ödənişsiz məzuniyyət', 'max_days' => 30]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_precedence_picks_one_status_per_person(): void
    {
        $sickOnVacation = $this->person('P-1');
        $this->leave('P-1', $this->sickType, '2026-10-06', '2026-10-08');
        $this->vacation('P-1', '2026-10-01', '2026-10-20', '2026-10-21');

        $vacationAndTrip = $this->person('P-2');
        $this->vacation('P-2', '2026-10-05', '2026-10-09', '2026-10-12');
        $this->trip('P-2', '2026-10-07', '2026-10-07');

        $tripAndLeave = $this->person('P-3');
        $this->trip('P-3', '2026-10-06', '2026-10-09');
        $this->leave('P-3', $this->unpaidType, '2026-10-07', '2026-10-07');

        $otherLeave = $this->person('P-4');
        $this->leave('P-4', $this->unpaidType, '2026-10-07', '2026-10-08');

        $hourly = $this->person('P-5');
        $this->leave('P-5', $this->sickType, '2026-10-07', '2026-10-07', unit: 'hour');

        $pending = $this->person('P-6', ['is_pending' => true]);
        $this->vacation('P-6', '2026-10-01', '2026-10-20', '2026-10-21');

        $dismissed = $this->person('P-7', ['leave_work_date' => '2026-09-30']);
        $this->leave('P-7', $this->sickType, '2026-10-06', '2026-10-08');

        $requestOnly = $this->person('P-8');
        $this->vacation('P-8', '2026-10-01', '2026-10-20', '2026-10-21', approval: 'pending');

        $deleted = $this->person('P-9', ['deleted_at' => now()]);

        $presences = app(PersonnelPresenceResolver::class)->resolveIds([
            $sickOnVacation->id, $vacationAndTrip->id, $tripAndLeave->id, $otherLeave->id,
            $hourly->id, $pending->id, $dismissed->id, $requestOnly->id, $deleted->id,
        ]);

        $this->assertSame(PersonnelPresenceStatus::Sick, $presences[$sickOnVacation->id]->status);
        $this->assertSame(PersonnelPresenceStatus::Vacation, $presences[$vacationAndTrip->id]->status);
        $this->assertSame(PersonnelPresenceStatus::BusinessTrip, $presences[$tripAndLeave->id]->status);
        $this->assertSame(PersonnelPresenceStatus::Leave, $presences[$otherLeave->id]->status);
        $this->assertSame('Ödənişsiz məzuniyyət', $presences[$otherLeave->id]->reason);
        $this->assertSame(PersonnelPresenceStatus::AtWork, $presences[$hourly->id]->status, 'an hourly permission must not flip the day');
        $this->assertSame(PersonnelPresenceStatus::Pending, $presences[$pending->id]->status);
        $this->assertSame(PersonnelPresenceStatus::Dismissed, $presences[$dismissed->id]->status);
        $this->assertSame(PersonnelPresenceStatus::AtWork, $presences[$requestOnly->id]->status, 'an unapproved request is not an absence');
        $this->assertSame(PersonnelPresenceStatus::Deleted, $presences[$deleted->id]->status);
    }

    public function test_sick_leave_is_recognised_by_attendance_code_as_well_as_by_name(): void
    {
        $coded = (int) DB::table('leave_types')->insertGetId(['name' => 'B/V', 'attendance_code' => 'XST', 'max_days' => 3]);
        $person = $this->person('P-1');
        $this->leave('P-1', $coded, '2026-10-07', '2026-10-07');

        $this->assertSame(PersonnelPresenceStatus::Sick, app(PersonnelPresenceResolver::class)->resolve($person)->status);
    }

    public function test_expected_return_is_the_next_working_day_on_the_calendar(): void
    {
        $friday = $this->person('P-1');
        $this->trip('P-1', '2026-10-06', '2026-10-09');

        $holidayMonday = $this->person('P-2');
        $this->leave('P-2', $this->sickType, '2026-10-06', '2026-10-16');
        DB::table('attendance_calendars')->insert(['date' => '2026-10-19', 'day_type' => 'holiday', 'scope_type' => 'global']);

        $movedSaturday = $this->person('P-3');
        $this->leave('P-3', $this->unpaidType, '2026-10-07', '2026-10-23');
        DB::table('attendance_calendars')->insert(['date' => '2026-10-24', 'day_type' => 'workday', 'scope_type' => 'global']);

        $recordedReturn = $this->person('P-4');
        $this->vacation('P-4', '2026-10-01', '2026-10-09', '2026-10-14');

        $presences = app(PersonnelPresenceResolver::class)->resolveIds([$friday->id, $holidayMonday->id, $movedSaturday->id, $recordedReturn->id]);

        $this->assertSame('12.10.2026', $presences[$friday->id]->expectedReturnLabel());
        $this->assertSame('06.10.2026 – 09.10.2026', $presences[$friday->id]->periodLabel());
        $this->assertSame('20.10.2026', $presences[$holidayMonday->id]->expectedReturnLabel());
        $this->assertSame('24.10.2026', $presences[$movedSaturday->id]->expectedReturnLabel());
        $this->assertSame('14.10.2026', $presences[$recordedReturn->id]->expectedReturnLabel(), 'HR-recorded return date wins');
    }

    public function test_sql_filter_selects_exactly_the_people_the_resolver_puts_in_each_status(): void
    {
        $this->seedMixedPopulation();

        $resolver = app(PersonnelPresenceResolver::class);
        $people = Personnel::query()->withTrashed()->get(['id', 'tabel_no', 'leave_work_date', 'is_pending', 'deleted_at']);
        $resolved = collect($resolver->resolveMany($people))->map(fn ($presence) => $presence->status->value);

        // The fixture must exercise every status, or the comparison below proves little.
        $this->assertEqualsCanonicalizing(
            array_map(fn (PersonnelPresenceStatus $status): string => $status->value, PersonnelPresenceStatus::precedence()),
            $resolved->unique()->values()->all(),
        );

        foreach (PersonnelPresenceStatus::precedence() as $status) {
            $expected = $resolved->filter(fn (string $value): bool => $value === $status->value)->keys()->sort()->values()->all();
            $actual = $resolver->constrainToStatuses(Personnel::query()->withTrashed(), [$status])->pluck('id')->sort()->values()->all();

            $this->assertSame($expected, $actual, "SQL and PHP disagree on [{$status->value}]");
        }

        $counts = $resolver->countByStatus(Personnel::query()->withTrashed(), PersonnelPresenceStatus::precedence());
        foreach ($counts as $status => $count) {
            $this->assertSame($resolved->filter(fn (string $value): bool => $value === $status)->count(), $count, "count of [{$status}]");
        }
    }

    public function test_a_page_of_people_resolves_in_a_constant_number_of_queries(): void
    {
        $this->seedMixedPopulation(30);
        InstalledTables::has('personnels');

        $resolver = app(PersonnelPresenceResolver::class);
        $five = Personnel::query()->orderBy('id')->limit(5)->get();
        $all = Personnel::query()->orderBy('id')->get();
        $this->assertGreaterThanOrEqual(20, $all->count());

        $small = $this->queriesDuring(fn () => $resolver->resolveMany($five));
        $large = $this->queriesDuring(fn () => $resolver->resolveMany($all));

        $this->assertLessThanOrEqual(4, count($large), implode("\n", $large));
        $this->assertSame(count($small), count($large), 'query count must not grow with the page size');
    }

    public function test_employee_list_renders_presence_and_filters_by_it_server_side(): void
    {
        $this->actingAsViewer(['show-personnels']);
        $this->person('S-1', ['surname' => 'Xəstəyev']);
        $this->leave('S-1', $this->sickType, '2026-10-06', '2026-10-09');
        $this->person('V-1', ['surname' => 'Məzuniyyətov']);
        $this->vacation('V-1', '2026-10-01', '2026-10-09', '2026-10-12');
        $this->person('W-1', ['surname' => 'İşləyən']);

        Livewire::test(TablePanel::class, ['status' => 'current'])
            ->assertSee('Xəstəyev')
            ->assertSee(__('personnel::common.presence.statuses.sick'))
            ->assertSee(__('personnel::common.presence.returns_on', ['date' => '12.10.2026']))
            ->assertSee(__('personnel::common.presence.statuses.vacation'));

        Livewire::test(TablePanel::class, ['status' => 'current', 'presence' => ['sick', 'bogus']])
            ->assertSet('presence', ['sick'])
            ->assertSee('Xəstəyev')
            ->assertDontSee('Məzuniyyətov')
            ->assertDontSee('İşləyən');

        Livewire::test(TablePanel::class, ['status' => 'current', 'presence' => ['vacation', 'at_work']])
            ->assertDontSee('Xəstəyev')
            ->assertSee('Məzuniyyətov')
            ->assertSee('İşləyən');

        Livewire::withQueryParams(['presence' => ['vacation']])
            ->test(AllPersonnel::class)
            ->assertSet('presence', ['vacation'])
            ->assertSeeHtml('personnel-presence-filter')
            ->set('presence', ['sick', 'nope'])
            ->assertSet('presence', ['sick']);

        $counts = app(PersonnelQueryService::class)->statusCounts([], [], [1]);
        $this->assertSame(1, $counts['on_vacation']);
        $this->assertSame(1, $counts['at_work']);
    }

    public function test_profile_header_uses_the_resolver(): void
    {
        $person = $this->person('P-1');
        $this->leave('P-1', $this->sickType, '2026-10-06', '2026-10-09');

        $reader = app(PersonnelProfileReadService::class);

        $this->assertSame('teal', $reader->statusTone($person));
        $this->assertSame(__('personnel::common.presence.statuses.sick'), $reader->statusLabel($person));
        $this->assertSame('12.10.2026', $reader->presence($person)->expectedReturnLabel());

        $atWork = $this->person('P-2');
        $this->assertSame('neutral', $reader->statusTone($atWork));
    }

    public function test_home_lists_who_is_away_today_with_return_dates_and_overflow(): void
    {
        $this->actingAsViewer(['show-personnels']);

        foreach (range(1, 8) as $index) {
            $this->person("A-{$index}", ['surname' => sprintf('Yoxdur%02d', $index)]);
            $this->vacation("A-{$index}", '2026-10-01', '2026-10-09', '2026-10-12');
        }
        $this->person('S-1', ['surname' => 'Aaxəstə']);
        $this->leave('S-1', $this->sickType, '2026-10-06', '2026-10-07');
        $this->person('W-1', ['surname' => 'Burada']);
        $this->person('X-1', ['surname' => 'Başqaidarə', 'structure_id' => 2]);
        $this->leave('X-1', $this->sickType, '2026-10-06', '2026-10-07');

        $absent = Livewire::test(Home::class)->instance()->absentToday;

        $this->assertSame(9, $absent['total'], 'people outside the viewer structures are left out');
        $this->assertSame(['sick' => 1, 'vacation' => 8], $absent['counts']);
        $this->assertCount(6, $absent['rows']);
        $this->assertSame(3, $absent['more']);
        $this->assertSame('08.10.2026', $absent['rows'][0]['returns']);

        $html = $this->loadIsland(Livewire::test(Home::class), 'home-absent-today');

        $this->assertStringContainsString(__('personnel::home.absent.title'), $html);
        $this->assertStringContainsString(__('personnel::home.absent.more', ['count' => 3]), $html);
        $this->assertStringContainsString(__('personnel::home.absent.returns', ['date' => '12.10.2026']), $html);
        $this->assertStringContainsString(route('personnel.show', $absent['rows'][0]['id']), $html);
        $this->assertStringNotContainsString('Burada', $html);
        $this->assertStringNotContainsString('Başqaidarə', $html);
    }

    public function test_home_says_everyone_is_at_work_when_nobody_is_away(): void
    {
        $this->actingAsViewer(['show-personnels']);
        $this->person('W-1');

        $html = $this->loadIsland(Livewire::test(Home::class), 'home-absent-today');

        $this->assertStringContainsString(__('personnel::home.absent.empty'), $html);
    }

    public function test_home_absent_panel_is_not_registered_without_the_personnel_permission(): void
    {
        $this->actingAsViewer(['show-orders']);

        Livewire::test(Home::class)->assertDontSeeHtml('name=home-absent-today');
        $this->assertSame(0, app(\App\Modules\Personnel\Application\Services\HomeOverviewService::class)->absentToday(auth()->user())['total']);
    }

    private function seedMixedPopulation(int $size = 24): void
    {
        foreach (range(1, $size) as $index) {
            $tabelNo = sprintf('M-%03d', $index);
            $overrides = match ($index % 9) {
                1 => ['is_pending' => true],
                2 => ['leave_work_date' => '2026-01-01'],
                3 => ['deleted_at' => now()],
                default => [],
            };
            $this->person($tabelNo, $overrides);

            match ($index % 6) {
                0 => $this->leave($tabelNo, $this->sickType, '2026-10-06', '2026-10-08'),
                1 => $this->vacation($tabelNo, '2026-10-01', '2026-10-09', '2026-10-12'),
                2 => $this->trip($tabelNo, '2026-10-07', '2026-10-07'),
                3 => $this->leave($tabelNo, $this->unpaidType, '2026-10-07', '2026-10-07'),
                4 => $this->leave($tabelNo, $this->sickType, '2026-10-07', '2026-10-07', unit: 'half_day'),
                default => null,
            };

            if ($index % 4 === 0) {
                $this->vacation($tabelNo, '2026-10-05', '2026-10-10', '2026-10-13');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function person(string $tabelNo, array $overrides = []): Personnel
    {
        return Personnel::withoutEvents(fn (): Personnel => Personnel::query()->forceCreate([
            'tabel_no' => $tabelNo,
            'surname' => $overrides['surname'] ?? 'Soyad'.$tabelNo,
            'name' => 'Ad',
            'patronymic' => 'Ata',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '0501112233',
            'nationality_id' => 1,
            'pin' => strtoupper(substr(md5($tabelNo), 0, 7)),
            'residental_address' => 'Bakı',
            'education_degree_id' => 1,
            'structure_id' => $overrides['structure_id'] ?? 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2021-01-01',
            'leave_work_date' => $overrides['leave_work_date'] ?? null,
            'added_by' => 1,
            'is_pending' => $overrides['is_pending'] ?? false,
            'deleted_at' => $overrides['deleted_at'] ?? null,
        ]));
    }

    private function leave(string $tabelNo, int $typeId, string $from, string $to, string $unit = 'day'): void
    {
        DB::table('leaves')->insert([
            'tabel_no' => $tabelNo,
            'leave_type_id' => $typeId,
            'starts_at' => $from,
            'ends_at' => $to,
            'duration_unit' => $unit,
            'status_id' => OrderStatusEnum::APPROVED->value,
            'total_days' => 1,
        ]);
    }

    private function vacation(string $tabelNo, string $from, string $to, string $return, ?string $approval = null): void
    {
        DB::table('personnel_vacations')->insert([
            'tabel_no' => $tabelNo,
            'vacation_places' => 'Bakı',
            'duration' => 5,
            'start_date' => $from,
            'end_date' => $to,
            'return_work_date' => $return,
            'order_given_by' => 'HR',
            'added_by' => 1,
            'approval_status' => $approval,
        ]);
    }

    private function trip(string $tabelNo, string $from, string $to): void
    {
        DB::table('personnel_business_trips')->insert([
            'tabel_no' => $tabelNo,
            'location' => 'Gəncə',
            'start_date' => $from,
            'end_date' => $to,
            'order_given_by' => 'HR',
            'added_by' => 1,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actingAsViewer(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->actingAs($user);
        Livewire::actingAs($user);
        $this->mock(StructureService::class, fn ($mock) => $mock->shouldReceive('getAccessibleStructures')->andReturn([1]));

        return $user;
    }

    private function loadIsland(Testable $component, string $island): string
    {
        $component->update(calls: [[
            'method' => '__lazyLoadIsland',
            'params' => [],
            'path' => '',
            'metadata' => ['island' => ['name' => $island, 'mode' => 'morph']],
        ]]);

        return implode('', $component->effects['islandFragments'] ?? []);
    }

    /**
     * @return list<string>
     */
    private function queriesDuring(callable $callback): array
    {
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $callback();

        app('events')->forget(QueryExecuted::class);

        return $queries;
    }
}

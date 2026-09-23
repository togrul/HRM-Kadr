<?php

namespace Tests\Feature\Personnel;

use App\Models\AttendanceDailyStructureSummary;
use App\Models\AttendanceManualEntry;
use App\Models\OrderLog;
use App\Models\PersonnelVacation;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Orders\Infrastructure\Document\OrderIssueService;
use App\Modules\Personnel\Livewire\Home;
use App\Support\Database\InstalledTables;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    /** Every permission that unlocks a home block. */
    private const ALL_BLOCKS = [
        'show-attendance-manual',
        'show-orders',
        'show-vacations',
        'show-document-compliance',
        'show-personnels',
        'show-attendance',
        'show-audit-logs',
        'show-staff',
    ];

    public function test_home_renders_without_any_permission_and_exposes_no_blocks(): void
    {
        $this->actingAs(User::factory()->create());

        $home = Livewire::test(Home::class)->assertOk()->instance();

        $this->assertSame([], $home->attention);
        $this->assertSame([], $home->attendanceWeek);
        $this->assertSame([], $home->activity);
        $this->assertSame([], $home->structureFill);
    }

    public function test_attention_tiles_count_pending_work_across_modules(): void
    {
        // Signed in first: PersonnelVacation stamps added_by from the current user.
        $this->actingAsViewer([
            'show-attendance-manual',
            'show-orders',
            'show-vacations',
            'show-document-compliance',
        ]);
        $this->seedPendingWork();

        $counts = collect(Livewire::test(Home::class)->instance()->attention)
            ->pluck('count', 'key');

        $this->assertSame(2, $counts['attendance_pending']);
        $this->assertSame(1, $counts['unsigned_orders']);
        $this->assertSame(1, $counts['vacation_requests']);
        $this->assertSame(1, $counts['expiring_documents']);
    }

    public function test_today_rail_lists_queues_birthdays_and_upcoming_leaves(): void
    {
        $this->actingAsViewer([
            'show-attendance-manual',
            'show-orders',
            'show-personnels',
            'show-vacations',
            'add-personnels',
        ]);
        $this->seedPendingWork();

        DB::table('personnels')
            ->where('tabel_no', 'T-1001')
            ->update(['birthdate' => today()->subYears(30)->toDateString()]);

        // Approved and starting inside the week — the one the rail is meant to surface.
        PersonnelVacation::query()->create([
            'tabel_no' => 'T-1001',
            'vacation_places' => 'Baku',
            'duration' => 5,
            'start_date' => today()->addDays(2)->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(),
            'return_work_date' => today()->addDays(8)->toDateString(),
            'order_given_by' => 'Komandir',
            'added_by' => 1,
            'approval_status' => 'approved',
        ]);

        $component = Livewire::test(Home::class)->assertOk();
        $today = collect($component->instance()->today)->keyBy('key');

        $this->assertSame(2, $today['attendance_pending']['count']);
        $this->assertSame(1, $today['unsigned_orders']['count']);
        $this->assertSame(1, $today['birthdays']['count']);
        $this->assertSame(1, $today['vacations_starting']['count']);

        // The quick-action rail is permission-gated, so its presence is part of the contract.
        $component->assertSee(__('personnel::home.quick.new_employee'));
    }

    public function test_attention_tiles_report_how_long_the_oldest_item_has_waited(): void
    {
        $this->actingAsViewer(['show-orders']);
        $this->seedPendingWork();

        OrderLog::query()->where('order_no', 'A-1')->update(['created_at' => now()->subDays(4)]);

        $tile = collect(Livewire::test(Home::class)->instance()->attention)->firstWhere('key', 'unsigned_orders');

        $this->assertSame(1, $tile['count']);
        $this->assertSame(4, $tile['oldest_days']);
    }

    public function test_weekly_attendance_always_returns_seven_days_with_today_last(): void
    {
        $structure = Structure::factory()->create(['id' => 5, 'name' => 'Baş idarə', 'shortname' => 'Bİ']);

        AttendanceDailyStructureSummary::query()->create([
            'date' => today()->toDateString(),
            'structure_id' => $structure->id,
            'ledger_rows' => 10,
            'scheduled_days' => 10,
            'present_days' => 8,
            'absence_days' => 2,
            'compliant_days' => 8,
            'scheduled_minutes_sum' => 4800,
            'worked_minutes_sum' => 3840,
            'overtime_minutes_sum' => 0,
            'late_minutes_sum' => 0,
            'early_leave_minutes_sum' => 0,
        ]);

        $this->actingAsViewer(['show-attendance']);

        $week = Livewire::test(Home::class)->instance()->attendanceWeek;

        $this->assertCount(7, $week);
        $this->assertSame(today()->toDateString(), end($week)['date']);
        $this->assertSame(8, end($week)['present']);
        $this->assertSame(2, end($week)['absent']);
        $this->assertSame(0, $week[0]['present']);
    }

    public function test_structure_fill_reports_coverage_percentage(): void
    {
        $structure = Structure::factory()->create(['id' => 5, 'name' => 'Baş idarə', 'shortname' => 'Bİ']);

        StaffSchedule::query()->create([
            'structure_id' => $structure->id,
            'position_id' => 1,
            'total' => 8,
            'filled' => 6,
            'vacant' => 2,
        ]);
        StaffSchedule::query()->create([
            'structure_id' => $structure->id,
            'position_id' => 2,
            'total' => 2,
            'filled' => 1,
            'vacant' => 1,
        ]);

        $this->actingAsViewer(['show-staff']);

        $row = collect(Livewire::test(Home::class)->instance()->structureFill)->firstWhere('id', $structure->id);

        $this->assertSame(10, $row['total']);
        $this->assertSame(7, $row['filled']);
        $this->assertSame(3, $row['vacant']);
        $this->assertSame(70, $row['pct']);
    }

    public function test_first_paint_reads_only_the_above_the_fold_blocks(): void
    {
        $this->actingAsViewer(self::ALL_BLOCKS);
        $this->seedPendingWork();
        $this->seedBelowTheFold();

        // Warm the per-request caches every page shares (permissions, table listing)
        // so the count below is the home page's own reads.
        auth()->user()->getAllPermissions();
        InstalledTables::has('personnels');

        $cold = $this->queriesDuring(fn () => Livewire::test(Home::class)->assertOk());
        $warm = $this->queriesDuring(fn () => Livewire::test(Home::class)->assertOk());

        // 3 queue tiles + 1 expiring-documents query + birthdays + leaves starting.
        $this->assertCount(6, $cold, implode("\n", $cold));
        // Birthdays and leaves starting now come from cache; the queues stay live.
        $this->assertCount(4, $warm, implode("\n", $warm));

        foreach (['attendance_daily_structure_summaries', 'activity_log', 'staff_schedules'] as $lazyTable) {
            $this->assertEmpty(preg_grep('/'.$lazyTable.'/', $cold), "{$lazyTable} was read on first paint");
        }
    }

    public function test_heavy_blocks_render_as_lazy_island_placeholders_and_load_on_request(): void
    {
        $this->actingAsViewer(self::ALL_BLOCKS);
        $this->seedBelowTheFold();

        $component = Livewire::test(Home::class)
            ->assertSeeHtml('name=home-attendance-week')
            ->assertSeeHtml('name=home-activity')
            ->assertSeeHtml('name=home-structure-fill')
            ->assertSeeHtml('wire:intersect.once="__lazyLoadIsland"')
            ->assertDontSee(__('personnel::home.structure.title'));

        $fragments = $this->loadIsland($component, 'home-structure-fill');

        $this->assertStringContainsString(__('personnel::home.structure.title'), $fragments);
        $this->assertStringContainsString('Baş idarə', $fragments);
        $this->assertStringContainsString('6/8 · 75%', $fragments);

        $activity = $this->loadIsland(Livewire::test(Home::class), 'home-activity');
        $this->assertStringContainsString(__('personnel::home.activity.title'), $activity);

        $attendance = $this->loadIsland(Livewire::test(Home::class), 'home-attendance-week');
        $this->assertStringContainsString(__('personnel::home.attendance.title'), $attendance);
        $this->assertStringContainsString('80%', $attendance);
    }

    public function test_islands_the_viewer_cannot_see_are_never_registered(): void
    {
        $this->actingAsViewer(['show-orders']);

        $component = Livewire::test(Home::class)
            ->assertDontSeeHtml('name=home-attendance-week')
            ->assertDontSeeHtml('name=home-activity')
            ->assertDontSeeHtml('name=home-structure-fill');

        $this->assertSame('', $this->loadIsland($component, 'home-activity'));
    }

    public function test_pending_queues_stay_live_while_informational_blocks_are_cached(): void
    {
        $this->actingAsViewer(self::ALL_BLOCKS);
        $this->seedPendingWork();
        $this->seedBelowTheFold();

        $home = Livewire::test(Home::class)->instance();
        $this->assertSame(2, collect($home->attention)->firstWhere('key', 'attendance_pending')['count']);
        $this->assertSame(8, $home->structureFill[0]['total']);

        // The viewer approves an entry and HR adds a position elsewhere.
        AttendanceManualEntry::query()->where('approval_status', 'pending')->limit(1)->update(['approval_status' => 'approved']);
        StaffSchedule::query()->create(['structure_id' => 5, 'position_id' => 9, 'total' => 2, 'filled' => 0, 'vacant' => 2]);

        $home = Livewire::test(Home::class)->instance();
        $this->assertSame(1, collect($home->attention)->firstWhere('key', 'attendance_pending')['count']);
        $this->assertSame(1, collect($home->today)->firstWhere('key', 'attendance_pending')['count']);
        $this->assertSame(8, $home->structureFill[0]['total'], 'structure coverage is served from its short cache');

        Cache::flush();

        $this->assertSame(10, Livewire::test(Home::class)->instance()->structureFill[0]['total']);
    }

    private function seedBelowTheFold(): void
    {
        Structure::factory()->create(['id' => 5, 'name' => 'Baş idarə', 'shortname' => 'Bİ']);
        StaffSchedule::query()->create(['structure_id' => 5, 'position_id' => 1, 'total' => 8, 'filled' => 6, 'vacant' => 2]);
        AttendanceDailyStructureSummary::query()->create([
            'date' => today()->toDateString(),
            'structure_id' => 5,
            'ledger_rows' => 10,
            'scheduled_days' => 10,
            'present_days' => 8,
            'absence_days' => 2,
            'compliant_days' => 8,
            'scheduled_minutes_sum' => 4800,
            'worked_minutes_sum' => 3840,
            'overtime_minutes_sum' => 0,
            'late_minutes_sum' => 0,
            'early_leave_minutes_sum' => 0,
        ]);
        activity()->causedBy(auth()->user())->log('home-probe');
    }

    /**
     * Replays the request the browser sends when a lazy island scrolls into view.
     */
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

        DB::flushQueryLog();
        app('events')->forget(QueryExecuted::class);

        return $queries;
    }

    private function seedPendingWork(): void
    {
        // Inserted straight into the table: the counters only need a live personnel
        // row to join against, not the model's observers and role provisioning.
        $tabelNo = 'T-1001';
        DB::table('personnels')->insert([
            'tabel_no' => $tabelNo,
            'surname' => 'Test',
            'name' => 'Personnel',
            'patronymic' => 'Home',
            'birthdate' => '1990-01-01',
            'mobile' => '0500000001',
            'nationality_id' => 1,
            'pin' => 'PIN1001',
            'residental_address' => 'Baku',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'join_work_date' => '2020-01-01',
            'added_by' => 1,
            'work_norm_id' => 1,
        ]);

        foreach (['pending', 'pending', 'approved'] as $index => $status) {
            AttendanceManualEntry::query()->create([
                'tabel_no' => $tabelNo,
                'date' => today()->subDays($index)->toDateString(),
                'worked_minutes' => 480,
                'entered_by' => 1,
                'approval_status' => $status,
            ]);
        }

        OrderLog::query()->create([
            'order_id' => 1,
            'order_no' => 'A-1',
            'given_date' => '2026-08-01',
            'given_by' => 'Komandir',
            'given_by_rank' => 'Polkovnik',
            'status_id' => OrderIssueService::STATUS_PENDING,
        ]);
        OrderLog::query()->create([
            'order_id' => 1,
            'order_no' => 'A-2',
            'given_date' => '2026-08-01',
            'given_by' => 'Komandir',
            'given_by_rank' => 'Polkovnik',
            'status_id' => 20,
        ]);

        foreach (['pending', 'approved'] as $index => $status) {
            PersonnelVacation::query()->create([
                'tabel_no' => $tabelNo,
                'vacation_places' => 'Baku',
                'duration' => 7,
                'start_date' => today()->addWeeks($index + 1)->toDateString(),
                'end_date' => today()->addWeeks($index + 2)->toDateString(),
                'return_work_date' => today()->addWeeks($index + 2)->addDay()->toDateString(),
                'order_given_by' => 'Komandir',
                'added_by' => 1,
                'approval_status' => $status,
            ]);
        }

        // One card expires inside the 30-day window, one comfortably outside it.
        DB::table('personnel_cards')->insert([
            ['tabel_no' => $tabelNo, 'card_number' => 'C-1', 'valid_date' => today()->addDays(10)->toDateString()],
            ['tabel_no' => $tabelNo, 'card_number' => 'C-2', 'valid_date' => today()->addYear()->toDateString()],
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actingAsViewer(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }
}

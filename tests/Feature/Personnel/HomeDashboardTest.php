<?php

namespace Tests\Feature\Personnel;

use App\Models\AttendanceDailyStructureSummary;
use App\Models\AttendanceManualEntry;
use App\Models\OrderLog;
use App\Models\PersonnelVacation;
use App\Models\StaffSchedule;
use App\Models\Structure;
use App\Models\User;
use App\Modules\Personnel\Livewire\Home;
use App\Services\Orders\Document\OrderIssueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HomeDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_without_any_permission_and_exposes_no_blocks(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = Livewire::test(Home::class)->assertOk()->instance()->payload;

        $this->assertSame([], $payload['attention']);
        $this->assertSame([], $payload['attendance_week']);
        $this->assertSame([], $payload['activity']);
        $this->assertSame([], $payload['structure_fill']);
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

        $counts = collect(Livewire::test(Home::class)->instance()->payload['attention'])
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
        $today = collect($component->instance()->payload['today'])->keyBy('key');

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

        $tile = collect(Livewire::test(Home::class)->instance()->payload['attention'])->firstWhere('key', 'unsigned_orders');

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

        $week = Livewire::test(Home::class)->instance()->payload['attendance_week'];

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

        $row = collect(Livewire::test(Home::class)->instance()->payload['structure_fill'])->firstWhere('id', $structure->id);

        $this->assertSame(10, $row['total']);
        $this->assertSame(7, $row['filled']);
        $this->assertSame(3, $row['vacant']);
        $this->assertSame(70, $row['pct']);
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

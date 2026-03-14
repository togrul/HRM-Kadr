<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceDailyStructureSummary;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceDailyMonitorReadService;
use App\Modules\Attendance\Application\Services\AttendanceOverviewService;
use App\Modules\Attendance\Livewire\AttendanceManagerSummary;
use App\Modules\Attendance\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceReadModelRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_monitor_includes_personnel_with_future_leave_work_date(): void
    {
        $activeWithinDay = $this->makePersonnel([
            'name' => 'Future',
            'surname' => 'Leave',
            'join_work_date' => '2026-01-01',
            'leave_work_date' => '2026-03-20',
        ]);

        $inactiveBeforeDay = $this->makePersonnel([
            'name' => 'Past',
            'surname' => 'Leave',
            'join_work_date' => '2025-01-01',
            'leave_work_date' => '2026-03-05',
        ]);

        $rows = app(AttendanceDailyMonitorReadService::class)
            ->paginateRows('2026-03-10', '', 'all', 50)
            ->getCollection()
            ->pluck('tabel_no')
            ->all();

        $this->assertContains($activeWithinDay->tabel_no, $rows);
        $this->assertNotContains($inactiveBeforeDay->tabel_no, $rows);
    }

    public function test_dashboard_overview_aggregates_selected_structure_tree(): void
    {
        $user = $this->userWithPermissions(['show-attendance']);

        $parent = Structure::query()->create([
            'name' => 'Main Campus',
            'shortname' => 'MAIN',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 100,
            'level' => 1,
        ]);

        $child = Structure::query()->create([
            'name' => 'Faculty',
            'shortname' => 'FAC',
            'parent_id' => $parent->id,
            'coefficient' => 1.10,
            'code' => 101,
            'level' => 2,
        ]);

        AttendanceDailyStructureSummary::query()->create([
            'date' => '2026-03-03',
            'structure_id' => $parent->id,
            'ledger_rows' => 1,
            'scheduled_days' => 1,
            'present_days' => 1,
            'absence_days' => 0,
            'compliant_days' => 1,
            'scheduled_minutes_sum' => 60,
            'worked_minutes_sum' => 60,
            'overtime_minutes_sum' => 0,
            'late_minutes_sum' => 0,
            'early_leave_minutes_sum' => 0,
        ]);

        AttendanceDailyStructureSummary::query()->create([
            'date' => '2026-03-03',
            'structure_id' => $child->id,
            'ledger_rows' => 1,
            'scheduled_days' => 1,
            'present_days' => 1,
            'absence_days' => 0,
            'compliant_days' => 1,
            'scheduled_minutes_sum' => 120,
            'worked_minutes_sum' => 120,
            'overtime_minutes_sum' => 0,
            'late_minutes_sum' => 0,
            'early_leave_minutes_sum' => 0,
        ]);

        $this->actingAs($user);

        Livewire::withQueryParams(['year' => 2026, 'month' => 3])
            ->test(Dashboard::class)
            ->call('selectStructure', $parent->id)
            ->assertSet('overview.scheduled_minutes', 180)
            ->assertSet('overview.worked_minutes', 180);
    }

    public function test_overview_skips_ledger_queries_when_structure_summary_exists(): void
    {
        AttendanceDailyStructureSummary::query()->create([
            'date' => '2026-03-03',
            'structure_id' => 1,
            'ledger_rows' => 1,
            'scheduled_days' => 1,
            'present_days' => 1,
            'absence_days' => 0,
            'compliant_days' => 1,
            'scheduled_minutes_sum' => 120,
            'worked_minutes_sum' => 120,
            'overtime_minutes_sum' => 15,
            'late_minutes_sum' => 0,
            'early_leave_minutes_sum' => 0,
        ]);

        AttendanceDailyStructureSummary::query()->create([
            'date' => '2026-02-03',
            'structure_id' => 1,
            'ledger_rows' => 1,
            'scheduled_days' => 1,
            'present_days' => 1,
            'absence_days' => 0,
            'compliant_days' => 1,
            'scheduled_minutes_sum' => 120,
            'worked_minutes_sum' => 120,
            'overtime_minutes_sum' => 0,
            'late_minutes_sum' => 0,
            'early_leave_minutes_sum' => 0,
        ]);

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        app(AttendanceOverviewService::class)->build(2026, 3, 1, false, [1]);

        $queries = collect(DB::connection()->getQueryLog())->pluck('query');

        $this->assertFalse(
            $queries->contains(fn (string $query) => str_contains($query, 'attendance_daily_ledgers')),
            'Overview should not hit attendance_daily_ledgers when structure summaries exist for current and previous month.'
        );
    }

    public function test_manager_summary_keeps_cached_totals_when_search_changes(): void
    {
        $user = $this->userWithPermissions(['show-attendance-manager-summary']);

        $personnel = $this->makePersonnel([
            'surname' => 'Məmmədov',
            'name' => 'Elvin',
            'patronymic' => 'Rəşad',
        ]);

        AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-03',
            'scheduled_minutes' => 540,
            'worked_minutes' => 520,
            'break_minutes' => 40,
            'overtime_minutes' => 30,
            'late_minutes' => 15,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
            'source_summary' => 'system',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(AttendanceManagerSummary::class, ['year' => 2026, 'month' => 3]);

        DB::connection()->flushQueryLog();
        DB::connection()->enableQueryLog();

        $component->set('search', 'Elvin');

        $queries = collect(DB::connection()->getQueryLog())->pluck('query');

        $this->assertTrue($queries->isNotEmpty());
        $this->assertFalse(
            $queries->contains(fn (string $query) => str_contains($query, 'personnel_count')),
            'Changing manager summary search should not rerun totals aggregation.'
        );
    }

    /**
     * @param  array<int,string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Regression Role '.Str::random(4),
            'guard_name' => 'web',
        ]);

        $role->syncPermissions(
            collect($permissions)->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'))
        );

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makePersonnel(array $overrides = []): Personnel
    {
        $user = User::query()->first() ?? User::factory()->create();

        $country = Country::query()->first() ?? Country::query()->create([
            'id' => 1,
            'code' => 'AZ',
        ]);

        if (! EducationDegree::query()->whereKey(1)->exists()) {
            EducationDegree::query()->create([
                'id' => 1,
                'title_az' => 'Bakalavr',
                'title_en' => 'Bachelor',
                'title_ru' => 'Bakalavr',
            ]);
        }

        if (! WorkNorm::query()->whereKey(1)->exists()) {
            WorkNorm::query()->create([
                'id' => 1,
                'name_az' => 'Tam',
                'name_en' => 'Full',
                'name_ru' => 'Polniy',
            ]);
        }

        $structure = Structure::query()->first() ?? Structure::query()->create([
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);

        $position = Position::query()->first() ?? Position::query()->create([
            'id' => 1,
            'name' => 'Officer',
        ]);

        $payload = array_merge([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'John',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => $country->id,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => $structure->id,
            'position_id' => $position->id,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => $user->id,
            'is_pending' => false,
        ], $overrides);

        return Personnel::withoutEvents(fn () => Personnel::query()->create($payload));
    }
}

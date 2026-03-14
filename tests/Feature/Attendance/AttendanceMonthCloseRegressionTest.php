<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceMonthlySummary;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Application\Services\AttendanceMonthLockService;
use App\Modules\Attendance\Livewire\MonthClose;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceMonthCloseRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_is_blocked_until_snapshot_exists(): void
    {
        $user = $this->userWithPermissions(['show-attendance-month-close', 'export-attendance']);

        $personnel = $this->makePersonnel();

        AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-03',
            'scheduled_minutes' => 540,
            'worked_minutes' => 520,
            'break_minutes' => 40,
            'overtime_minutes' => 20,
            'late_minutes' => 10,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
            'source_summary' => 'system',
        ]);

        $this->actingAs($user);

        Livewire::test(MonthClose::class, ['year' => 2026, 'month' => 3])
            ->call('exportPayroll')
            ->assertDispatched('notify', type: 'error', message: __('attendance::month_close.messages.export_requires_snapshot'));
    }

    public function test_export_is_blocked_when_snapshot_is_stale(): void
    {
        $user = $this->userWithPermissions(['show-attendance-month-close', 'export-attendance']);

        $personnel = $this->makePersonnel();

        $ledger = AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-03',
            'scheduled_minutes' => 540,
            'worked_minutes' => 520,
            'break_minutes' => 40,
            'overtime_minutes' => 20,
            'late_minutes' => 10,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
            'source_summary' => 'system',
        ]);
        $ledger->forceFill(['updated_at' => now()])->saveQuietly();

        AttendanceMonthlySummary::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'year' => 2026,
            'month' => 3,
            'total_scheduled_minutes' => 540,
            'total_worked_minutes' => 500,
            'total_overtime_minutes' => 0,
            'total_absence_minutes' => 0,
            'total_workdays' => 1,
            'total_present_days' => 1,
            'total_absence_days' => 0,
            'is_locked' => false,
            'calculated_at' => now()->subDay(),
        ]);

        $this->actingAs($user);

        Livewire::test(MonthClose::class, ['year' => 2026, 'month' => 3])
            ->call('exportPayrollCsv')
            ->assertDispatched('notify', type: 'error', message: __('attendance::month_close.messages.export_requires_fresh_snapshot'));
    }

    public function test_snapshot_month_uses_bulk_upsert_and_deletes_stale_rows(): void
    {
        $personnel = $this->makePersonnel([
            'tabel_no' => 'TBNEW001',
        ]);

        AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-03',
            'scheduled_minutes' => 540,
            'worked_minutes' => 520,
            'break_minutes' => 40,
            'overtime_minutes' => 20,
            'late_minutes' => 10,
            'early_leave_minutes' => 0,
            'attendance_status' => 'present',
            'source_summary' => 'system',
        ]);

        AttendanceMonthlySummary::query()->create([
            'tabel_no' => 'TBOLD001',
            'year' => 2026,
            'month' => 3,
            'total_scheduled_minutes' => 540,
            'total_worked_minutes' => 540,
            'total_overtime_minutes' => 0,
            'total_absence_minutes' => 0,
            'total_workdays' => 1,
            'total_present_days' => 1,
            'total_absence_days' => 0,
            'is_locked' => false,
            'calculated_at' => now()->subDay(),
        ]);

        $stats = app(AttendanceMonthLockService::class)->snapshotMonth(2026, 3, false);

        $this->assertSame(1, $stats['summary_upserts']);
        $this->assertSame(1, $stats['deleted_stale_summaries']);
        $this->assertDatabaseHas('attendance_monthly_summaries', [
            'tabel_no' => 'TBNEW001',
            'year' => 2026,
            'month' => 3,
        ]);
        $this->assertDatabaseMissing('attendance_monthly_summaries', [
            'tabel_no' => 'TBOLD001',
            'year' => 2026,
            'month' => 3,
        ]);
    }

    /**
     * @param  array<int,string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Month Close Role '.Str::random(4),
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

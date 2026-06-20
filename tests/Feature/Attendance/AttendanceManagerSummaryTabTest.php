<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceDailyLedger;
use App\Models\AttendanceException;
use App\Models\Country;
use App\Models\EducationDegree;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Structure;
use App\Models\User;
use App\Models\WorkNorm;
use App\Modules\Attendance\Livewire\AttendanceManagerSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceManagerSummaryTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_summary_shows_monthly_personnel_rollup(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Summary Manager',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('show-attendance-manager-summary', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

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

        AttendanceDailyLedger::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-04',
            'scheduled_minutes' => 540,
            'worked_minutes' => 0,
            'break_minutes' => 0,
            'overtime_minutes' => 0,
            'late_minutes' => 0,
            'early_leave_minutes' => 0,
            'attendance_status' => 'absent',
            'absence_code' => 'ABS',
            'source_summary' => 'system',
        ]);

        AttendanceException::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'date' => '2026-03-04',
            'type' => 'missing_out',
            'status' => 'open',
            'message' => 'Çıxış punch-u yoxdur',
        ]);

        $this->actingAs($user);

        Livewire::test(AttendanceManagerSummary::class, ['year' => 2026, 'month' => 3])
            ->assertSee(__('attendance::manager_summary.title'))
            ->assertSee('Məmmədov Elvin Rəşad')
            ->assertSee((string) 15)
            ->assertSee((string) 1)
            ->assertSee(__('attendance::manager_summary.labels.problematic'));
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

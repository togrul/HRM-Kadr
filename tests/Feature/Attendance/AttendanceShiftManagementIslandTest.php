<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceShift;
use App\Models\AttendanceShiftAssignment;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Attendance\Livewire\ShiftManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendanceShiftManagementIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_management_renders_linked_island_regions(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Shift Admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('manage-attendance-shifts', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        Livewire::test(ShiftManagement::class)
            ->assertSee(__('attendance::shift_management.titles.definitions'))
            ->assertSee(__('attendance::shift_management.titles.assignments'))
            ->assertSeeHtml('FRAGMENT:type=island|name=attendance-shift-definitions')
            ->assertDontSeeHtml('FRAGMENT:type=island|name=attendance-shift-assignments');
    }

    public function test_recent_assignment_edit_populates_form_without_island_regression(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Shift Admin',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('manage-attendance-shifts', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $personnel = $this->makePersonnel($user->id);

        $shift = AttendanceShift::query()->create([
            'name' => 'Day shift',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'is_night_shift' => false,
            'in_flex_before_minutes' => 0,
            'in_flex_after_minutes' => 0,
            'out_flex_before_minutes' => 0,
            'out_flex_after_minutes' => 0,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $assignment = AttendanceShiftAssignment::query()->create([
            'tabel_no' => $personnel->tabel_no,
            'shift_id' => $shift->id,
            'effective_from' => '2026-03-01',
            'effective_to' => '2026-03-31',
            'assignment_source' => 'manual_ui',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);

        Livewire::test(ShiftManagement::class)
            ->call('editAssignment', $assignment->id)
            ->assertSet('editingAssignmentId', $assignment->id)
            ->assertSet('assignmentForm.tabel_no', $personnel->tabel_no)
            ->assertSet('assignmentForm.shift_id', $shift->id)
            ->assertSet('assignmentForm.effective_from', '2026-03-01')
            ->assertSet('assignmentForm.effective_to', '2026-03-31')
            ->assertSet('selectedPersonnel.tabel_no', $personnel->tabel_no);
    }

    private function makePersonnel(int $userId): Personnel
    {
        DB::table('countries')->insert([
            'id' => 1,
            'code' => 'AZ',
        ]);

        DB::table('education_degrees')->insert([
            'id' => 1,
            'title_az' => 'Bakalavr',
            'title_en' => 'Bachelor',
            'title_ru' => 'Bachelor',
        ]);

        DB::table('structures')->insert([
            'id' => 1,
            'name' => 'HQ',
            'shortname' => 'HQ',
            'parent_id' => null,
            'coefficient' => 1.10,
            'code' => 10,
            'level' => 1,
        ]);

        DB::table('positions')->insert([
            'id' => 1,
            'name' => 'Officer',
        ]);

        DB::table('work_norms')->insert([
            'id' => 1,
            'name_az' => 'Tam iş günü',
            'name_en' => 'Full time',
            'name_ru' => 'Full time',
        ]);

        return Personnel::withoutEvents(fn () => Personnel::query()->create([
            'tabel_no' => 'TB'.Str::upper(Str::random(6)),
            'surname' => 'Doe',
            'name' => 'John',
            'patronymic' => 'Smith',
            'birthdate' => '1990-01-01',
            'gender' => 1,
            'mobile' => '994501112233',
            'nationality_id' => 1,
            'pin' => 'P'.str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT),
            'residental_address' => 'Main st',
            'education_degree_id' => 1,
            'structure_id' => 1,
            'position_id' => 1,
            'work_norm_id' => 1,
            'join_work_date' => '2026-03-01',
            'added_by' => $userId,
            'is_pending' => false,
        ]));
    }
}

<?php

namespace Tests\Feature\Attendance;

use App\Models\Role;
use App\Models\User;
use App\Modules\Attendance\Livewire\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendancePermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string,array<int,string>>
     */
    private const ROLE_MATRIX = [
        'HR Admin' => [
            'show-attendance',
            'manage-attendance',
            'add-attendance-manual',
            'edit-attendance-manual',
            'approve-attendance-manual',
            'approve-attendance-overtime',
            'manage-attendance-month-close',
            'edit-attendance-exceptions',
            'export-attendance',
        ],
        'HR Manager' => [
            'show-attendance',
            'add-attendance-manual',
            'edit-attendance-manual',
            'approve-attendance-manual',
            'approve-attendance-overtime',
            'edit-attendance-exceptions',
            'export-attendance',
        ],
        'HR Employee' => [
            'show-attendance',
        ],
        'HR Auditor' => [
            'show-attendance',
            'export-attendance',
        ],
    ];

    public function test_hr_admin_sees_all_attendance_tabs(): void
    {
        $user = $this->userForRole('HR Admin');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual', 'settings', 'shifts'])
            ->assertSee(__('Summary'))
            ->assertSee(__('Daily monitor'))
            ->assertSee(__('Timesheet grid'))
            ->assertSee(__('Exceptions inbox'))
            ->assertSee(__('Overtime board'))
            ->assertSee(__('Month close'))
            ->assertSee(__('Manual entries'))
            ->assertSee(__('Settings'))
            ->assertSee(__('Shifts'));
    }

    public function test_hr_manager_sees_operational_tabs_but_not_admin_shift_settings_tabs(): void
    {
        $user = $this->userForRole('HR Manager');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual'])
            ->assertSee(__('Manual entries'))
            ->assertSee(__('Exceptions inbox'))
            ->assertSee(__('Overtime board'))
            ->assertSee(__('Month close'))
            ->assertDontSee(__('Settings'))
            ->assertDontSee(__('Shifts'));
    }

    public function test_hr_employee_sees_only_read_tabs(): void
    {
        $user = $this->userForRole('HR Employee');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj'])
            ->assertSee(__('Summary'))
            ->assertSee(__('Daily monitor'))
            ->assertSee(__('Timesheet grid'))
            ->assertDontSee(__('Manual entries'))
            ->assertDontSee(__('Exceptions inbox'))
            ->assertDontSee(__('Overtime board'))
            ->assertDontSee(__('Month close'))
            ->assertDontSee(__('Settings'))
            ->assertDontSee(__('Shifts'));
    }

    public function test_hr_auditor_can_open_export_tab_but_not_mutation_tabs(): void
    {
        $user = $this->userForRole('HR Auditor');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'month-close'])
            ->assertSee(__('Month close'))
            ->assertDontSee(__('Manual entries'))
            ->assertDontSee(__('Exceptions inbox'))
            ->assertDontSee(__('Overtime board'))
            ->assertDontSee(__('Settings'))
            ->assertDontSee(__('Shifts'));
    }

    private function userForRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $permissions = collect(self::ROLE_MATRIX[$roleName])
            ->map(fn (string $permissionName) => Permission::findOrCreate($permissionName, 'web'));

        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}

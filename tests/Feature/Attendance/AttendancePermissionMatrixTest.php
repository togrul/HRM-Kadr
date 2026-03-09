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
            'manage-attendance-settings',
            'manage-attendance-shifts',
            'manage-attendance-calendars',
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
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual', 'settings', 'shifts', 'calendar-regimes'])
            ->assertSee(__('attendance::dashboard.tabs.overview'))
            ->assertSee(__('attendance::dashboard.tabs.daily_monitor'))
            ->assertSee(__('attendance::dashboard.tabs.puantaj'))
            ->assertSee(__('attendance::dashboard.tabs.exceptions'))
            ->assertSee(__('attendance::dashboard.tabs.overtime'))
            ->assertSee(__('attendance::dashboard.tabs.month_close'))
            ->assertSee(__('attendance::dashboard.tabs.manual'))
            ->assertSee(__('attendance::dashboard.tabs.settings'))
            ->assertSee(__('attendance::dashboard.tabs.shifts'))
            ->assertSee(__('attendance::dashboard.tabs.calendar_regimes'));
    }

    public function test_hr_manager_sees_operational_tabs_but_not_admin_shift_settings_tabs(): void
    {
        $user = $this->userForRole('HR Manager');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual'])
            ->assertSee(__('attendance::dashboard.tabs.manual'))
            ->assertSee(__('attendance::dashboard.tabs.exceptions'))
            ->assertSee(__('attendance::dashboard.tabs.overtime'))
            ->assertSee(__('attendance::dashboard.tabs.month_close'))
            ->assertDontSee(__('attendance::dashboard.tabs.settings'))
            ->assertDontSee(__('attendance::dashboard.tabs.shifts'))
            ->assertDontSee(__('attendance::dashboard.tabs.calendar_regimes'));
    }

    public function test_hr_employee_sees_module_tabs_but_not_admin_sections(): void
    {
        $user = $this->userForRole('HR Employee');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'manual'])
            ->assertSee(__('attendance::dashboard.tabs.overview'))
            ->assertSee(__('attendance::dashboard.tabs.daily_monitor'))
            ->assertSee(__('attendance::dashboard.tabs.puantaj'))
            ->assertSee(__('attendance::dashboard.tabs.manual'))
            ->assertSee(__('attendance::dashboard.tabs.exceptions'))
            ->assertSee(__('attendance::dashboard.tabs.overtime'))
            ->assertDontSee(__('attendance::dashboard.tabs.month_close'))
            ->assertDontSee(__('attendance::dashboard.tabs.settings'))
            ->assertDontSee(__('attendance::dashboard.tabs.shifts'))
            ->assertDontSee(__('attendance::dashboard.tabs.calendar_regimes'));
    }

    public function test_hr_auditor_can_open_module_tabs_but_not_admin_sections(): void
    {
        $user = $this->userForRole('HR Auditor');

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual'])
            ->assertSee(__('attendance::dashboard.tabs.month_close'))
            ->assertSee(__('attendance::dashboard.tabs.manual'))
            ->assertSee(__('attendance::dashboard.tabs.exceptions'))
            ->assertSee(__('attendance::dashboard.tabs.overtime'))
            ->assertDontSee(__('attendance::dashboard.tabs.settings'))
            ->assertDontSee(__('attendance::dashboard.tabs.shifts'))
            ->assertDontSee(__('attendance::dashboard.tabs.calendar_regimes'));
    }

    public function test_settings_and_shifts_tabs_require_explicit_section_permissions(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Attendance Ops',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('show-attendance', 'web'),
            Permission::findOrCreate('manage-attendance', 'web'),
            Permission::findOrCreate('show-attendance-manual', 'web'),
            Permission::findOrCreate('show-attendance-overtime', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertSet('availableTabs', ['overview', 'daily-monitor', 'puantaj', 'exceptions', 'overtime', 'month-close', 'manual'])
            ->assertDontSee(__('attendance::dashboard.tabs.settings'))
            ->assertDontSee(__('attendance::dashboard.tabs.shifts'))
            ->assertDontSee(__('attendance::dashboard.tabs.calendar_regimes'));
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

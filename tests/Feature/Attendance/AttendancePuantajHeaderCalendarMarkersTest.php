<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceCalendar;
use App\Models\Role;
use App\Models\User;
use App\Modules\Attendance\Livewire\PuantajGrid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendancePuantajHeaderCalendarMarkersTest extends TestCase
{
    use RefreshDatabase;

    public function test_puantaj_headers_mark_implicit_weekend_and_explicit_holiday_days(): void
    {
        $user = $this->authorizedUser();
        $this->actingAs($user);

        AttendanceCalendar::query()->create([
            'date' => '2026-03-09',
            'day_type' => 'holiday',
            'name' => 'attendance::calendar_regimes.options.holiday',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ]);

        Livewire::test(PuantajGrid::class, ['year' => 2026, 'month' => 3])
            ->assertSeeHtml('data-day="7"')
            ->assertSeeHtml('data-day-type="weekend"')
            ->assertSeeHtml('data-day="9"')
            ->assertSeeHtml('data-day-type="holiday"');
    }

    private function authorizedUser(): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Puantaj Test User',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            Permission::findOrCreate('show-attendance', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}

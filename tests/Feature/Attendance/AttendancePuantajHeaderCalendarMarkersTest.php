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

    public function test_the_header_row_and_the_name_column_stay_pinned_while_days_scroll(): void
    {
        $this->actingAs($this->authorizedUser());

        $html = Livewire::test(PuantajGrid::class, ['year' => 2026, 'month' => 3])->html();

        // the table's own scroller scrolls both axes, so sticky resolves against it
        $this->assertStringContainsString('max-h-[calc(100dvh-15rem)] overflow-y-auto', $html);
        $this->assertMatchesRegularExpression('/<th[^>]*class="[^"]*sticky top-0 z-10[^"]*left-0 z-20/s', $html);
        $this->assertStringNotContainsString('inline-block min-w-full', $html);
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

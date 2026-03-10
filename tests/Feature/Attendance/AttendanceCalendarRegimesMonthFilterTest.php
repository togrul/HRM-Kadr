<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceCalendar;
use App\Models\User;
use App\Modules\Attendance\Livewire\CalendarRegimes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AttendanceCalendarRegimesMonthFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_regimes_table_respects_selected_year_and_month(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('manage-attendance-calendars', 'web'));

        $this->actingAs($user);

        AttendanceCalendar::query()->create([
            'date' => '2026-03-10',
            'day_type' => 'holiday',
            'name' => 'Mart bayramı',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ]);

        AttendanceCalendar::query()->create([
            'date' => '2026-04-05',
            'day_type' => 'holiday',
            'name' => 'Aprel bayramı',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ]);

        Livewire::test(CalendarRegimes::class, ['year' => 2026, 'month' => 3])
            ->assertSee('2026-03-10')
            ->assertDontSee('2026-04-05');
    }
}

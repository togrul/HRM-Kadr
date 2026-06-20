<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceCalendar;
use App\Modules\Attendance\Application\Services\AttendanceWeekendCalendarSeederService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceWeekendCalendarSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_global_weekend_rows_for_month_and_is_idempotent(): void
    {
        /** @var AttendanceWeekendCalendarSeederService $service */
        $service = app(AttendanceWeekendCalendarSeederService::class);

        $first = $service->seedMonth(2026, 3);
        $second = $service->seedMonth(2026, 3);

        $this->assertSame(9, $first['weekend_days']);
        $this->assertSame(9, $first['created']);
        $this->assertSame(0, $first['skipped_existing']);

        $this->assertSame(9, $second['weekend_days']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(9, $second['skipped_existing']);

        $this->assertSame(
            9,
            AttendanceCalendar::query()
                ->where('scope_type', 'global')
                ->whereNull('scope_id')
                ->where('day_type', 'weekend')
                ->whereDate('date', '>=', '2026-03-01')
                ->whereDate('date', '<=', '2026-03-31')
                ->count()
        );
    }

    public function test_it_does_not_override_existing_manual_global_calendar_rule(): void
    {
        AttendanceCalendar::query()->create([
            'date' => '2026-03-07',
            'day_type' => 'holiday',
            'name' => 'attendance::calendar_regimes.options.holiday',
            'is_paid' => true,
            'scope_type' => 'global',
            'scope_id' => null,
        ]);

        /** @var AttendanceWeekendCalendarSeederService $service */
        $service = app(AttendanceWeekendCalendarSeederService::class);
        $stats = $service->seedMonth(2026, 3);

        $this->assertSame(8, $stats['created']);
        $this->assertSame(1, $stats['skipped_existing']);

        $calendar = AttendanceCalendar::query()
            ->whereDate('date', '2026-03-07')
            ->where('scope_type', 'global')
            ->whereNull('scope_id')
            ->first();

        $this->assertNotNull($calendar);
        $this->assertSame('holiday', $calendar->day_type);
    }
}

<?php

namespace App\Modules\Attendance\Console\Commands;

use App\Modules\Attendance\Application\Services\AttendanceWeekendCalendarSeederService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AttendanceSeedWeekendCalendarsCommand extends Command
{
    protected $signature = 'attendance:calendars:seed-weekends
        {--year= : Target year}
        {--month= : Target month (1-12)}
        {--next-month : Seed next month instead of current month}';

    protected $description = 'Auto-create global weekend calendar rows for a target month';

    public function handle(AttendanceWeekendCalendarSeederService $service): int
    {
        $target = $this->resolveTargetMonth();

        $stats = $service->seedMonth(
            year: (int) $target->year,
            month: (int) $target->month,
            causerId: null
        );

        $this->table(
            ['metric', 'value'],
            [
                ['year', (string) $target->year],
                ['month', str_pad((string) $target->month, 2, '0', STR_PAD_LEFT)],
                ['weekend_days', (string) $stats['weekend_days']],
                ['created', (string) $stats['created']],
                ['skipped_existing', (string) $stats['skipped_existing']],
            ]
        );

        return self::SUCCESS;
    }

    private function resolveTargetMonth(): Carbon
    {
        if ((bool) $this->option('next-month')) {
            return now()->addMonthNoOverflow()->startOfMonth();
        }

        $year = $this->option('year');
        $month = $this->option('month');

        if (is_numeric($year) && is_numeric($month)) {
            return Carbon::createFromDate((int) $year, (int) $month, 1)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}

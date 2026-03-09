<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('notify:birthdays')->dailyAt('08:00');

        if ((bool) config('orders.observability.reports.enabled', false)) {
            $dailyAt = (string) config('orders.observability.reports.daily_at', '09:00');
            $weeklyDay = (int) config('orders.observability.reports.weekly_day', 1);
            $weeklyAt = (string) config('orders.observability.reports.weekly_at', '09:00');

            $schedule->command('orders:templates:report --days=1 --allow-empty-budget')
                ->dailyAt($dailyAt)
                ->withoutOverlapping();

            $schedule->command('orders:templates:report --days=7 --allow-empty-budget')
                ->weeklyOn($weeklyDay, $weeklyAt)
                ->withoutOverlapping();
        }

        if ((bool) config('attendance.processing.schedule_enabled', false)) {
            $everyMinutes = min(59, max(1, (int) config('attendance.processing.schedule_every_minutes', 10)));
            $schedule->command('attendance:punches:process')
                ->cron(sprintf('*/%d * * * *', $everyMinutes))
                ->withoutOverlapping();
        }

        if ((bool) config('attendance.snapshot.schedule_enabled', false)) {
            $day = min(28, max(1, (int) config('attendance.snapshot.schedule_day', 1)));
            $at = (string) config('attendance.snapshot.schedule_at', '01:30');
            $lock = (bool) config('attendance.snapshot.schedule_lock', false);

            $command = 'attendance:monthly-snapshot --previous-month';
            if ($lock) {
                $command .= ' --lock';
            }

            $schedule->command($command)
                ->monthlyOn($day, $at)
                ->withoutOverlapping();
        }

        if ((bool) config('attendance.calendar.weekend_auto_seed.schedule_enabled', true)) {
            $at = (string) config('attendance.calendar.weekend_auto_seed.schedule_at', '00:05');

            $schedule->command('attendance:calendars:seed-weekends')
                ->monthlyOn(1, $at)
                ->withoutOverlapping();
        }

        if ((bool) config('attendance.observability.reports.enabled', false)) {
            $dailyAt = (string) config('attendance.observability.reports.daily_at', '08:30');
            $weeklyDay = (int) config('attendance.observability.reports.weekly_day', 1);
            $weeklyAt = (string) config('attendance.observability.reports.weekly_at', '08:30');

            $dailyEvent = $schedule->command('attendance:query-budget --json --allow-empty')
                ->dailyAt($dailyAt)
                ->withoutOverlapping();

            $weeklyEvent = $schedule->command('attendance:query-budget --json --allow-empty')
                ->weeklyOn($weeklyDay, $weeklyAt)
                ->withoutOverlapping();

            if ((bool) config('attendance.observability.reports.append_output', true)) {
                $outputPath = storage_path((string) config('attendance.observability.reports.output_file', 'logs/attendance-query-budget.log'));
                $dailyEvent->appendOutputTo($outputPath);
                $weeklyEvent->appendOutputTo($outputPath);
            }
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

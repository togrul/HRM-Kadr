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

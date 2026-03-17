<?php

namespace App\Console\Commands;

use App\Models\AttendanceCalendar;
use App\Modules\Notifications\Support\NotificationCampaignDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyHolidays extends Command
{
    protected $signature = 'notify:holidays {--days-ahead=1 : How many days ahead to look for holiday notifications}';

    protected $description = 'Dispatch holiday notifications through the managed notification pipeline.';

    public function handle(NotificationCampaignDispatcher $dispatcher): int
    {
        $daysAhead = max(0, (int) $this->option('days-ahead'));
        $targetDate = Carbon::today()->addDays($daysAhead)->toDateString();

        $holidays = AttendanceCalendar::query()
            ->where('day_type', 'holiday')
            ->whereDate('date', $targetDate)
            ->orderBy('scope_type')
            ->orderBy('scope_id')
            ->get(['id', 'date', 'day_type', 'name', 'scope_type', 'scope_id', 'is_paid']);

        if ($holidays->isEmpty()) {
            $this->info('No holidays found for notification window.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($holidays as $holiday) {
            $sent += $dispatcher->dispatchHoliday($holiday);
        }

        $this->info("Holiday notifications dispatched: {$sent}");

        return self::SUCCESS;
    }
}

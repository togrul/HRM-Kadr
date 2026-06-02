<?php

namespace App\Modules\EmployeeLifecycle\Console\Commands;

use App\Modules\EmployeeLifecycle\Application\Services\LifecycleReminderService;
use Illuminate\Console\Command;

class SendLifecycleDeadlineRemindersCommand extends Command
{
    protected $signature = 'employee-lifecycle:send-reminders
        {--days-ahead= : Include deadlines due within this many days}
        {--cooldown-hours= : Minimum hours before the same item can be reminded again}
        {--max= : Maximum reminders to send in one run}
        {--json}';

    protected $description = 'Send idempotent lifecycle event and task deadline reminders.';

    public function handle(LifecycleReminderService $service): int
    {
        $result = $service->run(
            $this->option('days-ahead') !== null ? (int) $this->option('days-ahead') : null,
            $this->option('cooldown-hours') !== null ? (int) $this->option('cooldown-hours') : null,
            $this->option('max') !== null ? (int) $this->option('max') : null,
        );

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('Employee lifecycle reminders completed.');
        $this->table(['Metric', 'Value'], collect($result)->map(fn ($value, $key) => [$key, $value]));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditRetentionStatusCommand extends Command
{
    protected $signature = 'audit:retention-status {--json : Print report as JSON}';

    protected $description = 'Show audit activity retention configuration and scheduler readiness';

    public function handle(): int
    {
        $payload = [
            'enabled' => (bool) config('activitylog.enabled'),
            'connection' => (string) config('activitylog.database_connection'),
            'table' => (string) config('activitylog.table_name', 'activity_log'),
            'retention_days' => (int) config('activitylog.delete_records_older_than_days'),
            'schedule_enabled' => (bool) config('activitylog.retention.schedule_enabled', false),
            'daily_at' => (string) config('activitylog.retention.daily_at', '02:30'),
            'scheduled_command' => 'activitylog:clean --force',
            'scheduler_required_cron' => '* * * * * php artisan schedule:run',
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(
            ['metric', 'value'],
            collect($payload)->map(fn ($value, string $metric) => [
                $metric,
                is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
            ])->values()->all()
        );

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditActivityMigrateCommand extends Command
{
    protected $signature = 'audit:activity-migrate
        {--force : Force the operation to run in production}';

    protected $description = 'Run activity log migrations on the configured audit connection.';

    public function handle(): int
    {
        $connection = config('activitylog.database_connection') ?: config('database.default');
        $paths = [
            'database/migrations/2025_03_03_195447_create_activity_log_table.php',
            'database/migrations/2025_03_03_195448_add_event_column_to_activity_log_table.php',
            'database/migrations/2025_03_03_195449_add_batch_uuid_column_to_activity_log_table.php',
        ];

        $this->info("Running activity log migrations on [{$connection}] connection.");

        $exitCode = $this->call('migrate', [
            '--database' => $connection,
            '--path' => $paths,
            '--force' => (bool) $this->option('force'),
        ]);

        return $exitCode === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}

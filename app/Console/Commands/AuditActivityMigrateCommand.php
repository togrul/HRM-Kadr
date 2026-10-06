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

        $this->info("Running activity log migrations on [{$connection}] connection.");

        $exitCode = $this->call('migrate', [
            '--database' => $connection,
            '--path' => 'database/migrations_logs',
            '--force' => (bool) $this->option('force'),
        ]);

        return $exitCode === self::SUCCESS ? self::SUCCESS : self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

class AuditDatabasePrepareCommand extends Command
{
    protected $signature = 'audit:database-prepare
        {--migrate : Run activity log migrations after creating the database}
        {--force : Force nested migrations to run in production}';

    protected $description = 'Create the configured audit database when supported and optionally migrate activity tables.';

    public function handle(): int
    {
        $connection = config('activitylog.database_connection') ?: config('database.default');
        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            $this->error("Database connection [{$connection}] is not configured.");

            return self::FAILURE;
        }

        $driver = (string) ($config['driver'] ?? '');

        if ($driver !== 'mysql') {
            $this->warn("Automatic audit database creation is only implemented for mysql. Current driver: [{$driver}].");
            $this->line('Create the database manually, then run: php artisan audit:activity-migrate --force');

            return self::SUCCESS;
        }

        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            $this->error("Connection [{$connection}] does not define a database name.");

            return self::FAILURE;
        }

        $serverConnection = "{$connection}_server";
        Config::set("database.connections.{$serverConnection}", array_merge(
            Arr::except($config, ['database']),
            ['database' => null]
        ));

        try {
            DB::connection($serverConnection)->statement(sprintf(
                'CREATE DATABASE IF NOT EXISTS %s CHARACTER SET %s COLLATE %s',
                $this->quoteMysqlIdentifier($database),
                $this->quoteMysqlValue((string) ($config['charset'] ?? 'utf8mb4')),
                $this->quoteMysqlValue((string) ($config['collation'] ?? 'utf8mb4_unicode_ci')),
            ));
        } catch (Throwable $exception) {
            $this->error("Could not create audit database [{$database}] on connection [{$connection}].");
            $this->line($exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge($serverConnection);
        }

        $this->info("Audit database [{$database}] is ready on connection [{$connection}].");

        if ((bool) $this->option('migrate')) {
            return Artisan::call('audit:activity-migrate', [
                '--force' => (bool) $this->option('force'),
            ]) === self::SUCCESS ? self::SUCCESS : self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function quoteMysqlIdentifier(string $value): string
    {
        return '`'.str_replace('`', '``', $value).'`';
    }

    private function quoteMysqlValue(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
}

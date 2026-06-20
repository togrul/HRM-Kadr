<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditActivityBackfillCommand extends Command
{
    protected $signature = 'audit:activity-backfill
        {--source-connection= : Source connection for the old activity_log table}
        {--source-table= : Source activity table name}
        {--chunk=500 : Number of rows copied per batch}';

    protected $description = 'Copy old activity log rows from the main database into the configured audit database.';

    public function handle(): int
    {
        $sourceConnection = (string) ($this->option('source-connection') ?: config('database.default'));
        $destinationConnection = config('activitylog.database_connection') ?: config('database.default');
        $sourceTable = (string) ($this->option('source-table') ?: config('activitylog.table_name', 'activity_log'));
        $destinationTable = (string) config('activitylog.table_name', 'activity_log');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($sourceConnection === $destinationConnection) {
            $this->warn("Source and destination connection are both [{$sourceConnection}]. Nothing to backfill.");

            return self::SUCCESS;
        }

        if (! Schema::connection($sourceConnection)->hasTable($sourceTable)) {
            $this->error("Source table [{$sourceConnection}.{$sourceTable}] does not exist.");

            return self::FAILURE;
        }

        if (! Schema::connection($destinationConnection)->hasTable($destinationTable)) {
            $this->error("Destination table [{$destinationConnection}.{$destinationTable}] does not exist.");
            $this->line('Run: php artisan audit:activity-migrate --force');

            return self::FAILURE;
        }

        $sourceColumns = Schema::connection($sourceConnection)->getColumnListing($sourceTable);
        $destinationColumns = Schema::connection($destinationConnection)->getColumnListing($destinationTable);
        $columns = array_values(array_intersect($sourceColumns, $destinationColumns));

        if (! in_array('id', $columns, true)) {
            $this->error('Both source and destination activity tables must include an id column.');

            return self::FAILURE;
        }

        $copied = 0;
        $skipped = 0;

        DB::connection($sourceConnection)->table($sourceTable)
            ->select($columns)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($rows) use (
                $destinationConnection,
                $destinationTable,
                $columns,
                &$copied,
                &$skipped
            ) {
                $payload = collect($rows)->map(function ($row) use ($columns) {
                    $row = (array) $row;

                    return collect($columns)
                        ->mapWithKeys(fn (string $column) => [$column => $row[$column] ?? null])
                        ->all();
                });

                $ids = $payload->pluck('id')->all();
                $existingIds = DB::connection($destinationConnection)->table($destinationTable)
                    ->whereIn('id', $ids)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $existingLookup = array_flip($existingIds);
                $missingRows = $payload
                    ->reject(fn (array $row) => isset($existingLookup[(int) $row['id']]))
                    ->values();

                if ($missingRows->isNotEmpty()) {
                    DB::connection($destinationConnection)->table($destinationTable)
                        ->insert($missingRows->all());
                }

                $copied += $missingRows->count();
                $skipped += count($ids) - $missingRows->count();
            }, 'id');

        $this->table(['source', 'destination', 'copied', 'skipped'], [[
            "{$sourceConnection}.{$sourceTable}",
            "{$destinationConnection}.{$destinationTable}",
            (string) $copied,
            (string) $skipped,
        ]]);

        return self::SUCCESS;
    }
}

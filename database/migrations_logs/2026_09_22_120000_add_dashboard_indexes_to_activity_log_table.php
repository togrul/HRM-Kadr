<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The audit dashboard sorts by (created_at, id), filters by date range and groups by
 * event; without these every render filesorts the whole log.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection(config('activitylog.database_connection'));
        $table = config('activitylog.table_name');

        // The shared audit database may already carry them — see the create migration.
        $existing = collect($schema->getIndexes($table))->pluck('name')->all();

        $schema->table($table, function (Blueprint $blueprint) use ($existing, $table): void {
            if (! in_array("{$table}_created_at_id_index", $existing, true)) {
                $blueprint->index(['created_at', 'id']);
            }

            if (! in_array("{$table}_event_created_at_index", $existing, true)) {
                $blueprint->index(['event', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(config('activitylog.table_name'), function (Blueprint $blueprint): void {
            $blueprint->dropIndex(['created_at', 'id']);
            $blueprint->dropIndex(['event', 'created_at']);
        });
    }
};

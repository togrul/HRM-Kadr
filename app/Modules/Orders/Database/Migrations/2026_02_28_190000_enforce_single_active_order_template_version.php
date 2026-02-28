<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'order_template_versions_single_active_per_set';

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if (! $this->hasIndex('order_template_versions', self::INDEX_NAME)) {
                // MySQL 8 supports functional indexes. This avoids generated-column/FK edge cases.
                DB::statement(
                    'CREATE UNIQUE INDEX '.self::INDEX_NAME
                    .' ON order_template_versions ((CASE WHEN is_active = 1 THEN order_template_set_id ELSE NULL END))'
                );
            }

            return;
        }

        if (! $this->hasIndex('order_template_versions', self::INDEX_NAME)) {
            DB::statement(
                'CREATE UNIQUE INDEX '.self::INDEX_NAME
                .' ON order_template_versions(order_template_set_id) WHERE is_active = 1'
            );
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if ($this->hasIndex('order_template_versions', self::INDEX_NAME)) {
                Schema::table('order_template_versions', function (Blueprint $table) {
                    $table->dropUnique(self::INDEX_NAME);
                });
            }

            return;
        }

        if ($this->hasIndex('order_template_versions', self::INDEX_NAME)) {
            DB::statement('DROP INDEX '.self::INDEX_NAME);
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        return match ($driver) {
            'mysql' => ! empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])),
            'pgsql' => (bool) DB::scalar(
                'SELECT EXISTS (
                    SELECT 1
                    FROM pg_indexes
                    WHERE schemaname = current_schema()
                    AND tablename = ?
                    AND indexname = ?
                )',
                [$table, $indexName]
            ),
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => (($row->name ?? null) === $indexName)),
            default => false,
        };
    }
};

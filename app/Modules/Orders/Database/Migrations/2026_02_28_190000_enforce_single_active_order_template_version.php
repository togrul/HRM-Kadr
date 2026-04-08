<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'order_template_versions_single_active_per_set';
    private const MARIA_DB_COLUMN = 'active_order_template_set_id';

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            if ($this->isMariaDb()) {
                $this->createMariaDbIndex();
            } elseif (! $this->hasIndex('order_template_versions', self::INDEX_NAME)) {
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
            if ($this->isMariaDb()) {
                $this->dropMariaDbIndex();
            } elseif ($this->hasIndex('order_template_versions', self::INDEX_NAME)) {
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

    private function isMariaDb(): bool
    {
        $version = (string) DB::scalar('select version()');

        return str_contains(strtolower($version), 'mariadb');
    }

    private function createMariaDbIndex(): void
    {
        if (! Schema::hasColumn('order_template_versions', self::MARIA_DB_COLUMN)) {
            DB::statement(
                'ALTER TABLE order_template_versions '
                .'ADD COLUMN '.self::MARIA_DB_COLUMN
                .' BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_active = 1 THEN order_template_set_id ELSE NULL END) STORED'
            );
        }

        if (! $this->hasIndex('order_template_versions', self::INDEX_NAME)) {
            Schema::table('order_template_versions', function (Blueprint $table) {
                $table->unique(self::MARIA_DB_COLUMN, self::INDEX_NAME);
            });
        }
    }

    private function dropMariaDbIndex(): void
    {
        if ($this->hasIndex('order_template_versions', self::INDEX_NAME)) {
            Schema::table('order_template_versions', function (Blueprint $table) {
                $table->dropUnique(self::INDEX_NAME);
            });
        }

        if (Schema::hasColumn('order_template_versions', self::MARIA_DB_COLUMN)) {
            Schema::table('order_template_versions', function (Blueprint $table) {
                $table->dropColumn(self::MARIA_DB_COLUMN);
            });
        }
    }
};

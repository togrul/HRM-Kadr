<?php

namespace App\Support\Database;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Memoized table-existence guard.
 *
 * `Schema::hasTable()` is a database round trip per call, and the read services that
 * guard optional module tables ask the same questions on every render — the landing page
 * alone asked a dozen times. One table listing per connection, per request, answers all
 * of them.
 *
 * Resolved as a container singleton, so the cache lives exactly as long as the request
 * (and is rebuilt between tests, where migrations run in between).
 */
class InstalledTables
{
    /** @var array<string,array<string,true>> */
    private array $listings = [];

    public static function has(string $table, ?string $connection = null): bool
    {
        return app(self::class)->installed($table, $connection);
    }

    public function installed(string $table, ?string $connection = null): bool
    {
        return isset($this->listing($connection)[$table]);
    }

    public function flush(): void
    {
        $this->listings = [];
    }

    /**
     * @return array<string,true>
     */
    private function listing(?string $connection): array
    {
        $handle = DB::connection($connection);

        return $this->listings[$handle->getName()] ??= array_fill_keys($this->names($handle, $connection), true);
    }

    /**
     * `Schema::getTableListing()` answers this too, but its query also computes every
     * table's size, comment, engine and collation — 5.5ms against this database against
     * 1ms for the names alone, on a path every page hits. The `table_type` filter and the
     * single-schema scope mirror the framework's own MySQL grammar exactly.
     *
     * @return list<string>
     */
    private function names(Connection $handle, ?string $connection): array
    {
        if (in_array($handle->getDriverName(), ['mysql', 'mariadb'], true)) {
            return array_map(
                static fn (object $row): string => (string) $row->name,
                $handle->select(
                    'select table_name as `name` from information_schema.tables '
                    ."where table_schema = ? and table_type in ('BASE TABLE', 'SYSTEM VERSIONED')",
                    [$handle->getDatabaseName()],
                ),
            );
        }

        // SQLite has a single database, and its name (`:memory:`) is not a schema filter;
        // anything else falls back to the framework, which is cheap outside MySQL.
        $schema = $handle->getDriverName() === 'sqlite' ? null : $handle->getDatabaseName();

        return Schema::connection($connection)->getTableListing($schema, schemaQualified: false);
    }
}

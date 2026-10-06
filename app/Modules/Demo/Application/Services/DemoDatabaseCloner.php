<?php

namespace App\Modules\Demo\Application\Services;

use App\Modules\Demo\Models\DemoTenant;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Şablon bazadan (demo serverin əsas bazası) yeni müştəri bazası qurur: bütün
 * cədvəllərin strukturu olduğu kimi (xarici açarlarla), məlumat isə yalnız
 * `demo.reference_tables` siyahısındakı cədvəllərdən köçürülür. Yalnız MySQL.
 */
class DemoDatabaseCloner
{
    private const BUILD_CONNECTION = 'demo_build';

    public function __construct(private readonly string $auditSourceDatabase) {}

    /** @return array{tables:int, copied:int} */
    public function cloneMain(string $target): array
    {
        $control = $this->control();

        return $this->cloneSchema(
            $control,
            (string) $control->getDatabaseName(),
            $target,
            (array) config('demo.reference_tables', []),
            ['demo_tenants'],
        );
    }

    /** @return array{tables:int, copied:int} */
    public function cloneAudit(string $target): array
    {
        return $this->cloneSchema($this->control(), $this->auditSourceDatabase, $target, ['migrations'], []);
    }

    public function databaseExists(string $name): bool
    {
        return $this->control()
            ->table('information_schema.schemata')
            ->where('schema_name', $name)
            ->exists();
    }

    public function drop(string $name): void
    {
        $this->control()->statement('DROP DATABASE IF EXISTS '.$this->quote($name));
    }

    /**
     * @param  list<string>  $dataTables
     * @param  list<string>  $skipTables
     * @return array{tables:int, copied:int}
     */
    private function cloneSchema(Connection $control, string $source, string $target, array $dataTables, array $skipTables): array
    {
        if ($control->getDriverName() !== 'mysql') {
            throw new RuntimeException('Demo bazaları yalnız MySQL-də yaradıla bilər.');
        }

        $control->statement(sprintf(
            'CREATE DATABASE %s CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $this->quote($target)
        ));

        $build = $this->buildConnection($target);
        $tables = $control->table('information_schema.tables')
            ->where('table_schema', $source)
            ->where('table_type', 'BASE TABLE')
            ->whereNotIn('table_name', $skipTables)
            ->orderBy('table_name')
            ->selectRaw('table_name as name') // MySQL 8 sütun adını böyük hərflə qaytarır
            ->pluck('name')
            ->map(fn ($name): string => (string) $name)
            ->all();

        $copied = 0;
        $build->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                $create = (array) $control->selectOne('SHOW CREATE TABLE '.$this->quote($source).'.'.$this->quote($table));
                $sql = (string) ($create['Create Table'] ?? array_values($create)[1] ?? '');
                $build->statement((string) preg_replace('/\sAUTO_INCREMENT=\d+/', '', $sql));

                if (in_array($table, $dataTables, true)) {
                    $this->copyRows($build, $control, $source, $target, $table);
                    $copied++;
                }
            }
        } finally {
            $build->statement('SET FOREIGN_KEY_CHECKS=1');
            DB::purge(self::BUILD_CONNECTION);
        }

        return ['tables' => count($tables), 'copied' => $copied];
    }

    private function copyRows(Connection $build, Connection $control, string $source, string $target, string $table): void
    {
        // Hesablanan (virtual/stored generated) sütunlara yazmaq olmaz.
        $columns = $control->table('information_schema.columns')
            ->where('table_schema', $source)
            ->where('table_name', $table)
            ->where(fn ($query) => $query
                ->where('extra', 'not like', '%VIRTUAL GENERATED%')
                ->where('extra', 'not like', '%STORED GENERATED%'))
            ->orderBy('ordinal_position')
            ->selectRaw('column_name as name')
            ->pluck('name')
            ->map(fn ($name): string => $this->quote((string) $name))
            ->implode(', ');

        $build->statement(sprintf(
            'INSERT INTO %s.%s (%s) SELECT %s FROM %s.%s',
            $this->quote($target), $this->quote($table), $columns,
            $columns, $this->quote($source), $this->quote($table),
        ));
    }

    private function buildConnection(string $database): Connection
    {
        $config = config('database.connections.'.DemoTenant::CONNECTION);
        $config['database'] = $database;
        config(['database.connections.'.self::BUILD_CONNECTION => $config]);
        DB::purge(self::BUILD_CONNECTION);

        return DB::connection(self::BUILD_CONNECTION);
    }

    private function control(): Connection
    {
        return DB::connection(DemoTenant::CONNECTION);
    }

    private function quote(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }
}

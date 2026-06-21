<?php

namespace App\Services\Structures;

use Illuminate\Support\Facades\Schema;

/**
 * Discovers — from the live database schema — every column that points at the
 * `structures` table, and returns them in a foreign-key-safe deletion order.
 *
 * Discovery is dynamic (via Schema introspection) so the map never goes stale: add a
 * new table with a structure foreign key tomorrow and it is picked up automatically,
 * with no code change here. A short supplemental list covers columns that reference a
 * structure WITHOUT a database-level constraint (legacy tables) which introspection
 * cannot see. Results are memoised for the lifetime of the request.
 */
class StructureDependencyMap
{
    /** The structures table — the deletion root. */
    private const TARGET = 'structures';

    /**
     * Columns that logically reference a structure but carry no DB foreign key, so the
     * schema introspector cannot find them. Kept tiny and obvious on purpose.
     *
     * @var array<int,array{0:string,1:string}>  [table, column]
     */
    private const UNCONSTRAINED = [
        ['staff_schedules', 'structure_id'],
        ['attendance_daily_structure_summaries', 'structure_id'],
    ];

    /** @var array<int,array{table:string,column:string}>|null */
    private ?array $memo = null;

    /**
     * Every (table, column) that references a structure, ordered so that a table is
     * always deleted before any table it depends on (referencing rows first).
     *
     * @return array<int,array{table:string,column:string}>
     */
    public function deletionOrder(): array
    {
        return $this->memo ??= $this->topologicallyOrder($this->discover());
    }

    /**
     * Raw, unordered discovery of every referencing column.
     *
     * @return array<string,array{table:string,column:string}> keyed by "table.column"
     */
    private function discover(): array
    {
        $refs = [];

        foreach (Schema::getTables() as $table) {
            $name = $table['name'];

            foreach (Schema::getForeignKeys($name) as $foreignKey) {
                if (($foreignKey['foreign_table'] ?? null) !== self::TARGET) {
                    continue;
                }
                foreach ($foreignKey['columns'] as $column) {
                    $refs[$name.'.'.$column] = ['table' => $name, 'column' => $column];
                }
            }
        }

        foreach (self::UNCONSTRAINED as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $refs[$table.'.'.$column] ??= ['table' => $table, 'column' => $column];
            }
        }

        return $refs;
    }

    /**
     * Order the referencing entries so a table that has a foreign key onto another
     * referencing table is deleted first (Kahn topological sort over the sub-graph).
     *
     * @param  array<string,array{table:string,column:string}>  $refs
     * @return array<int,array{table:string,column:string}>
     */
    private function topologicallyOrder(array $refs): array
    {
        $tables = array_values(array_unique(array_map(static fn ($r) => $r['table'], $refs)));
        $inSet = array_flip($tables);

        $dependsOn = array_fill_keys($tables, []); // table => [tables it references]
        $indegree = array_fill_keys($tables, 0);   // how many referencing tables depend on it

        foreach ($tables as $table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $referenced = $foreignKey['foreign_table'] ?? null;
                if ($referenced === null || $referenced === $table || ! isset($inSet[$referenced])) {
                    continue;
                }
                if (! in_array($referenced, $dependsOn[$table], true)) {
                    $dependsOn[$table][] = $referenced;
                    $indegree[$referenced]++;
                }
            }
        }

        // Tables nothing else depends on go first.
        $queue = array_values(array_filter($tables, static fn ($t) => $indegree[$t] === 0));
        $ordered = [];

        while ($queue !== []) {
            $table = array_shift($queue);
            $ordered[] = $table;
            foreach ($dependsOn[$table] as $referenced) {
                if (--$indegree[$referenced] === 0) {
                    $queue[] = $referenced;
                }
            }
        }

        // Defensive: any table left out by a cycle still gets deleted (FK off would be needed,
        // but real schemas here are acyclic) — append in discovery order.
        foreach ($tables as $table) {
            if (! in_array($table, $ordered, true)) {
                $ordered[] = $table;
            }
        }

        $byTable = [];
        foreach ($refs as $ref) {
            $byTable[$ref['table']][] = $ref;
        }

        $result = [];
        foreach ($ordered as $table) {
            foreach ($byTable[$table] ?? [] as $ref) {
                $result[] = $ref;
            }
        }

        return $result;
    }
}

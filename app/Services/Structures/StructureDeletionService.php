<?php

namespace App\Services\Structures;

use App\Models\Structure;
use Illuminate\Support\Facades\DB;

/**
 * Safely removes a structure together with everything that depends on it.
 *
 * The structure tree is referenced by many tables (employees, candidates, staff
 * schedules, approval routes, recruitment, lifecycle …) through RESTRICT foreign keys,
 * so a plain delete fails. This service answers "is it referenced anywhere?" (for the
 * warning) and, once the admin confirms, deletes the structure, all of its descendant
 * structures and every dependent row in a single transaction.
 *
 * Design notes (deliberate, not shortcuts):
 *  - Dependents are discovered from the live schema (see StructureDependencyMap) so the
 *    map can never silently miss a newly added foreign key.
 *  - Dependent rows are removed with bulk query-builder deletes. This is intentional:
 *    deep child records (a person's vacations, ranks, documents …) are removed by their
 *    own ON DELETE CASCADE rules at the database level, while bypassing per-record model
 *    events avoids firing thousands of side effects — e.g. PersonnelObserver::deleted
 *    notifies every admin, which would spam one notification per deleted employee.
 *  - The whole operation is recorded once in the activity log (who, what, how many rows
 *    per table) so this destructive action is fully auditable.
 *  - The structures themselves are deleted through the model so StructureObserver fires
 *    and the structure caches are flushed.
 */
class StructureDeletionService
{
    public function __construct(private readonly StructureDependencyMap $dependencies) {}

    /** Whether the structure — or any of its descendants — is referenced anywhere. */
    public function isUsed(int $structureId): bool
    {
        $ids = $this->descendantIds($structureId);

        foreach ($this->dependencies->deletionOrder() as $dependent) {
            if (DB::table($dependent['table'])->whereIn($dependent['column'], $ids)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Delete the structure, its descendants and all dependent rows in one transaction.
     *
     * @return array<string,int>  rows removed per dependent table (the deletion impact)
     */
    public function cascadeDelete(int $structureId): array
    {
        $ids = $this->descendantIds($structureId);
        $root = Structure::query()->find($structureId);

        return DB::transaction(function () use ($ids, $root): array {
            $impact = [];

            foreach ($this->dependencies->deletionOrder() as $dependent) {
                $removed = DB::table($dependent['table'])->whereIn($dependent['column'], $ids)->delete();
                if ($removed > 0) {
                    $impact[$dependent['table']] = ($impact[$dependent['table']] ?? 0) + $removed;
                }
            }

            if ($root) {
                $this->audit($root, $ids, $impact);
            }

            // Through the model so StructureObserver flushes the structure caches.
            Structure::destroy($ids);

            return $impact;
        });
    }

    /**
     * The structure id plus every descendant id (children, grandchildren, …), resolved
     * in memory from a single query — the structures table is small.
     *
     * @return array<int,int>
     */
    public function descendantIds(int $structureId): array
    {
        $childrenByParent = Structure::query()
            ->get(['id', 'parent_id'])
            ->groupBy('parent_id');

        $ids = [];
        $stack = [$structureId];

        while ($stack !== []) {
            $current = (int) array_pop($stack);
            if (in_array($current, $ids, true)) {
                continue;
            }
            $ids[] = $current;

            foreach ($childrenByParent->get($current, collect()) as $child) {
                $stack[] = (int) $child->id;
            }
        }

        return $ids;
    }

    /**
     * @param  array<int,int>  $ids
     * @param  array<string,int>  $impact
     */
    private function audit(Structure $root, array $ids, array $impact): void
    {
        activity('structures')
            ->causedBy(auth()->user())
            ->performedOn($root)
            ->event('cascade_deleted')
            ->withProperties([
                'structure' => $root->name,
                'structure_id' => $root->id,
                'descendant_structures' => max(0, count($ids) - 1),
                'deleted_rows' => $impact,
            ])
            ->log('structure.cascade_deleted');
    }
}

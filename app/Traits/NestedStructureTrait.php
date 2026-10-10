<?php

namespace App\Traits;

use App\Services\StructurePathService;
use App\Services\StructureService;

trait NestedStructureTrait
{
    /**
     * The unit and the units below it that the user may see — from one flat read of the
     * chart instead of a `withRecursive('subs')` query per level. A unit outside the
     * user's scope yields an empty list (fail closed), never its subtree.
     *
     * @return list<int>
     */
    public function getNestedStructure($id): array
    {
        $scope = resolve(StructureService::class)->scopeFor();

        if (! $scope->allows($id)) {
            return [];
        }

        return app(StructurePathService::class)->descendantIds((int) $id, $scope->isAll() ? null : $scope->ids());
    }
}

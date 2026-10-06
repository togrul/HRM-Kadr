<?php

namespace App\Traits;

use App\Services\StructurePathService;
use App\Services\StructureService;

trait NestedStructureTrait
{
    /**
     * The unit and the units below it that the user may see — from one flat read of the
     * chart instead of a `withRecursive('subs')` query per level.
     *
     * @return list<int>
     */
    public function getNestedStructure($id): array
    {
        $accessible = resolve(StructureService::class)->getAccessibleStructures();

        return app(StructurePathService::class)->descendantIds((int) $id, $accessible === [] ? null : $accessible);
    }
}

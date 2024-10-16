<?php

namespace App\Traits;

use App\Models\Structure;

trait NestedStructureTrait
{
    public function getNestedStructure($id): array
    {
        $structureModel = Structure::withRecursive('subs')->find($id);

        if ($structureModel) {
            return $structureModel->getAllNestedIds();
        }

        return [];
    }
}

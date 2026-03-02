<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Eloquent;

use App\Modules\Orders\Domain\Contracts\AccessibleStructureScopeReadRepository;
use App\Services\StructureService;

class StructureServiceAccessibleStructureScopeReadRepository implements AccessibleStructureScopeReadRepository
{
    public function __construct(
        private readonly StructureService $structureService,
    ) {}

    public function accessibleStructureIds(): array
    {
        return $this->structureService->getAccessibleStructures();
    }
}


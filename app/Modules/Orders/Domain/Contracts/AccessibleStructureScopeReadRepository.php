<?php

namespace App\Modules\Orders\Domain\Contracts;

interface AccessibleStructureScopeReadRepository
{
    /**
     * @return array<int,int>
     */
    public function accessibleStructureIds(): array;
}

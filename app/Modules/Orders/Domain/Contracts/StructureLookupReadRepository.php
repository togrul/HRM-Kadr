<?php

namespace App\Modules\Orders\Domain\Contracts;

use Illuminate\Support\Collection;

interface StructureLookupReadRepository
{
    public function mainStructures(?string $search = null): Collection;

    public function accessibleStructuresTree(?string $search = null): Collection;
}

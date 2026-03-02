<?php

namespace App\Modules\Orders\Domain\Contracts;

use Illuminate\Support\Collection;

interface RankPositionLookupReadRepository
{
    public function activeRanks(): Collection;

    public function positions(?string $search = null): Collection;
}


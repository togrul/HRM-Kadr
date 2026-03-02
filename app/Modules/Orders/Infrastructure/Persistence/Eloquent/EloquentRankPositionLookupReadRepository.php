<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Eloquent;

use App\Models\Position;
use App\Models\Rank;
use App\Modules\Orders\Domain\Contracts\RankPositionLookupReadRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentRankPositionLookupReadRepository implements RankPositionLookupReadRepository
{
    public function activeRanks(): Collection
    {
        return Rank::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    public function positions(?string $search = null): Collection
    {
        $normalized = trim((string) $search);

        return Position::query()
            ->when($normalized !== '', fn (Builder $query) => $query->where('name', 'LIKE', "%{$normalized}%"))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}


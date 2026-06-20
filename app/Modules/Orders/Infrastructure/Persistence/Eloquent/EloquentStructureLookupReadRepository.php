<?php

namespace App\Modules\Orders\Infrastructure\Persistence\Eloquent;

use App\Models\Structure;
use App\Modules\Orders\Domain\Contracts\StructureLookupReadRepository;
use Illuminate\Support\Collection;

class EloquentStructureLookupReadRepository implements StructureLookupReadRepository
{
    public function mainStructures(?string $search = null): Collection
    {
        $normalized = trim((string) $search);

        return Structure::query()
            ->with('parent')
            ->where('level', 0)
            ->when($normalized !== '', fn ($query) => $query->where('name', 'LIKE', "%{$normalized}%"))
            ->orderBy('id')
            ->get();
    }

    public function accessibleStructuresTree(?string $search = null): Collection
    {
        $query = Structure::query()
            ->withRecursive('subs')
            ->accessible()
            ->whereNotNull('parent_id')
            ->where('code', '<>', 0)
            ->orderBy('code');

        if ($search !== null && trim($search) !== '') {
            $query->where('name', 'LIKE', '%'.trim($search).'%');
        }

        return $query->get();
    }
}

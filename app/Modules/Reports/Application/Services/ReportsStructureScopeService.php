<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\Structure;
use App\Services\StructurePathService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportsStructureScopeService
{
    /**
     * @return array<int,int>
     */
    public function resolveIds(?int $structureId): array
    {
        if (! $structureId) {
            return [];
        }

        return Cache::remember(
            "reports-structure-scope:{$structureId}",
            now()->addMinutes(10),
            fn (): array => app(StructurePathService::class)->descendantIds($structureId) ?: [$structureId]
        );
    }

    /**
     * @return Collection<int,array{id:int,label:string}>
     */
    public function filterOptions(string $search = '', int $limit = 80): Collection
    {
        return Structure::query()
            ->select('id', DB::raw('name as label'))
            ->accessible()
            ->when(
                mb_strlen(trim($search)) >= 1,
                fn ($query) => $query->where('name', 'like', '%'.trim($search).'%')
            )
            ->orderBy('level')
            ->orderBy('code')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'label' => (string) $row->label]);
    }
}

<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\Structure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceStructureScopeReadService
{
    public function label(?int $structureId): ?string
    {
        if (! $structureId) {
            return null;
        }

        return Cache::remember(
            "attendance-structure-label:{$structureId}",
            now()->addMinutes(10),
            fn () => Structure::query()->whereKey($structureId)->value('name')
        );
    }

    /**
     * @return Collection<int,object>
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
            ->get();
    }
}

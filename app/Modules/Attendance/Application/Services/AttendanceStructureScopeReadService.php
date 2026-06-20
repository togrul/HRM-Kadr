<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\Structure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceStructureScopeReadService
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
            "attendance-structure-scope-ids:{$structureId}",
            now()->addMinutes(10),
            function () use ($structureId): array {
                $rows = Structure::query()->get(['id', 'parent_id']);
                $childrenByParent = [];

                foreach ($rows as $row) {
                    $parentId = $row->parent_id !== null ? (int) $row->parent_id : 0;
                    $childrenByParent[$parentId] ??= [];
                    $childrenByParent[$parentId][] = (int) $row->id;
                }

                $resolved = [];
                $stack = [$structureId];

                while ($stack !== []) {
                    $currentId = (int) array_pop($stack);
                    if (isset($resolved[$currentId])) {
                        continue;
                    }

                    $resolved[$currentId] = $currentId;

                    foreach ($childrenByParent[$currentId] ?? [] as $childId) {
                        if (! isset($resolved[$childId])) {
                            $stack[] = $childId;
                        }
                    }
                }

                return array_values($resolved);
            }
        );
    }

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

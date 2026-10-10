<?php

namespace App\Modules\Reports\Application\Services;

use App\Models\Structure;
use App\Services\StructurePathService;
use App\Services\StructureService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportsStructureScopeService
{
    /**
     * Struktur id-si — heç bir struktur bu id-ni daşımır; hesabat sorğularını boş nəticəyə
     * daraltmaq üçün «heç nə» işarəsi kimi işlədilir.
     */
    public const NOTHING = 0;

    /**
     * Hesabatın struktur filtri istifadəçinin struktur görünürlüyü ilə kəsişir (fail closed).
     *
     * `[]` — məhdudiyyət yoxdur: yalnız «bütün strukturlar» rolu və filtr verilməyibsə.
     * `[self::NOTHING]` — heç nə: görünürlük boşdur və ya istənilən struktur görünürlükdən
     * kənardadır. Əks halda görünən struktur id-ləri (seçilmiş struktur + alt strukturları).
     *
     * @return array<int,int>
     */
    public function resolveIds(?int $structureId): array
    {
        $scope = app(StructureService::class)->scopeFor();

        if (! $structureId) {
            if ($scope->isAll()) {
                return [];
            }

            return $scope->ids() !== [] ? $scope->ids() : [self::NOTHING];
        }

        if (! $scope->allows($structureId)) {
            return [self::NOTHING];
        }

        $ids = Cache::remember(
            "reports-structure-scope:{$structureId}",
            now()->addMinutes(10),
            fn (): array => app(StructurePathService::class)->descendantIds($structureId) ?: [$structureId]
        );

        if ($scope->isAll()) {
            return $ids;
        }

        $visible = array_values(array_intersect($ids, $scope->ids()));

        return $visible !== [] ? $visible : [self::NOTHING];
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

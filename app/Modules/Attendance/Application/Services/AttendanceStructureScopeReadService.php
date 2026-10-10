<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\Structure;
use App\Services\StructurePathService;
use App\Services\StructureService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AttendanceStructureScopeReadService
{
    /**
     * Struktur id-si — heç bir struktur bu id-ni daşımır; «heç nə» mənasında işlədilir ki,
     * `$structureIds !== []` yoxlayan mövcud oxu servisləri sorğunu boş nəticəyə daraltsın.
     */
    public const NOTHING = 0;

    /**
     * Seçilmiş struktur (və alt strukturları) istifadəçinin struktur görünürlüyü ilə kəsişir.
     *
     * Qaytarılan dəyər: `[]` — məhdudiyyət yoxdur (yalnız «bütün strukturlar» rolu və seçim
     * edilməyibsə); `[self::NOTHING]` — heç nə (görünürlük boşdur və ya seçilmiş struktur
     * görünürlükdən kənardadır); əks halda görünən struktur id-ləri. Fail closed.
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
            "attendance-structure-scope-ids:{$structureId}",
            now()->addMinutes(10),
            fn (): array => app(StructurePathService::class)->descendantIds($structureId) ?: [$structureId]
        );

        if ($scope->isAll()) {
            return $ids;
        }

        $visible = array_values(array_intersect($ids, $scope->ids()));

        return $visible !== [] ? $visible : [self::NOTHING];
    }

    /** İstifadəçi «bütün strukturlar» görür — keşlənmiş təşkilat üzrə aqreqatları paylaşmaq olar. */
    public function unrestricted(): bool
    {
        return app(StructureService::class)->scopeFor()->isAll();
    }

    /** Tabel nömrəsinin işçisi istifadəçinin struktur görünürlüyündədirmi (yazma əməliyyatları üçün). */
    public function allowsTabelNo(?string $tabelNo): bool
    {
        $tabelNo = trim((string) $tabelNo);

        if ($tabelNo === '') {
            return false;
        }

        $scope = app(StructureService::class)->scopeFor();

        if ($scope->isAll()) {
            return true;
        }

        $structureId = DB::table('personnels')->where('tabel_no', $tabelNo)->value('structure_id');

        return $scope->allows($structureId === null ? null : (int) $structureId);
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

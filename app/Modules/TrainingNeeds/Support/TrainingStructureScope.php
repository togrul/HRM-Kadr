<?php

namespace App\Modules\TrainingNeeds\Support;

use App\Models\Personnel;
use App\Models\User;
use App\Services\StructureScope;
use App\Services\StructureService;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * Təlim ehtiyacları modulunun struktur görünürlüyü: ehtiyac, profil, iştirakçı, keçirilmə
 * və rəy sətirləri işçinin strukturu ilə məhdudlaşır (fail closed).
 */
final class TrainingStructureScope
{
    public static function for(?User $user = null): StructureScope
    {
        return app(StructureService::class)->scopeFor($user);
    }

    /**
     * Sorğuya personnels cədvəli qoşulubsa (və ya Personnel sorğusudursa).
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public static function onPersonnelTable(Builder $query, ?User $user = null): Builder
    {
        return self::for($user)->constrain($query, 'personnels.structure_id');
    }

    /**
     * Sorğuda yalnız işçi id sütunu varsa (məs. training_need_items.personnel_id).
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public static function onPersonnelColumn(Builder $query, string $column, ?User $user = null): Builder
    {
        $scope = self::for($user);

        if ($scope->isAll()) {
            return $query;
        }

        if ($scope->isNone()) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $query->whereIn($column, Personnel::query()->select('personnels.id')->whereIn('personnels.structure_id', $scope->ids()));

        return $query;
    }

    /** İşçi istifadəçinin struktur görünürlüyündə deyilsə 403. */
    public static function ensurePersonnelVisible(int|string|null $personnelId, ?User $user = null): void
    {
        $structureId = $personnelId ? Personnel::query()->whereKey((int) $personnelId)->value('structure_id') : null;

        abort_unless($structureId !== null && self::for($user)->allows($structureId), 403);
    }
}

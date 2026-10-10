<?php

namespace App\Modules\Candidates\Support;

use App\Models\User;
use App\Services\StructureScope;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Namizəd modulunun struktur görünürlüyü: namizəd, vakansiya və tələbnamə öz
 * structure_id-si ilə, müraciət isə namizədin və vakansiyanın strukturu ilə məhdudlaşır.
 * Struktursuz qeyd yalnız «bütün strukturlar» bayraqlı rola görünür (fail closed).
 */
final class CandidateStructureScope
{
    public static function for(?User $user = null): StructureScope
    {
        return app(StructureService::class)->scopeFor($user);
    }

    /**
     * Öz structure_id sütunu olan cədvəllər (candidates, job_openings, job_requisitions).
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public static function constrainOwn(Builder $query, ?User $user = null): Builder
    {
        return self::for($user)->constrain($query, $query->getModel()->qualifyColumn('structure_id'));
    }

    /**
     * Müraciətlər: namizəd görünürlükdə olmalı, vakansiyanın strukturu varsa o da.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public static function constrainApplications(Builder $query, ?User $user = null): Builder
    {
        $scope = self::for($user);

        if ($scope->isAll()) {
            return $query;
        }

        if ($scope->isNone()) {
            $query->whereRaw('1 = 0');

            return $query;
        }

        $ids = $scope->ids();

        $query->whereHas('candidate', fn (Builder $candidate) => $candidate->whereIn('candidates.structure_id', $ids))
            ->where(fn (Builder $opening) => $opening
                ->whereNull($query->getModel()->qualifyColumn('job_opening_id'))
                ->orWhereHas('opening', fn (Builder $jobOpening) => $jobOpening
                    ->where(fn (Builder $structure) => $structure
                        ->whereNull('job_openings.structure_id')
                        ->orWhereIn('job_openings.structure_id', $ids))));

        return $query;
    }
}

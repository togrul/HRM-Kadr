<?php

namespace App\Modules\PerformanceEvaluation\Support;

use App\Models\PerformanceForm;
use App\Models\PerformanceTestAttempt;
use App\Models\PerformanceTestAttemptAnswer;
use App\Models\PerformanceTestSession;
use App\Models\Personnel;
use App\Models\User;
use App\Services\StructureScope;
use App\Services\StructureService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Performans modulunun struktur görünürlüyü: qiymətləndirmə formaları, test sessiyaları,
 * cəhdlər və 360° sorğuları işçinin strukturu ilə məhdudlaşır (fail closed).
 */
final class PerformanceStructureScope
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
     * Sorğuda yalnız işçi id sütunu varsa (məs. performance_forms.personnel_id).
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

    /**
     * @return EloquentBuilder<PerformanceForm>
     */
    public static function forms(?User $user = null): EloquentBuilder
    {
        return self::onPersonnelColumn(PerformanceForm::query(), 'performance_forms.personnel_id', $user);
    }

    /**
     * @return EloquentBuilder<PerformanceTestSession>
     */
    public static function sessions(?User $user = null): EloquentBuilder
    {
        return self::onPersonnelColumn(PerformanceTestSession::query(), 'performance_test_sessions.personnel_id', $user);
    }

    /**
     * @return EloquentBuilder<PerformanceTestAttempt>
     */
    public static function attempts(?User $user = null): EloquentBuilder
    {
        return PerformanceTestAttempt::query()
            ->whereIn('performance_test_attempts.performance_test_session_id', self::sessions($user)->select('performance_test_sessions.id'));
    }

    /**
     * @return EloquentBuilder<PerformanceTestAttemptAnswer>
     */
    public static function answers(?User $user = null): EloquentBuilder
    {
        return PerformanceTestAttemptAnswer::query()
            ->whereIn('performance_test_attempt_answers.performance_test_attempt_id', self::attempts($user)->select('performance_test_attempts.id'));
    }
}

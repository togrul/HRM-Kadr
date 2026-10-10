<?php

namespace App\Modules\Candidates\Policies;

use App\Models\Candidate;
use App\Models\CandidateApplication;
use App\Models\JobOpening;
use App\Models\User;
use App\Services\StructureService;

class CandidateApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-candidates');
    }

    public function view(User $user, ?CandidateApplication $application = null): bool
    {
        return $user->can('show-candidates') && $this->inScope($user, $application);
    }

    public function create(User $user): bool
    {
        return $user->can('candidate-applications.create') || $user->can('add-candidates');
    }

    public function transition(User $user, ?CandidateApplication $application = null): bool
    {
        return ($user->can('candidate-applications.transition') || $user->can('edit-candidates')) && $this->inScope($user, $application);
    }

    public function reject(User $user, ?CandidateApplication $application = null): bool
    {
        return ($user->can('candidate-applications.reject') || $user->can('edit-candidates')) && $this->inScope($user, $application);
    }

    public function appoint(User $user, ?CandidateApplication $application = null): bool
    {
        return ($user->can('candidate-applications.appoint') || $user->can('edit-candidates')) && $this->inScope($user, $application);
    }

    /**
     * Müraciət namizədin strukturu görünürlükdə olanda açılır; vakansiyanın strukturu varsa,
     * o da görünürlükdə olmalıdır. Struktursuz namizəd yalnız «bütün strukturlar» bayraqlı
     * rola görünür — siyahı sorğusu (CandidateStructureScope) ilə eyni qayda.
     */
    private function inScope(User $user, ?CandidateApplication $application): bool
    {
        if ($application === null) {
            return true;
        }

        $scope = app(StructureService::class)->scopeFor($user);

        if ($scope->isAll()) {
            return true;
        }

        $openingStructureId = $this->relatedStructureId($application, 'opening', JobOpening::class, $application->job_opening_id);

        return $scope->allows($this->relatedStructureId($application, 'candidate', Candidate::class, $application->candidate_id))
            && ($openingStructureId === null || $scope->allows($openingStructureId));
    }

    /**
     * Yüklənmiş əlaqədə structure_id varsa onu, yoxdursa (məhdud sütunlarla yüklənibsə)
     * bir sorğu ilə oxuyur.
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     */
    private function relatedStructureId(CandidateApplication $application, string $relation, string $modelClass, mixed $foreignId): ?int
    {
        if ($foreignId === null) {
            return null;
        }

        $related = $application->relationLoaded($relation) ? $application->getRelation($relation) : null;

        $structureId = $related !== null && array_key_exists('structure_id', $related->getAttributes())
            ? $related->getAttribute('structure_id')
            : $modelClass::query()->whereKey($foreignId)->value('structure_id');

        return $structureId === null ? null : (int) $structureId;
    }
}

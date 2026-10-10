<?php

namespace App\Modules\Candidates\Policies;

use App\Models\Candidate;
use App\Models\User;
use App\Services\StructureService;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-candidates');
    }

    public function view(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('show-candidates') && $this->inScope($user, $candidate);
    }

    public function create(User $user): bool
    {
        return $user->can('add-candidates');
    }

    public function update(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('edit-candidates') && $this->inScope($user, $candidate);
    }

    public function delete(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('delete-candidates') && $this->inScope($user, $candidate);
    }

    public function restore(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('delete-candidates') && $this->inScope($user, $candidate);
    }

    public function forceDelete(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('delete-candidates') && $this->inScope($user, $candidate);
    }

    public function export(User $user): bool
    {
        return $user->can('export-candidates');
    }

    /**
     * Namizəd yalnız onun strukturu istifadəçinin görünürlüyündə olanda açılır. Struktursuz
     * namizəd yalnız «bütün strukturlar» bayraqlı rola görünür (fail closed). Modelsiz
     * (sinif səviyyəli) yoxlama yalnız icazəyə baxır.
     */
    private function inScope(User $user, ?Candidate $candidate): bool
    {
        if ($candidate === null) {
            return true;
        }

        $scope = app(StructureService::class)->scopeFor($user);

        if ($scope->isAll()) {
            return true;
        }

        // Namizəd məhdud sütunlarla yüklənə bilər — structure_id yoxdursa bazadan oxunur.
        $structureId = array_key_exists('structure_id', $candidate->getAttributes())
            ? $candidate->structure_id
            : Candidate::withTrashed()->whereKey($candidate->getKey())->value('structure_id');

        return $scope->allows($structureId);
    }
}

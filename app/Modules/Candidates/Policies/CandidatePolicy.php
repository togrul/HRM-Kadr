<?php

namespace App\Modules\Candidates\Policies;

use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-candidates');
    }

    public function view(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('show-candidates');
    }

    public function create(User $user): bool
    {
        return $user->can('add-candidates');
    }

    public function update(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('edit-candidates');
    }

    public function delete(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('delete-candidates');
    }

    public function restore(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('delete-candidates');
    }

    public function forceDelete(User $user, ?Candidate $candidate = null): bool
    {
        return $user->can('delete-candidates');
    }

    public function export(User $user): bool
    {
        return $user->can('export-candidates');
    }
}

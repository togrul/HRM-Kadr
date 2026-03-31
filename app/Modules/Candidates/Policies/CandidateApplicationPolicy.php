<?php

namespace App\Modules\Candidates\Policies;

use App\Models\CandidateApplication;
use App\Models\User;

class CandidateApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-candidates');
    }

    public function view(User $user, ?CandidateApplication $application = null): bool
    {
        return $user->can('show-candidates');
    }

    public function create(User $user): bool
    {
        return $user->can('candidate-applications.create') || $user->can('add-candidates');
    }

    public function transition(User $user, ?CandidateApplication $application = null): bool
    {
        return $user->can('candidate-applications.transition') || $user->can('edit-candidates');
    }

    public function reject(User $user, ?CandidateApplication $application = null): bool
    {
        return $user->can('candidate-applications.reject') || $user->can('edit-candidates');
    }

    public function appoint(User $user, ?CandidateApplication $application = null): bool
    {
        return $user->can('candidate-applications.appoint') || $user->can('edit-candidates');
    }
}

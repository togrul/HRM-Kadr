<?php

namespace App\Modules\Personnel\Policies;

use App\Models\Personnel;
use App\Models\User;

class PersonnelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-personnels');
    }

    public function view(User $user, Personnel $personnel): bool
    {
        return $user->can('show-personnels');
    }

    public function create(User $user): bool
    {
        return $user->can('add-personnels');
    }

    public function update(User $user, Personnel $personnel): bool
    {
        return $user->can('edit-personnels') || $user->can('update-personnels');
    }

    public function delete(User $user, Personnel $personnel): bool
    {
        return $user->can('delete-personnels');
    }

    public function restore(User $user, Personnel $personnel): bool
    {
        return $user->can('delete-personnels');
    }

    public function forceDelete(User $user, Personnel $personnel): bool
    {
        return $user->can('delete-personnels');
    }

    public function export(User $user): bool
    {
        return $user->can('show-personnels');
    }
}

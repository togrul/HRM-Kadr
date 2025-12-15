<?php

namespace App\Modules\Vacation\Policies;

use App\Models\PersonnelVacation;
use App\Models\User;

class VacationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-vacations');
    }

    public function view(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('show-vacations');
    }

    public function create(User $user): bool
    {
        return $user->can('add-vacations');
    }

    public function update(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('edit-vacations');
    }

    public function delete(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('delete-vacations');
    }

    public function restore(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('delete-vacations');
    }

    public function forceDelete(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('delete-vacations');
    }

    public function export(User $user): bool
    {
        return $user->can('show-vacations');
    }
}

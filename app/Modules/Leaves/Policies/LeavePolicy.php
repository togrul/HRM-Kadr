<?php

namespace App\Modules\Leaves\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-leaves');
    }

    public function view(User $user, ?Leave $leave = null): bool
    {
        return $user->can('show-leaves');
    }

    public function create(User $user): bool
    {
        return $user->can('add-leaves');
    }

    public function update(User $user, ?Leave $leave = null): bool
    {
        return $user->can('edit-leaves');
    }

    public function delete(User $user, ?Leave $leave = null): bool
    {
        return $user->can('delete-leaves');
    }

    public function restore(User $user, ?Leave $leave = null): bool
    {
        return $user->can('delete-leaves');
    }

    public function forceDelete(User $user, ?Leave $leave = null): bool
    {
        return $user->can('delete-leaves');
    }

    public function export(User $user): bool
    {
        return $user->can('export-leaves');
    }
}

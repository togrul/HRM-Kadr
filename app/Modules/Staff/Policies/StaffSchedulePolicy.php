<?php

namespace App\Modules\Staff\Policies;

use App\Models\StaffSchedule;
use App\Models\User;

class StaffSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-staff');
    }

    public function view(User $user, StaffSchedule $staff): bool
    {
        return $user->can('show-staff');
    }

    public function create(User $user): bool
    {
        return $user->can('add-staff');
    }

    public function update(User $user, StaffSchedule $staff): bool
    {
        return $user->can('edit-staff');
    }

    public function delete(User $user, StaffSchedule $staff): bool
    {
        return $user->can('delete-staff');
    }

    public function restore(User $user, StaffSchedule $staff): bool
    {
        return $user->can('delete-staff');
    }

    public function forceDelete(User $user, StaffSchedule $staff): bool
    {
        return $user->can('delete-staff');
    }

    public function export(User $user): bool
    {
        return $user->can('show-staff');
    }
}

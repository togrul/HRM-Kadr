<?php

namespace App\Modules\BusinessTrips\Policies;

use App\Models\PersonnelBusinessTrip;
use App\Models\User;

class BusinessTripPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-business_trips');
    }

    public function view(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('show-business_trips');
    }

    public function create(User $user): bool
    {
        return $user->can('add-business_trips');
    }

    public function update(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('edit-business_trips');
    }

    public function delete(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('delete-business_trips');
    }

    public function restore(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('delete-business_trips');
    }

    public function forceDelete(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('delete-business_trips');
    }

    public function export(User $user): bool
    {
        return $user->can('show-business_trips');
    }
}

<?php

namespace App\Modules\BusinessTrips\Policies;

use App\Models\PersonnelBusinessTrip;
use App\Models\User;
use App\Services\StructureService;

/**
 * Qeyd səviyyəli yoxlamalar icazə ilə yanaşı işçinin strukturunun istifadəçinin struktur
 * görünürlüyündə olmasını tələb edir (fail closed).
 */
class BusinessTripPolicy
{
    public function __construct(private readonly StructureService $structures) {}

    public function viewAny(User $user): bool
    {
        return $user->can('show-business_trips');
    }

    public function view(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('show-business_trips') && $this->inScope($user, $trip);
    }

    public function create(User $user): bool
    {
        return $user->can('add-business_trips');
    }

    public function update(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('edit-business_trips') && $this->inScope($user, $trip);
    }

    public function delete(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('delete-business_trips') && $this->inScope($user, $trip);
    }

    public function restore(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('delete-business_trips') && $this->inScope($user, $trip);
    }

    public function forceDelete(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $user->can('delete-business_trips') && $this->inScope($user, $trip);
    }

    public function export(User $user): bool
    {
        return $user->can('export-business_trips');
    }

    private function inScope(User $user, PersonnelBusinessTrip $trip): bool
    {
        return $this->structures->allowsTabelNo($user, $trip->tabel_no);
    }
}

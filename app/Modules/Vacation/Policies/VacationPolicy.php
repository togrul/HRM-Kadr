<?php

namespace App\Modules\Vacation\Policies;

use App\Models\PersonnelVacation;
use App\Models\User;
use App\Services\StructureService;

/**
 * Qeyd səviyyəli yoxlamalar icazə ilə yanaşı işçinin strukturunun istifadəçinin struktur
 * görünürlüyündə olmasını tələb edir (fail closed).
 */
class VacationPolicy
{
    public function __construct(private readonly StructureService $structures) {}

    public function viewAny(User $user): bool
    {
        return $user->can('show-vacations');
    }

    public function view(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('show-vacations') && $this->inScope($user, $vacation);
    }

    public function create(User $user): bool
    {
        return $user->can('add-vacations');
    }

    public function update(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('edit-vacations') && $this->inScope($user, $vacation);
    }

    public function delete(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('delete-vacations') && $this->inScope($user, $vacation);
    }

    public function restore(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('delete-vacations') && $this->inScope($user, $vacation);
    }

    public function forceDelete(User $user, PersonnelVacation $vacation): bool
    {
        return $user->can('delete-vacations') && $this->inScope($user, $vacation);
    }

    public function export(User $user): bool
    {
        return $user->can('export-vacations');
    }

    private function inScope(User $user, PersonnelVacation $vacation): bool
    {
        return $this->structures->allowsTabelNo($user, $vacation->tabel_no);
    }
}

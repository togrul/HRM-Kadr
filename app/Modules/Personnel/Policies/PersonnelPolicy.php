<?php

namespace App\Modules\Personnel\Policies;

use App\Models\Personnel;
use App\Models\User;
use App\Services\StructureService;

/**
 * Qeyd səviyyəli yoxlamalar icazə ilə yanaşı işçinin strukturunun istifadəçinin struktur
 * görünürlüyündə olmasını tələb edir — siyahıda görünməyən işçi kartı, çapı, faylı və
 * redaktəsi URL ilə də açılmamalıdır.
 */
class PersonnelPolicy
{
    public function __construct(private readonly StructureService $structures) {}

    public function viewAny(User $user): bool
    {
        return $user->can('show-personnels');
    }

    public function view(User $user, Personnel $personnel): bool
    {
        return $user->can('show-personnels') && $this->inScope($user, $personnel);
    }

    public function create(User $user): bool
    {
        return $user->can('add-personnels');
    }

    public function update(User $user, Personnel $personnel): bool
    {
        return ($user->can('edit-personnels') || $user->can('update-personnels'))
            && $this->inScope($user, $personnel);
    }

    public function delete(User $user, Personnel $personnel): bool
    {
        return $user->can('delete-personnels') && $this->inScope($user, $personnel);
    }

    public function restore(User $user, Personnel $personnel): bool
    {
        return $user->can('delete-personnels') && $this->inScope($user, $personnel);
    }

    public function forceDelete(User $user, Personnel $personnel): bool
    {
        return $user->can('delete-personnels') && $this->inScope($user, $personnel);
    }

    public function export(User $user): bool
    {
        return $user->can('export-personnels');
    }

    private function inScope(User $user, Personnel $personnel): bool
    {
        return $this->structures->allowsPersonnel($user, $personnel);
    }
}

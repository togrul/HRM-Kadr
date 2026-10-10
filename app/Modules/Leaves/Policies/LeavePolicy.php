<?php

namespace App\Modules\Leaves\Policies;

use App\Models\Leave;
use App\Models\Personnel;
use App\Models\User;
use App\Services\StructureService;

/**
 * Qeyd səviyyəli yoxlamalar icazə ilə yanaşı işçinin strukturunun istifadəçinin
 * görünürlüyündə olmasını da tələb edir (fail closed). Qeydsiz (sinif səviyyəli) çağırış
 * yalnız icazəyə baxır — siyahı sorğusu özü görünürlüklə məhdudlaşdırılır.
 */
class LeavePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('show-leaves');
    }

    public function view(User $user, ?Leave $leave = null): bool
    {
        return $user->can('show-leaves') && $this->inScope($user, $leave);
    }

    public function create(User $user): bool
    {
        return $user->can('add-leaves');
    }

    public function update(User $user, ?Leave $leave = null): bool
    {
        return $user->can('edit-leaves') && ! $this->isCertificateLeave($leave) && $this->inScope($user, $leave);
    }

    public function delete(User $user, ?Leave $leave = null): bool
    {
        return $user->can('delete-leaves') && ! $this->isCertificateLeave($leave) && $this->inScope($user, $leave);
    }

    public function restore(User $user, ?Leave $leave = null): bool
    {
        return $user->can('delete-leaves') && ! $this->isCertificateLeave($leave) && $this->inScope($user, $leave);
    }

    public function forceDelete(User $user, ?Leave $leave = null): bool
    {
        return $user->can('delete-leaves') && ! $this->isCertificateLeave($leave) && $this->inScope($user, $leave);
    }

    public function export(User $user): bool
    {
        return $user->can('export-leaves');
    }

    /** Sick-certificate leaves are read-only here; the certificate register owns them. */
    private function isCertificateLeave(?Leave $leave): bool
    {
        return $leave !== null && $leave->isManagedBySickCertificate();
    }

    /** İcazənin işçisi istifadəçinin struktur görünürlüyündədirmi (işçisiz qeyd — yalnız «hamısı»). */
    private function inScope(User $user, ?Leave $leave): bool
    {
        if ($leave === null) {
            return true;
        }

        $scope = app(StructureService::class)->scopeFor($user);

        if ($scope->isAll()) {
            return true;
        }

        // Dar select-lə yüklənmiş işçi əlaqəsində structure_id olmaya bilər — onda ayrıca oxu.
        $personnel = $leave->relationLoaded('personnel') ? $leave->personnel : null;
        $structureId = $personnel !== null && array_key_exists('structure_id', $personnel->getAttributes())
            ? $personnel->getAttribute('structure_id')
            : Personnel::withTrashed()->where('tabel_no', $leave->tabel_no)->value('structure_id');

        return $scope->allows($structureId);
    }
}

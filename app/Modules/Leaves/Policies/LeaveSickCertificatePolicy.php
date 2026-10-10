<?php

namespace App\Modules\Leaves\Policies;

use App\Models\Leave;
use App\Models\LeaveSickCertificate;
use App\Models\Personnel;
use App\Models\User;
use App\Services\StructureService;

/**
 * The certificate register follows the leave permissions; the diagnosis has its own.
 */
class LeaveSickCertificatePolicy
{
    public const DIAGNOSIS_PERMISSION = 'view-medical-diagnosis';

    public function viewAny(User $user): bool
    {
        return $user->can('show-leaves');
    }

    public function view(User $user, ?LeaveSickCertificate $certificate = null): bool
    {
        return $user->can('show-leaves') && $this->inScope($user, $certificate);
    }

    public function create(User $user): bool
    {
        return $user->can('add-leaves');
    }

    public function update(User $user, ?LeaveSickCertificate $certificate = null): bool
    {
        return $user->can('edit-leaves') && $this->inScope($user, $certificate);
    }

    public function export(User $user): bool
    {
        return $user->can('export-leaves');
    }

    public function viewDiagnosis(User $user): bool
    {
        return $user->can(self::DIAGNOSIS_PERMISSION);
    }

    /** Vərəqənin işçisi istifadəçinin struktur görünürlüyündədirmi (fail closed). */
    private function inScope(User $user, ?LeaveSickCertificate $certificate): bool
    {
        if ($certificate === null) {
            return true;
        }

        $scope = app(StructureService::class)->scopeFor($user);

        if ($scope->isAll()) {
            return true;
        }

        $tabelNo = $certificate->relationLoaded('leave')
            ? $certificate->leave?->tabel_no
            : Leave::withTrashed()->whereKey($certificate->leave_id)->value('tabel_no');

        return filled($tabelNo)
            && $scope->allows(Personnel::withTrashed()->where('tabel_no', $tabelNo)->value('structure_id'));
    }
}

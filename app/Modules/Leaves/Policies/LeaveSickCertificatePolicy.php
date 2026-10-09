<?php

namespace App\Modules\Leaves\Policies;

use App\Models\LeaveSickCertificate;
use App\Models\User;

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
        return $user->can('show-leaves');
    }

    public function create(User $user): bool
    {
        return $user->can('add-leaves');
    }

    public function update(User $user, ?LeaveSickCertificate $certificate = null): bool
    {
        return $user->can('edit-leaves');
    }

    public function export(User $user): bool
    {
        return $user->can('export-leaves');
    }

    public function viewDiagnosis(User $user): bool
    {
        return $user->can(self::DIAGNOSIS_PERMISSION);
    }
}

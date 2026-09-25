<?php

namespace App\Modules\Attendance\Contracts;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Sanctioned cross-module surface for deciding a pending manual attendance entry
 * outside the Attendance screens (e.g. from the home page's attention tiles).
 *
 * @see \App\Modules\Attendance\Application\Services\ManualEntryApproverService
 */
interface ManualEntryApprover
{
    public function canApprove(User $user): bool;

    /**
     * @throws ValidationException when the entry can no longer be approved
     */
    public function approve(int $entryId, User $approver): void;

    /**
     * @throws ValidationException when the entry can no longer be rejected
     */
    public function reject(int $entryId, User $approver): void;
}

<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceManualEntry;
use App\Models\User;
use App\Modules\Attendance\Contracts\ManualEntryApprover;

class ManualEntryApproverService implements ManualEntryApprover
{
    public function __construct(
        private readonly AttendanceAuthorizationService $authorization,
        private readonly AttendanceManualEntryService $entries,
    ) {}

    public function canApprove(User $user): bool
    {
        return $this->authorization->can('attendance.manual.approve', $user);
    }

    public function approve(int $entryId, User $approver): void
    {
        abort_unless($this->canApprove($approver), 403);

        $this->entries->approve(AttendanceManualEntry::query()->findOrFail($entryId), (int) $approver->id);
    }

    public function reject(int $entryId, User $approver): void
    {
        abort_unless($this->canApprove($approver), 403);

        $this->entries->reject(AttendanceManualEntry::query()->findOrFail($entryId), (int) $approver->id);
    }
}

<?php

namespace App\Modules\Attendance\Application\Services;

use App\Models\AttendanceManualEntry;
use App\Models\User;
use App\Modules\Attendance\Contracts\ManualEntryApprover;
use Illuminate\Support\Facades\Validator;

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

    public function reject(int $entryId, User $approver, string $reason): void
    {
        abort_unless($this->canApprove($approver), 403);

        // Same rule as the attendance page's own reject (ManualEntries::reject).
        $reason = trim($reason);
        Validator::make(
            ['reason' => $reason],
            ['reason' => ['required', 'string', 'min:3', 'max:1000']],
            [],
            ['reason' => __('attendance::manual_entries.labels.reject_note')]
        )->validate();

        $this->entries->reject(AttendanceManualEntry::query()->findOrFail($entryId), (int) $approver->id, $reason);
    }
}

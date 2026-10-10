<?php

namespace App\Observers;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use App\Models\Personnel;
use App\Models\User;
use App\Modules\Attendance\Application\Services\AttendanceLeaveSyncService;
use App\Notifications\LeaveStatusChanged;
use App\Notifications\NewLeaveRequested;
use App\Services\Modules\ModuleState;
use App\Services\StructureService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;

class LeaveObserver
{
    public function created(Leave $leave): void
    {
        Notification::send($this->notificationRecipients($leave), new NewLeaveRequested($leave));

        $this->syncAttendance($leave);

        // Notify assigned personnel by email if available
        // $assignedEmail = $leave->assigned?->email;
        // if ($assignedEmail) {
        //     Notification::route('mail', $assignedEmail)->notify(new NewLeaveRequested($leave));
        // }
    }

    public function updated(Leave $leave): void
    {
        $this->syncAttendance($leave, $leave->getOriginal());

        if (! $leave->isDirty('status_id')) {
            return;
        }

        $statusId = (int) $leave->status_id;
        if (! in_array($statusId, [OrderStatusEnum::APPROVED->value, OrderStatusEnum::CANCELLED->value], true)) {
            return;
        }

        $notification = new LeaveStatusChanged($leave);

        Notification::send($this->notificationRecipients($leave), $notification);

        // Notify requester by email if we have it
        // $requesterEmail = $leave->personnel?->email;
        // if ($requesterEmail) {
        //     Notification::route('mail', $requesterEmail)->notify($notification);
        // }
    }

    public function deleted(Leave $leave): void
    {
        $this->syncAttendance($leave, $leave->getOriginal());
    }

    public function restored(Leave $leave): void
    {
        $this->syncAttendance($leave, $leave->getOriginal());
    }

    /**
     * @param  array<string,mixed>  $original
     */
    private function syncAttendance(Leave $leave, array $original = []): void
    {
        if (! app(ModuleState::class)->enabled('attendance')) {
            return;
        }

        app(AttendanceLeaveSyncService::class)->syncLeaveChange($leave, $original);
    }

    /**
     * get-notification icazəsi olan, struktur görünürlüyü icazənin işçisini əhatə edən
     * istifadəçilər — başqa strukturun işçisi haqqında bildiriş getməməlidir.
     */
    private function notificationRecipients(Leave $leave): EloquentCollection
    {
        $guard = config('auth.defaults.guard', 'web');

        $permissionExists = Permission::query()
            ->where('name', 'get-notification')
            ->where('guard_name', $guard)
            ->exists();

        if (! $permissionExists) {
            return new EloquentCollection;
        }

        $structureId = Personnel::withTrashed()->where('tabel_no', $leave->tabel_no)->value('structure_id');
        $structures = app(StructureService::class);

        return User::permission('get-notification')
            ->get()
            ->filter(fn (User $user): bool => $structures->allows($user, $structureId))
            ->values();
    }
}

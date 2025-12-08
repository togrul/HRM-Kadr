<?php

namespace App\Observers;

use App\Models\Leave;
use App\Models\User;
use App\Notifications\NewLeaveRequested;
use App\Notifications\LeaveStatusChanged;
use Illuminate\Support\Facades\Notification;
use App\Enums\OrderStatusEnum;

class LeaveObserver
{
    public function created(Leave $leave): void
    {
        // Notify admins
        $admins = User::role('admin')->permission('get-notification')->get();
        Notification::send($admins, new NewLeaveRequested($leave));

        // Notify assigned personnel by email if available
        // $assignedEmail = $leave->assigned?->email;
        // if ($assignedEmail) {
        //     Notification::route('mail', $assignedEmail)->notify(new NewLeaveRequested($leave));
        // }
    }

    public function updated(Leave $leave): void
    {
        if (! $leave->isDirty('status_id')) {
            return;
        }

        $statusId = (int) $leave->status_id;
        if (! in_array($statusId, [OrderStatusEnum::APPROVED->value, OrderStatusEnum::CANCELLED->value], true)) {
            return;
        }

        $notification = new LeaveStatusChanged($leave);

        // Notify admins
        $admins = User::role('admin')->permission('get-notification')->get();
        Notification::send($admins, $notification);

        // Notify requester by email if we have it
        // $requesterEmail = $leave->personnel?->email;
        // if ($requesterEmail) {
        //     Notification::route('mail', $requesterEmail)->notify($notification);
        // }
    }
}

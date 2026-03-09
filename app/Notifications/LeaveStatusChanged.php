<?php

namespace App\Notifications;

use App\Enums\OrderStatusEnum;
use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Leave $leave)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $type = $this->leave->leaveType?->name ?? __('notifications::common.categories.leave');
        $fullname = $this->leave->personnel?->fullname ?? $this->leave->tabel_no;
        $status = $this->statusLabel();

        return (new MailMessage)
            ->subject(__('notifications::common.mail.subject_leave_request_updated'))
            ->greeting(__('notifications::common.mail.hello'))
            ->line(__('notifications::common.mail.leave_request_status_line', ['type' => $type, 'status' => $status, 'fullname' => $fullname]))
            ->action(__('notifications::common.mail.view_leave'), url('/leaves'));
    }

    public function toArray(object $notifiable): array
    {
        $type = $this->leave->leaveType?->name ?? __('notifications::common.categories.leave');
        $fullname = $this->leave->personnel?->fullname ?? $this->leave->tabel_no;
        $status = $this->statusLabel();

        return [
            'type'       => class_basename(Leave::class),
            'name'       => $fullname,
            'action'     => 'leaveStatusChanged',
            'message'    => 'notifications::common.messages.leave_request_status_changed',
            'category'   => 'notifications::common.categories.leave',
            'leave_type' => $type,
            'tabel_no'   => $this->leave->tabel_no,
            'status'     => $status,
            'added_by'   => null
        ];
    }

    protected function statusLabel(): string
    {
        return match ((int) $this->leave->status_id) {
            OrderStatusEnum::APPROVED->value => __('notifications::common.status.approved'),
            OrderStatusEnum::CANCELLED->value => __('notifications::common.status.rejected'),
            default => __('notifications::common.status.updated'),
        };
    }
}

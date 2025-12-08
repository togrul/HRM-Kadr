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
        $type = $this->leave->leaveType?->name ?? __('Leave');
        $fullname = $this->leave->personnel?->fullname ?? $this->leave->tabel_no;
        $status = $this->statusLabel();

        return (new MailMessage)
            ->subject(__('Leave request updated'))
            ->greeting(__('Hello'))
            ->line(__(':type request :status', ['type' => $type, 'status' => $status]) . ' - ' . $fullname)
            ->action(__('View leave'), url('/leaves'));
    }

    public function toArray(object $notifiable): array
    {
        $type = $this->leave->leaveType?->name ?? __('Leave');
        $fullname = $this->leave->personnel?->fullname ?? $this->leave->tabel_no;
        $status = $this->statusLabel();

        return [
            'type'       => class_basename(Leave::class),
            'name'       => $fullname,
            'action'     => 'leaveStatusChanged',
            'leave_type' => $type,
            'tabel_no'   => $this->leave->tabel_no,
            'status'     => $status,
            'added_by'   => null
        ];
    }

    protected function statusLabel(): string
    {
        return match ((int) $this->leave->status_id) {
            OrderStatusEnum::APPROVED->value => __('approved'),
            OrderStatusEnum::CANCELLED->value => __('rejected'),
            default => __('updated'),
        };
    }
}

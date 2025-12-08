<?php

namespace App\Notifications;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeaveRequested extends Notification implements ShouldQueue
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
        $starts = optional($this->leave->starts_at)->format('d.m.Y');
        $ends = optional($this->leave->ends_at)->format('d.m.Y');

        return (new MailMessage)
            ->subject(__('New leave request'))
            ->greeting(__('Hello'))
            ->line(__('A new leave request was submitted.'))
            ->line("{$fullname} – {$type}")
            ->line(__('Dates').": {$starts} - {$ends}")
            ->action(__('View leave'), url('/leaves'));
    }

    public function toArray(object $notifiable): array
    {
        $type = $this->leave->leaveType?->name ?? __('Leave');
        $fullname = $this->leave->personnel?->fullname ?? $this->leave->tabel_no;

        return [
            'type'       => class_basename(Leave::class),
            'name'       => $fullname,
            'action'     => 'leave',
            'leave_type' => $type,
            'tabel_no'   => $this->leave->tabel_no,
            'status'     => $this->leave->status?->name,
            'added_by'   => null
        ];
    }
}

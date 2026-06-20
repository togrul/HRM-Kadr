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
        $type = $this->leave->leaveType?->name ?? __('notifications::common.categories.leave');
        $fullname = $this->leave->personnel?->fullname ?? $this->leave->tabel_no;
        $starts = optional($this->leave->starts_at)->format('d.m.Y');
        $ends = optional($this->leave->ends_at)->format('d.m.Y');
        $duration = $this->leave->durationDetailLabel();

        return (new MailMessage)
            ->subject(__('notifications::common.mail.subject_new_leave_request'))
            ->greeting(__('notifications::common.mail.hello'))
            ->line(__('notifications::common.mail.new_leave_request_submitted'))
            ->line("{$fullname} – {$type}")
            ->line(__('notifications::common.mail.dates').": {$starts} - {$ends}")
            ->line(__('leaves::common.labels.duration').": {$duration}")
            ->action(__('notifications::common.mail.view_leave'), url('/leaves'));
    }

    public function toArray(object $notifiable): array
    {
        $type = $this->leave->leaveType?->name ?? __('notifications::common.categories.leave');
        $fullname = $this->leave->personnel?->fullname ?? $this->leave->tabel_no;
        $starts = optional($this->leave->starts_at)->format('d.m.Y');
        $ends = optional($this->leave->ends_at)->format('d.m.Y');

        return [
            'type'       => class_basename(Leave::class),
            'name'       => $fullname,
            'action'     => 'leave',
            'message'    => 'notifications::common.messages.new_leave_request_created',
            'category'   => 'notifications::common.categories.leave',
            'leave_type' => $type,
            'duration_summary' => $this->leave->durationSummary(),
            'duration_window' => $this->leave->durationWindowLabel(),
            'leave_period' => trim(implode(' - ', array_filter([$starts, $ends]))),
            'tabel_no'   => $this->leave->tabel_no,
            'status'     => $this->leave->status?->name,
            'added_by'   => null
        ];
    }
}

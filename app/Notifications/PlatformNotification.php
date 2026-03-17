<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlatformNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $channel,
        public array $payload,
        public ?string $subject = null,
        public string $body = '',
    ) {}

    public function via(object $notifiable): array
    {
        return [$this->channel];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject ?: __('notifications::common.mail.subject_notification'))
            ->line($this->body !== '' ? $this->body : __('notifications::common.messages.has_notification'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }
}

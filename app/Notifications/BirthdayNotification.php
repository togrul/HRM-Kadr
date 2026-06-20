<?php

namespace App\Notifications;

use App\Models\Personnel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BirthdayNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Personnel $personnel,
        public string $channel = 'database',
        public ?string $renderedSubject = null,
        public string $renderedBody = '',
        public array $payload = [],
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [$this->channel];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->renderedSubject ?: __('notifications::common.mail.subject_birthday'))
            ->line($this->renderedBody !== '' ? $this->renderedBody : __('notifications::common.messages.birthday_today'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload !== []
            ? array_merge([
                'type' => 'Birthday',
                'action' => 'birthday',
            ], $this->payload)
            : [
            'type' => 'Birthday',
            'tabel_no' => $this->personnel->tabel_no,
            'name' => $this->personnel->fullname,
            'birthdate' => optional($this->personnel->birthdate)->format('Y-m-d'),
            'birthday_label' => optional($this->personnel->birthdate)->format('d.m.Y'),
            'position' => $this->personnel->position?->name,
            'structure' => $this->personnel->structure?->fullStructureName(),
            'message' => __('notifications::common.messages.birthday_today'),
            'category' => __('notifications::common.categories.birthday'),
            'action' => 'birthday',
        ];
    }
}

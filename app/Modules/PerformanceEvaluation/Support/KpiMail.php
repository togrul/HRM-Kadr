<?php

namespace App\Modules\PerformanceEvaluation\Support;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A KPI e-mail — one event, or the daily digest of several — sent through the queue.
 */
class KpiMail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, string>  $lines
     */
    public function __construct(
        public string $subject,
        public array $lines,
        public ?string $link = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject);

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        return $this->link ? $mail->action(__('performance_evaluation::kpi.mail.open'), $this->link) : $mail;
    }
}

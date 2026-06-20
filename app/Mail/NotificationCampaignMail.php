<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;

class NotificationCampaignMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $body,
        public bool $isHtml = false,
    ) {}

    public function build(): static
    {
        $mail = $this->subject($this->subjectLine);

        if ($this->isHtml) {
            return $mail->html(new HtmlString($this->body));
        }

        return $mail->view('mail.notification-template-preview-text', [
            'body' => $this->body,
        ]);
    }
}

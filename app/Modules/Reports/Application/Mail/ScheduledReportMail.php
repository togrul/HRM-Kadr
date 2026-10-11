<?php

namespace App\Modules\Reports\Application\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Planlı hesabat məktubu: qısa mətn və hesabat faylı qoşma kimi.
 */
class ScheduledReportMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $scheduleName,
        public string $reportTitle,
        public string $periodLabel,
        public string $ownerName,
        public string $attachmentContent,
        public string $attachmentName,
        public string $attachmentMime,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('reports::schedules.mail.subject', ['name' => $this->scheduleName, 'period' => $this->periodLabel]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'reports::mail.scheduled-report');
    }

    /**
     * @return array<int,Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->attachmentContent, $this->attachmentName)
                ->withMime($this->attachmentMime),
        ];
    }
}

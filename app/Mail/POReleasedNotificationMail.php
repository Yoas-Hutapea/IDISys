<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class POReleasedNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $poNumber,
        public string $poType,
        public string $poSubType,
        public string $mitraName,
        public string $amountFormatted,
        public string $status,
        public string $remarks,
        public ?string $pdfContent = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'IDEAsys Notification - PO Released: ' . $this->poNumber,
            from: config('mail.from.address', 'noreply@ideasy.com'),
            replyTo: [config('mail.from.address', 'noreply@ideasy.com')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.po_released_notification',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        if ($this->pdfContent !== null && $this->pdfContent !== '') {
            $pdfContent = $this->pdfContent;
            $safePoNumber = str_replace(['/', '\\', ':'], '-', $this->poNumber);
            $filename = 'Document PO - ' . $safePoNumber . '.pdf';
            $attachments[] = Attachment::fromData(fn () => $pdfContent, $filename)
                ->withMime('application/pdf');
        }
        return $attachments;
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email reminder for PR/PO Waiting approval: notifies the PIC (ReviewedBy or ApprovedBy)
 * who needs to perform the next approval step.
 */
class PRWaitingApprovalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $prNumber,
        public string $statusLabel,
        public string $recipientRole,
        public string $recipientName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'IDEAsys Reminder - ' . $this->statusLabel . ': ' . $this->prNumber,
            from: config('mail.from.address', 'noreply@ideasy.com'),
            replyTo: [config('mail.from.address', 'noreply@ideasy.com')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pr_waiting_approval_reminder',
        );
    }
}

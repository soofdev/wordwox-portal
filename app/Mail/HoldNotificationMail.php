<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HoldNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;

    /**
     * Create a new message instance.
     */
    public function __construct($emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match($this->emailData['notification_type']) {
            'created' => 'Your Membership Has Been Put On Hold',
            'ended' => 'Your Membership Hold Has Ended',
            'cancelled' => 'Your Membership Hold Has Been Cancelled',
            'modified' => 'Your Membership Hold Has Been Updated',
            default => 'Membership Hold Update'
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.hold-notification',
            with: [
                'memberName' => $this->emailData['member_name'],
                'planName' => $this->emailData['plan_name'],
                'startDate' => $this->emailData['start_date'],
                'endDate' => $this->emailData['end_date'],
                'notificationType' => $this->emailData['notification_type'],
                'holdNote' => $this->emailData['hold_note'],
                'orgName' => $this->emailData['org_name']
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

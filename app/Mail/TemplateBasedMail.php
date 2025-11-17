<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * TemplateBasedMail - Mailable for template-based email notifications
 * 
 * Handles sending emails using notification templates with both HTML and text versions.
 */
class TemplateBasedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $emailData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->emailData['fromEmail'] ?? config('mail.from.address'),
            subject: $this->emailData['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Determine which template to use
        $htmlTemplate = $this->emailData['template_html'] ?? 'emails.template-based-html';
        $textTemplate = $this->emailData['template_text'] ?? 'emails.template-based-text';

        return new Content(
            view: $htmlTemplate,
            text: $textTemplate,
            with: [
                'htmlData' => $this->emailData['htmlData'] ?? '',
                'textData' => $this->emailData['textData'] ?? '',
                'subject' => $this->emailData['subject'],
                'fromName' => $this->emailData['fromName'] ?? config('mail.from.name'),
                'orgName' => $this->emailData['orgName'] ?? config('app.name'),
                'templateId' => $this->emailData['template_id'] ?? null
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







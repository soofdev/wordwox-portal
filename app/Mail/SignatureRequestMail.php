<?php

namespace App\Mail;

use App\Models\OrgUser;
use App\Models\SignatureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignatureRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public SignatureRequest $signatureRequest,
        public OrgUser $orgUser,
        public string $signatureUrl
    ) {
        // Set the queue for email processing
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $gymName = $this->orgUser->org->name ?? 'Wodworx';
        
        return new Envelope(
            subject: "{$gymName} - Membership Agreement Signature Required",
            from: config('mail.from.address'),
            tags: ['signature-request'],
            metadata: [
                'org_id' => $this->orgUser->org_id,
                'org_user_id' => $this->orgUser->id,
                'signature_request_id' => $this->signatureRequest->id,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.signature-request',
            text: 'emails.signature-request-text',
            with: [
                'orgUser' => $this->orgUser,
                'gymName' => $this->orgUser->org->name ?? 'Wodworx',
                'signatureUrl' => $this->signatureUrl,
                'expiresAt' => $this->signatureRequest->expires_at,
                'signatureRequest' => $this->signatureRequest,
            ],
        );
    }
}

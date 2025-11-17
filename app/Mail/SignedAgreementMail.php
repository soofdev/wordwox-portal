<?php

namespace App\Mail;

use App\Models\OrgUser;
use App\Models\Signature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignedAgreementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public OrgUser $orgUser,
        public Signature $signature,
        public string $pdfUrl
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
            subject: "{$gymName} - Your Signed Membership Agreement",
            from: config('mail.from.address'),
            tags: ['signed-agreement'],
            metadata: [
                'org_id' => $this->orgUser->org_id,
                'org_user_id' => $this->orgUser->id,
                'signature_id' => $this->signature->id,
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            html: 'emails.signed-agreement',
            text: 'emails.signed-agreement-text',
            with: [
                'orgUser' => $this->orgUser,
                'gymName' => $this->orgUser->org->name ?? 'Wodworx',
                'pdfUrl' => $this->pdfUrl,
                'signature' => $this->signature,
                'signedAt' => $this->signature->created_at,
            ],
        );
    }
}

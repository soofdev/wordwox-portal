<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\TemplateBasedMail;

/**
 * SendTemplateEmailJob - Send templated email notifications
 * 
 * Handles sending email notifications using notification templates
 * with HTML and text versions, placeholder replacement, and error handling.
 */
class SendTemplateEmailJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $emailData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('SendTemplateEmailJob: Processing template email', [
                'to' => $this->emailData['to'],
                'subject' => $this->emailData['subject'],
                'template_id' => $this->emailData['template_id'] ?? null,
                'org_id' => $this->emailData['org_id'] ?? null
            ]);

            // Validate required data
            if (empty($this->emailData['to']) || empty($this->emailData['subject'])) {
                throw new \InvalidArgumentException('Missing required email data: to or subject');
            }

            // Send email using Laravel Mail
            Mail::to($this->emailData['to'])->send(new TemplateBasedMail($this->emailData));

            Log::info('SendTemplateEmailJob: Email sent successfully', [
                'to' => $this->emailData['to'],
                'subject' => $this->emailData['subject'],
                'template_id' => $this->emailData['template_id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('SendTemplateEmailJob: Failed to send email', [
                'to' => $this->emailData['to'] ?? 'unknown',
                'subject' => $this->emailData['subject'] ?? 'unknown',
                'template_id' => $this->emailData['template_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendTemplateEmailJob: Job failed permanently', [
            'to' => $this->emailData['to'] ?? 'unknown',
            'subject' => $this->emailData['subject'] ?? 'unknown',
            'template_id' => $this->emailData['template_id'] ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}







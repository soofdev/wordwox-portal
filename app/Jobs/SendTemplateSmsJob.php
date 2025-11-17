<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendSmsJob;

/**
 * SendTemplateSmsJob - Send templated SMS notifications
 * 
 * Handles sending SMS notifications using notification templates
 * with placeholder replacement and error handling.
 */
class SendTemplateSmsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $smsData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $smsData)
    {
        $this->smsData = $smsData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('SendTemplateSmsJob: Processing template SMS', [
                'to' => $this->smsData['to'],
                'from' => $this->smsData['from'],
                'template_id' => $this->smsData['template_id'] ?? null,
                'org_id' => $this->smsData['org_id'] ?? null
            ]);

            // Validate required data
            if (empty($this->smsData['to']) || empty($this->smsData['message'])) {
                throw new \InvalidArgumentException('Missing required SMS data: to or message');
            }

            // Dispatch to the existing SMS job
            SendSmsJob::dispatch(
                $this->smsData['to'],
                $this->smsData['message'],
                $this->smsData['from'] ?? config('sms.from_name', 'Wodworx')
            );

            Log::info('SendTemplateSmsJob: SMS dispatched successfully', [
                'to' => $this->smsData['to'],
                'template_id' => $this->smsData['template_id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('SendTemplateSmsJob: Failed to send SMS', [
                'to' => $this->smsData['to'] ?? 'unknown',
                'template_id' => $this->smsData['template_id'] ?? null,
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
        Log::error('SendTemplateSmsJob: Job failed permanently', [
            'to' => $this->smsData['to'] ?? 'unknown',
            'template_id' => $this->smsData['template_id'] ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}







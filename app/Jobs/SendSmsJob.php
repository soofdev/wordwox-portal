<?php

namespace App\Jobs;

use App\Models\LogSms;
use App\Models\OrgMsgItem;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;
    public int $tries;

    public function __construct(
        public string $provider,
        public string $from,
        public string $to,
        public string $message,
        public ?int $orgId = null,
        public ?int $orgUserId = null,
        public ?int $orgMsgItemId = null,
        public array $options = []
    ) {
        $smsConfig = config('sms.queue', []);
        $this->timeout = $smsConfig['timeout'] ?? 30;
        $this->tries = ($smsConfig['retry_attempts'] ?? 1) + 1; // +1 because tries includes initial attempt
    }

    /**
     * Execute the job.
     */
    public function handle(SmsService $smsService): void
    {
        try {
            Log::info('Processing SMS job', [
                'provider' => $this->provider,
                'to' => $this->to,
                'from' => $this->from,
                'org_id' => $this->orgId,
                'org_user_id' => $this->orgUserId,
            ]);

            // Update message item status to processing
            if ($this->orgMsgItemId) {
                $orgMsgItem = OrgMsgItem::find($this->orgMsgItemId);
                $orgMsgItem?->markAsProcessing();
            }

            // Send SMS immediately (synchronous within the job)
            $result = $smsService->sendNow(
                to: $this->to,
                message: $this->message,
                orgId: $this->orgId,
                orgUserId: $this->orgUserId,
                options: array_merge($this->options, [
                    'orgMsgItemId' => $this->orgMsgItemId,
                ])
            );

            if ($result->isSuccess()) {
                Log::info('SMS sent successfully', [
                    'provider' => $this->provider,
                    'to' => $this->to,
                    'message_id' => $result->messageId,
                    'cost' => $result->cost,
                ]);

                // Update message item status to sent
                if ($this->orgMsgItemId) {
                    $orgMsgItem = OrgMsgItem::find($this->orgMsgItemId);
                    $orgMsgItem?->markAsSent($result->rawResponse, $result->cost);
                }
            } else {
                Log::error('SMS sending failed', [
                    'provider' => $this->provider,
                    'to' => $this->to,
                    'error' => $result->message,
                    'raw_response' => $result->rawResponse,
                ]);

                // Update message item status to failed
                if ($this->orgMsgItemId) {
                    $orgMsgItem = OrgMsgItem::find($this->orgMsgItemId);
                    $orgMsgItem?->markAsFailed($result->rawResponse);
                }

                // Fail the job to trigger retry logic
                $this->fail(new \Exception($result->message));
            }

        } catch (\Exception $e) {
            Log::error('SMS job exception', [
                'provider' => $this->provider,
                'to' => $this->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update message item status to failed
            if ($this->orgMsgItemId) {
                $orgMsgItem = OrgMsgItem::find($this->orgMsgItemId);
                $orgMsgItem?->markAsFailed(['exception' => $e->getMessage()]);
            }

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SMS job failed permanently', [
            'provider' => $this->provider,
            'to' => $this->to,
            'from' => $this->from,
            'org_id' => $this->orgId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        // Create a failed SMS log entry
        try {
            LogSms::createFromSmsResult(
                gateway: $this->provider,
                orgId: $this->orgId ?? 0,
                orgUserId: $this->orgUserId,
                from: $this->from,
                to: $this->to,
                message: $this->message,
                status: 'failed',
                responseData: ['job_failed' => $exception->getMessage()],
                orgMsgItemId: $this->orgMsgItemId,
                visible: $this->options['visible'] ?? 'all'
            );
        } catch (\Exception $e) {
            Log::error('Failed to create SMS failure log', [
                'error' => $e->getMessage(),
                'original_error' => $exception->getMessage(),
            ]);
        }

        // Update message item status to failed
        if ($this->orgMsgItemId) {
            try {
                $orgMsgItem = OrgMsgItem::find($this->orgMsgItemId);
                $orgMsgItem?->markAsFailed(['job_failed' => $exception->getMessage()]);
            } catch (\Exception $e) {
                Log::error('Failed to update message item status', [
                    'error' => $e->getMessage(),
                    'org_msg_item_id' => $this->orgMsgItemId,
                ]);
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'sms',
            "provider:{$this->provider}",
            "org:{$this->orgId}",
            $this->to,
        ];
    }
}
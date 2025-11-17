<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\MyFatoorahPaymentApiService;

class InitiateMyFatoorahPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 3; // Retry 3 times on failure

    protected string $paymentUuid;
    protected string $membershipUuid;
    protected int $orgId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $paymentUuid, string $membershipUuid, int $orgId)
    {
        $this->paymentUuid = $paymentUuid;
        $this->membershipUuid = $membershipUuid;
        $this->orgId = $orgId;

        // Set queue name
        $this->onQueue('payments');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Initialize MyFatoorah API service
            $paymentApiService = app(MyFatoorahPaymentApiService::class);

            // Call MyFatoorah API
            $paymentResult = $paymentApiService->initiatePayment($this->paymentUuid);

            Log::info('InitiateMyFatoorahPaymentJob: API response received', [
                'payment_uuid' => $this->paymentUuid,
                'success' => $paymentResult['success'] ?? false,
                'has_data' => isset($paymentResult['data']),
                'status_code' => $paymentResult['status_code'] ?? null,
            ]);

            if (!isset($paymentResult['success']) || !$paymentResult['success']) {
                $errorMessage = $paymentResult['message'] ?? $paymentResult['error'] ?? 'Unknown error';

                Log::error('InitiateMyFatoorahPaymentJob: Payment initiation failed', [
                    'payment_uuid' => $this->paymentUuid,
                    'membership_uuid' => $this->membershipUuid,
                    'error' => $errorMessage,
                    'response' => $paymentResult,
                ]);

                // Throw exception to trigger retry logic (up to $tries attempts)
                throw new \Exception('MyFatoorah payment initiation failed: ' . $errorMessage);
            }

            // Extract payment URL from response
            $paymentUrl = $paymentResult['data']['payment_url'] ?? $paymentResult['data']['url'] ?? null;

            if ($paymentUrl) {
                // Update payment record with payment URL
                \Illuminate\Support\Facades\DB::table('orgInvoicePayment')
                    ->where('uuid', $this->paymentUuid)
                    ->update([
                        'pp' => $paymentUrl,
                        'gateway' => 'myfatoorah',
                        'updated_at' => time(),
                    ]);

                Log::info('InitiateMyFatoorahPaymentJob: Payment URL updated successfully', [
                    'payment_uuid' => $this->paymentUuid,
                    'payment_url' => 'SET',
                    'membership_uuid' => $this->membershipUuid,
                ]);
            } else {
                Log::warning('InitiateMyFatoorahPaymentJob: API success but no payment URL returned', [
                    'payment_uuid' => $this->paymentUuid,
                    'response_data' => $paymentResult['data'] ?? [],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('InitiateMyFatoorahPaymentJob: Exception during payment initiation', [
                'payment_uuid' => $this->paymentUuid,
                'membership_uuid' => $this->membershipUuid,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('InitiateMyFatoorahPaymentJob: Job failed after all retries', [
            'payment_uuid' => $this->paymentUuid,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace App\Jobs;

use App\Models\OrgUserPlan;
use App\Models\OrgInvoicePayment;
use App\Services\OrgUserPlanService;
use App\Enums\InvoiceStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Create Payment Records Job
 * 
 * Creates orgUserPlan, orgInvoice, and orgInvoicePayment records
 * after successful payment callback. Data is passed directly to job.
 */
class CreatePaymentRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 3; // Retry 3 times on failure

    protected array $membershipData;
    protected array $paymentData;
    protected float $planPrice;
    protected int $orgId;
    protected int $orgUserId;
    protected int $orgPlanId;
    protected string $paymentId;
    protected string $invoiceId;
    protected string $sessionKey;

    /**
     * Create a new job instance.
     * 
     * @param array $membershipData Membership data for creation
     * @param array $paymentData Payment data
     * @param float $planPrice Plan price
     * @param int $orgId Organization ID
     * @param int $orgUserId Organization User ID
     * @param int $orgPlanId Organization Plan ID
     * @param string $paymentId MyFatoorah Payment ID
     * @param string $invoiceId MyFatoorah Invoice ID
     * @param string $sessionKey Session key (for cleanup)
     */
    public function __construct(
        array $membershipData,
        array $paymentData,
        float $planPrice,
        int $orgId,
        int $orgUserId,
        int $orgPlanId,
        string $paymentId,
        string $invoiceId,
        string $sessionKey
    ) {
        $this->membershipData = $membershipData;
        $this->paymentData = $paymentData;
        $this->planPrice = $planPrice;
        $this->orgId = $orgId;
        $this->orgUserId = $orgUserId;
        $this->orgPlanId = $orgPlanId;
        $this->paymentId = $paymentId;
        $this->invoiceId = $invoiceId;
        $this->sessionKey = $sessionKey;
        
        // Set queue name for payments processing
        $this->onQueue('payments');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CreatePaymentRecordsJob: Starting record creation', [
            'session_key' => $this->sessionKey,
            'payment_id' => $this->paymentId,
            'invoice_id' => $this->invoiceId,
            'org_id' => $this->orgId,
            'org_user_id' => $this->orgUserId,
        ]);

        try {
            // Check if user already has an active plan before creating new one
            $existingMembership = OrgUserPlan::where('orgUser_id', $this->orgUserId)
                ->whereIn('status', [
                    OrgUserPlan::STATUS_ACTIVE,
                    OrgUserPlan::STATUS_UPCOMING,
                    OrgUserPlan::STATUS_PENDING,
                ])
                ->where('isCanceled', false)
                ->where('isDeleted', false)
                ->first();

            if ($existingMembership) {
                Log::warning('CreatePaymentRecordsJob: User already has active/upcoming/pending membership', [
                    'org_user_id' => $this->orgUserId,
                    'existing_membership_id' => $existingMembership->id,
                    'existing_plan_id' => $existingMembership->orgPlan_id,
                    'status' => $existingMembership->status,
                    'new_plan_id' => $this->orgPlanId,
                ]);
                
                // Don't create duplicate membership - log and exit
                // The payment was successful, but we won't create duplicate membership
                Log::info('CreatePaymentRecordsJob: Skipping membership creation due to existing active plan', [
                    'org_user_id' => $this->orgUserId,
                    'existing_membership_id' => $existingMembership->id,
                ]);
                return; // Exit without creating new membership
            }

            // Update membership data to ACTIVE/PAID status
            $this->membershipData['status'] = OrgUserPlan::STATUS_ACTIVE;
            $this->membershipData['invoiceStatus'] = OrgUserPlan::INVOICE_STATUS_PAID;
            $this->membershipData['note'] = 'Purchased online via customer portal';

            DB::beginTransaction();

            try {
                // Step 1: Create orgUserPlan (membership)
                $planService = app(OrgUserPlanService::class);
                $orgUserPlan = $planService->create($this->membershipData);

                Log::info('CreatePaymentRecordsJob: Created orgUserPlan', [
                    'membership_id' => $orgUserPlan->id,
                    'uuid' => $orgUserPlan->uuid,
                ]);

                // Step 2: Create orgInvoice
                $invoiceUuid = \Illuminate\Support\Str::uuid()->toString();
                $invoiceId = DB::table('orgInvoice')->insertGetId([
                    'uuid' => $invoiceUuid,
                    'org_id' => $this->orgId,
                    'orgUserPlan_id' => $orgUserPlan->id,
                    'orgUser_id' => $this->orgUserId,
                    'total' => $this->planPrice,
                    'totalPaid' => $this->planPrice, // Full payment received
                    'currency' => $this->paymentData['currency_iso'] ?? 'KWD',
                    'status' => InvoiceStatus::PAID->value,
                    'pp' => 'myfatoorah',
                    'isDeleted' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

                Log::info('CreatePaymentRecordsJob: Created orgInvoice', [
                    'invoice_id' => $invoiceId,
                    'uuid' => $invoiceUuid,
                ]);

                // Step 3: Create orgInvoicePayment
                $paymentUuid = \Illuminate\Support\Str::uuid()->toString();
                $dbPaymentId = DB::table('orgInvoicePayment')->insertGetId([
                    'uuid' => $paymentUuid,
                    'org_id' => $this->orgId,
                    'orgInvoice_id' => $invoiceId,
                    'amount' => $this->planPrice,
                    'currency' => $this->paymentData['currency_iso'] ?? 'KWD',
                    'method' => OrgInvoicePayment::METHOD_ONLINE,
                    'status' => OrgInvoicePayment::STATUS_PAID,
                    'gateway' => 'myfatoorah',
                    'pp' => 'myfatoorah',
                    'pp_id' => $this->paymentId,
                    'pp_number' => $this->invoiceId,
                    'paid_at' => time(),
                    'created_by' => $this->orgUserId,
                    'isCanceled' => 0,
                    'isDeleted' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);

                Log::info('CreatePaymentRecordsJob: Created orgInvoicePayment', [
                    'payment_id' => $dbPaymentId,
                    'uuid' => $paymentUuid,
                    'pp_id' => $this->paymentId,
                    'pp_number' => $this->invoiceId,
                ]);

                DB::commit();

                // Clear session data after successful creation (if session is available)
                try {
                    \Illuminate\Support\Facades\Session::forget($this->sessionKey);
                } catch (\Exception $e) {
                    // Session may not be available in queue context, that's okay
                    Log::info('CreatePaymentRecordsJob: Could not clear session (expected in queue)', [
                        'session_key' => $this->sessionKey,
                    ]);
                }

                Log::info('CreatePaymentRecordsJob: Successfully created all records', [
                    'membership_id' => $orgUserPlan->id,
                    'invoice_id' => $invoiceId,
                    'payment_id' => $dbPaymentId,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('CreatePaymentRecordsJob: Error creating records', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('CreatePaymentRecordsJob: Job failed', [
                'session_key' => $this->sessionKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}


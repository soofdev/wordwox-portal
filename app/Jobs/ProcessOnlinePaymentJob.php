<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\OrgSettingsPaymentGateway;

class ProcessOnlinePaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $membershipId;
    protected $membershipUuid;
    protected $orgId;
    protected $invoiceTotal;
    protected $orgUserId;

    /**
     * Create a new job instance.
     */
    public function __construct($membershipId, $membershipUuid, $orgId, $invoiceTotal, $orgUserId)
    {
        $this->membershipId = $membershipId;
        $this->membershipUuid = $membershipUuid;
        $this->orgId = $orgId;
        $this->invoiceTotal = $invoiceTotal;
        $this->orgUserId = $orgUserId;
        
        // Set queue name for payments processing
        $this->onQueue('payments');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessOnlinePaymentJob: Starting online payment processing', [
            'membership_id' => $this->membershipId,
            'membership_uuid' => $this->membershipUuid,
            'org_id' => $this->orgId,
        ]);

        try {
            // Check if MyFatoorah is available for this org
            $myFatoorahSettings = OrgSettingsPaymentGateway::getMyFatoorahForOrg($this->orgId);
            
            Log::info('ProcessOnlinePaymentJob: MyFatoorah availability check', [
                'org_id' => $this->orgId,
                'has_org_settings' => !is_null($myFatoorahSettings),
                'global_config_available' => !empty(config('services.myfatoorah.base_url')),
            ]);

            if (!$myFatoorahSettings) {
                Log::warning('ProcessOnlinePaymentJob: MyFatoorah not available for online payment', [
                    'org_id' => $this->orgId,
                    'membership_uuid' => $this->membershipUuid,
                ]);
                return;
            }

            // Step 1: Create orgInvoice first (if it doesn't exist)
            $invoice = DB::table('orgInvoice')
                ->where('orgUserPlan_id', $this->membershipId)
                ->where('isDeleted', 0)
                ->first();

            if (!$invoice) {
                // Create orgInvoice if it doesn't exist
                $invoiceId = DB::table('orgInvoice')->insertGetId([
                    'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'org_id' => $this->orgId,
                    'orgUserPlan_id' => $this->membershipId,
                    'orgUser_id' => $this->orgUserId,
                    'total' => $this->invoiceTotal,
                    'totalPaid' => 0, // No payment received yet for online payments
                    'currency' => $this->getOrgCurrency($this->orgId),
                    'status' => \App\Enums\InvoiceStatus::PENDING->value,
                    'pp' => 'myfatoorah',
                    'isDeleted' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                $invoice = (object)['id' => $invoiceId];
                
                Log::info('ProcessOnlinePaymentJob: Created orgInvoice', [
                    'invoice_id' => $invoiceId,
                    'membership_id' => $this->membershipId,
                ]);
            }

            // Step 2: Create orgInvoicePayment record with PENDING status
            $paymentUuid = \Illuminate\Support\Str::uuid()->toString();

            $paymentId = DB::table('orgInvoicePayment')->insertGetId([
                'uuid' => $paymentUuid,
                'org_id' => $this->orgId,
                'orgInvoice_id' => $invoice->id,
                'method' => 'online',
                'amount' => $this->invoiceTotal,
                'currency' => $this->getOrgCurrency($this->orgId),
                'status' => \App\Models\OrgInvoicePayment::STATUS_PENDING,
                'pp' => 'myfatoorah',
                'created_by' => $this->orgUserId, // Use the orgUser ID passed from the job
                'isCanceled' => 0,
                'isDeleted' => 0,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            Log::info('ProcessOnlinePaymentJob: Created orgInvoicePayment record', [
                'payment_id' => $paymentId,
                'payment_uuid' => $paymentUuid,
                'membership_uuid' => $this->membershipUuid,
                'invoice_id' => $invoice->id,
            ]);

            // Step 3: Dispatch MyFatoorah API call to queue
            InitiateMyFatoorahPaymentJob::dispatch(
                $paymentUuid,
                $this->membershipUuid,
                $this->orgId
            );

            Log::info('ProcessOnlinePaymentJob: MyFatoorah payment job dispatched to queue', [
                'payment_uuid' => $paymentUuid,
                'membership_uuid' => $this->membershipUuid,
                'payment_id' => $paymentId,
                'invoice_id' => $invoice->id,
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessOnlinePaymentJob: Exception during online payment processing', [
                'membership_uuid' => $this->membershipUuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw the exception to trigger job failure handling
            throw $e;
        }
    }

    /**
     * Get organization's default currency
     */
    protected function getOrgCurrency($orgId): string
    {
        // Try to get currency from organization settings first
        $org = \App\Models\Org::find($orgId);
        if ($org && $org->settings) {
            $settings = is_string($org->settings) ? json_decode($org->settings, true) : $org->settings;
            if (isset($settings['currency'])) {
                return $settings['currency'];
            }
        }

        // Try to get currency from org location/country
        if ($org && $org->sysCountry) {
            // Map countries to currencies
            $countryCurrencies = [
                'JO' => 'JOD', // Jordan
                'KW' => 'KWD', // Kuwait
                'SA' => 'SAR', // Saudi Arabia
                'AE' => 'AED', // UAE
                'QA' => 'QAR', // Qatar
                'BH' => 'BHD', // Bahrain
                'OM' => 'OMR', // Oman
                'US' => 'USD', // United States
                'GB' => 'GBP', // United Kingdom
                'EU' => 'EUR', // European Union
            ];

            $countryCode = $org->sysCountry->isoAlpha2 ?? null;
            if ($countryCode && isset($countryCurrencies[$countryCode])) {
                return $countryCurrencies[$countryCode];
            }
        }

        // Fallback to JOD as default
        return 'JOD';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessOnlinePaymentJob: Job failed', [
            'membership_uuid' => $this->membershipUuid,
            'membership_id' => $this->membershipId,
            'org_id' => $this->orgId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
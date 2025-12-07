<?php

namespace App\Livewire\Customer;

use App\Models\CmsPage;
use App\Models\OrgPlan;
use App\Models\OrgUser;
use App\Models\OrgSettingsPaymentGateway;
use App\Models\OrgUserPlan;
use App\Services\MyFatoorahPaymentApiService;
use App\Enums\InvoiceStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Customer Purchase Plan Component
 * 
 * Handles package purchase flow:
 * 1. Load plan details
 * 2. Call wodworx-pay API to create payment (NO database records)
 * 3. Get payment URL and redirect user
 * 4. Database records created in callback after successful payment
 */
class PurchasePlan extends Component
{
    public $planUuid;
    public $plan;
    public $paymentUrl;
    public $loading = false;
    public $error = null;
    public $navigationPages;
    public $showFreeConfirmation = false;
    
    public function mount($plan = null)
    {
        // Show loading state initially
        $this->loading = true;
        
        // Load navigation pages (can be deferred but keeping for now)
        $this->loadNavigationPages();
        
        // Get plan UUID from query parameter
        $this->planUuid = $plan ?? request()->query('plan');
        
        if (!$this->planUuid) {
            $this->error = 'No plan specified.';
            $this->loading = false;
            return;
        }
        
        // Check if user is authenticated
        if (!Auth::check()) {
            // Redirect to login with redirect parameter
            return redirect()->route('login', ['redirect' => request()->fullUrl()]);
        }
        
        // Load plan details (fast - just database query)
        $this->loadPlan();
        
        if (!$this->plan) {
            $this->error = 'Plan not found.';
            $this->loading = false;
            return;
        }
        
        // Check if plan is free (zero price) - must check BEFORE trying to create payment
        if ($this->plan->price == 0 || $this->plan->price == 0.00 || $this->plan->price < 0.1) {
            // For free plans, show checkout page with "Complete order" button (no payment gateway)
            $this->showFreeConfirmation = true;
            $this->loading = false;
            return;
        }
        
        // Check if user already has an active plan (only for paid plans)
        $this->checkExistingMembership();
        if ($this->error) {
            $this->loading = false;
            return; // Error message already set by checkExistingMembership
        }
        
        // Check if MyFatoorah is configured
        if (!$this->isMyFatoorahAvailable()) {
            $this->error = 'Online payment is not available at this time.';
            $this->loading = false;
            return;
        }
        
        // For paid plans, start payment creation immediately (non-blocking)
        // Page will render immediately, payment URL will update when ready
        $this->createPayment();
    }
    
    /**
     * Load navigation pages for navbar
     */
    protected function loadNavigationPages()
    {
        $orgId = env('CMS_DEFAULT_ORG_ID', 8);
        // Cache navigation pages for 1 hour to improve performance
        $this->navigationPages = \Illuminate\Support\Facades\Cache::remember(
            "navigation_pages_{$orgId}",
            3600,
            function () use ($orgId) {
                return CmsPage::where('org_id', $orgId)
                    ->where('status', 'published')
                    ->where('show_in_navigation', true)
                    ->orderBy('sort_order')
                    ->get();
            }
        );
    }
    
    /**
     * Load plan details
     */
    protected function loadPlan()
    {
        try {
            // Try to find by UUID first
            $this->plan = OrgPlan::where('uuid', $this->planUuid)
                ->orWhere('id', $this->planUuid)
                ->first();
                
            if (!$this->plan) {
                Log::warning('PurchasePlan: Plan not found', [
                    'plan_uuid' => $this->planUuid,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PurchasePlan: Error loading plan', [
                'plan_uuid' => $this->planUuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Check if MyFatoorah is available for this org
     */
    protected function isMyFatoorahAvailable(): bool
    {
        $orgId = Auth::user()->orgUser->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
        $settings = OrgSettingsPaymentGateway::getMyFatoorahForOrg($orgId);
        return !is_null($settings);
    }
    
    /**
     * Check for existing active memberships
     * Prevents duplicate purchases of the same plan
     */
    protected function checkExistingMembership()
    {
        $user = Auth::user();
        if (!$user->orgUser_id) {
            Log::info('PurchasePlan: No orgUser_id found for user', [
                'user_id' => $user->id,
            ]);
            return;
        }
        
        $orgUser = OrgUser::find($user->orgUser_id);
        if (!$orgUser) {
            Log::warning('PurchasePlan: OrgUser not found', [
                'orgUser_id' => $user->orgUser_id,
            ]);
            return;
        }
        
        // Check for active, upcoming, or pending membership for this specific plan
        $existingMembership = \App\Models\OrgUserPlan::where('orgUser_id', $orgUser->id)
            ->where('orgPlan_id', $this->plan->id)
            ->whereIn('status', [
                \App\Models\OrgUserPlan::STATUS_ACTIVE,
                \App\Models\OrgUserPlan::STATUS_UPCOMING,
                \App\Models\OrgUserPlan::STATUS_PENDING,
            ])
            ->where('isCanceled', false)
            ->where('isDeleted', false)
            ->first();
        
        Log::info('PurchasePlan: Checking existing membership', [
            'orgUser_id' => $orgUser->id,
            'plan_id' => $this->plan->id,
            'found_membership' => $existingMembership ? true : false,
            'membership_status' => $existingMembership->status ?? null,
        ]);
            
        if ($existingMembership) {
            $status = $existingMembership->status;
            
            if ($status == \App\Models\OrgUserPlan::STATUS_ACTIVE) {
                $this->error = 'You already have an active membership for this plan.';
                Log::warning('PurchasePlan: Active membership exists', [
                    'membership_id' => $existingMembership->id,
                    'plan_id' => $this->plan->id,
                ]);
            } elseif ($status == \App\Models\OrgUserPlan::STATUS_UPCOMING) {
                $this->error = 'You already have an upcoming membership for this plan. Please wait for it to start or contact support.';
                Log::warning('PurchasePlan: Upcoming membership exists', [
                    'membership_id' => $existingMembership->id,
                    'plan_id' => $this->plan->id,
                ]);
            } else {
                // PENDING status (6) or other status
                $this->error = 'You already have a pending membership for this plan. Please complete the payment or wait for it to be processed.';
                Log::warning('PurchasePlan: Pending membership exists', [
                    'membership_id' => $existingMembership->id,
                    'plan_id' => $this->plan->id,
                    'status' => $status,
                ]);
            }
        }
    }
    
    /**
     * Create payment via API and get payment URL
     * 
     * Strategy: Store payment details in session, create records AFTER callback success via queue job.
     */
    protected function createPayment()
    {
        // Don't set loading state - page should show immediately
        try {
            $user = Auth::user();
            $orgUser = $user->orgUser;
            
            if (!$orgUser) {
                $this->error = 'User information not found.';
                $this->loading = false;
                return;
            }
            
            // Note: We don't check for ANY active plan here because users should be able to purchase
            // different plans even if they have an active membership for another plan.
            // The checkExistingMembership() method already checks for the SPECIFIC plan being purchased.
            
            $orgId = $orgUser->org_id;
            $orgSettings = OrgSettingsPaymentGateway::getMyFatoorahForOrg($orgId);
            
            if (!$orgSettings) {
                $this->error = 'Payment gateway not configured for this organization.';
                $this->loading = false;
                return;
            }
            
            // Prepare membership data for creation after payment success
            $membershipData = [
                'org_id' => $orgId,
                'orgUser_id' => $orgUser->id,
                'orgPlan_id' => $this->plan->id,
                'invoiceStatus' => OrgUserPlan::INVOICE_STATUS_PAID, // Will be PAID after success
                'invoiceMethod' => 'online',
                'status' => OrgUserPlan::STATUS_ACTIVE, // Will be ACTIVE after success
                'created_by' => $orgUser->id,
                'sold_by' => $orgUser->id,
                'note' => 'Purchased online via customer portal',
                'startDateLoc' => now()->format('Y-m-d'),
            ];
            
            // Prepare payment data for API
            // Use same data source as FOH: orgUser.fullName and orgUser phone fields
            $customerName = $orgUser->fullName ?? '';
            $customerEmail = $orgUser->email ?? $user->email ?? '';
            
            // Format phone number same as FOH: CONCAT('+(', phoneCountry, ') ', phoneNumber)
            $customerPhone = null;
            if ($orgUser->phoneCountry && $orgUser->phoneNumber) {
                $customerPhone = '+(' . $orgUser->phoneCountry . ') ' . $orgUser->phoneNumber;
            }
            
            $paymentData = [
                'org_id' => $orgId,
                'payment_method_id' => 2, // Default to Visa/Mastercard
                'invoice_value' => (float) $this->plan->price,
                'customer_name' => $customerName,
                'currency_iso' => $orgSettings->currency ?? 'KWD',
                'language' => 'en',
                'membership_data' => $membershipData, // Include membership data for callback
            ];
            
            // Add optional fields (same as FOH)
            if ($customerEmail) {
                $paymentData['customer_email'] = $customerEmail;
            }
            
            // Phone number is not included in payment link creation
            // MyFatoorah will collect phone number during checkout if needed
            // But we have it available if needed: $customerPhone
            
            // Add plan information for callback
            $paymentData['org_plan_id'] = $this->plan->id;
            $paymentData['org_user_id'] = $orgUser->id;
            $paymentData['plan_name'] = $this->plan->name;
            
            // Add callback URL for payment success - MUST be Laravel portal URL
            $callbackUrl = route('payment.callback', [
                'org_id' => $orgId,
                'plan' => $this->plan->uuid,
            ], true);
            
            // Add error URL (same as callback URL - will handle errors too)
            $errorUrl = $callbackUrl;
            
            // Ensure URLs are absolute and use HTTPS in production
            if (str_starts_with($callbackUrl, 'http://') && !app()->environment('local')) {
                $callbackUrl = str_replace('http://', 'https://', $callbackUrl);
                $errorUrl = str_replace('http://', 'https://', $errorUrl);
            }
            
            // Set callback and error URLs in payment data
            // These will be used by wodworx-pay service: 
            // 'CallBackUrl' => $paymentData['callback_url'] ?? $this->config['callback_url']
            // 'ErrorUrl' => $paymentData['error_url'] ?? $this->config['error_url']
            $paymentData['callback_url'] = $callbackUrl;
            $paymentData['error_url'] = $errorUrl;
            $paymentData['return_url'] = $callbackUrl; // Also set return_url for compatibility
            
            Log::info('PurchasePlan: Creating payment via API with callback and error URLs', [
                'org_id' => $orgId,
                'plan_id' => $this->plan->id,
                'invoice_value' => $paymentData['invoice_value'],
                'callback_url' => $callbackUrl,
                'error_url' => $errorUrl,
                'return_url' => $callbackUrl,
                'callback_url_set' => !empty($paymentData['callback_url']),
                'error_url_set' => !empty($paymentData['error_url']),
            ]);
            
            // Step 2: Call API to create payment
            $paymentService = app(MyFatoorahPaymentApiService::class);
            $result = $paymentService->createPayment($paymentData);
            
            if ($result['success'] && isset($result['data']['payment_url'])) {
                $this->paymentUrl = $result['data']['payment_url'];
                $this->loading = false; // Stop loading when payment URL is ready
                
                // Get payment gateway IDs from response
                $paymentId = $result['data']['payment_id'] ?? $result['data']['PaymentId'] ?? null;
                $invoiceId = $result['data']['invoice_id'] ?? $result['data']['Id'] ?? null;
                
                // Step 3: Store all payment details in session for callback
                $sessionIdentifier = $invoiceId ?? $paymentId ?? uniqid('payment_', true);
                $sessionKey = 'payment_pending_' . $sessionIdentifier;
                
                $sessionData = [
                    'org_id' => $orgId,
                    'org_user_id' => $orgUser->id,
                    'org_plan_id' => $this->plan->id,
                    'plan_uuid' => $this->plan->uuid,
                    'plan_name' => $this->plan->name,
                    'plan_price' => $this->plan->price,
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoiceId,
                    'payment_url' => $this->paymentUrl,
                    'membership_data' => $membershipData,
                    'payment_data' => $paymentData,
                    'created_at' => now()->toIso8601String(),
                    'expires_at' => now()->addHours(24)->toIso8601String(), // 24 hour expiration
                ];
                
                // Store in session (for same-browser scenarios)
                \Illuminate\Support\Facades\Session::put($sessionKey, $sessionData);
                
                // Also store in cache (accessible from callback even without session)
                // Cache for 24 hours to match session expiration
                \Illuminate\Support\Facades\Cache::put($sessionKey, $sessionData, now()->addHours(24));
                
                Log::info('PurchasePlan: Stored payment data in session and cache', [
                    'session_key' => $sessionKey,
                    'session_identifier' => $sessionIdentifier,
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoiceId,
                    'cache_key' => $sessionKey,
                ]);
                
                Log::info('PurchasePlan: Payment URL received', [
                    'payment_url' => $this->paymentUrl,
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoiceId,
                    'session_key' => $sessionKey,
                ]);
                
                // Don't redirect - display page with package details and iframe
                // The view will render with $paymentUrl and $plan properties
            } else {
                $this->error = 'Failed to create payment. Please try again.';
                $this->loading = false;
                Log::error('PurchasePlan: Payment creation failed', [
                    'result' => $result,
                ]);
            }
            
        } catch (\Exception $e) {
            $this->error = 'An error occurred while processing your payment. Please try again.';
            $this->loading = false;
            Log::error('PurchasePlan: Error creating payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    /**
     * Check if user has any active, upcoming, or pending membership
     * 
     * @param OrgUser $orgUser The organization user to check
     * @return array|null Returns array with 'exists' => true and membership details if found, null otherwise
     */
    protected function checkActivePlan($orgUser)
    {
        // Check for any active, upcoming, or pending membership (any plan)
        $existingMembership = \App\Models\OrgUserPlan::where('orgUser_id', $orgUser->id)
            ->whereIn('status', [
                \App\Models\OrgUserPlan::STATUS_ACTIVE,
                \App\Models\OrgUserPlan::STATUS_UPCOMING,
                \App\Models\OrgUserPlan::STATUS_PENDING,
            ])
            ->where('isCanceled', false)
            ->where('isDeleted', false)
            ->first();
        
        if ($existingMembership) {
            Log::info('PurchasePlan: Active membership found', [
                'orgUser_id' => $orgUser->id,
                'existing_membership_id' => $existingMembership->id,
                'existing_plan_id' => $existingMembership->orgPlan_id,
                'status' => $existingMembership->status,
            ]);
            
            return [
                'exists' => true,
                'membership' => $existingMembership,
                'status' => $existingMembership->status,
            ];
        }
        
        return null;
    }
    
    /**
     * Confirm and create free membership
     * 
     * For free plans (price = 0), creates orgUserPlan, orgInvoice, and orgInvoicePayment
     * immediately when user clicks "Complete order" button.
     */
    public function confirmFreeMembership()
    {
        $this->loading = true;
        
        try {
            $user = Auth::user();
            $orgUser = $user->orgUser;
            
            if (!$orgUser) {
                $this->error = 'User information not found.';
                $this->loading = false;
                return;
            }
            
            // Check if user already has an active plan
            $activePlanCheck = $this->checkActivePlan($orgUser);
            if ($activePlanCheck && $activePlanCheck['exists']) {
                $status = $activePlanCheck['status'];
                if ($status == \App\Models\OrgUserPlan::STATUS_ACTIVE) {
                    $this->error = 'You already have an active membership. Please wait for it to expire or contact support.';
                } elseif ($status == \App\Models\OrgUserPlan::STATUS_UPCOMING) {
                    $this->error = 'You already have an upcoming membership. Please wait for it to start or contact support.';
                } else {
                    $this->error = 'You already have a pending membership. Please complete the payment or wait for it to be processed.';
                }
                $this->loading = false;
                return;
            }
            
            $orgId = $orgUser->org_id;
            
            Log::info('PurchasePlan: Creating free membership with invoice and payment', [
                'org_id' => $orgId,
                'plan_id' => $this->plan->id,
                'orgUser_id' => $orgUser->id,
                'plan_price' => $this->plan->price,
            ]);
            
            DB::beginTransaction();
            
            try {
                // Step 1: Create orgUserPlan (membership)
                $planService = app(\App\Services\OrgUserPlanService::class);
                
                $membershipData = [
                    'org_id' => $orgId,
                    'orgUser_id' => $orgUser->id,
                    'orgPlan_id' => $this->plan->id,
                    'invoiceStatus' => \App\Models\OrgUserPlan::INVOICE_STATUS_FREE,
                    'invoiceMethod' => 'free',
                    'status' => \App\Models\OrgUserPlan::STATUS_ACTIVE,
                    'created_by' => $orgUser->id,
                    'sold_by' => $orgUser->id,
                    'note' => 'Purchased online via customer portal (Free plan)',
                    'startDateLoc' => now()->format('Y-m-d'),
                ];
                
                $orgUserPlan = $planService->create($membershipData);
                
                Log::info('PurchasePlan: Created free orgUserPlan', [
                    'membership_id' => $orgUserPlan->id,
                    'uuid' => $orgUserPlan->uuid,
                ]);
                
                // Step 2: Create orgInvoice
                $invoiceUuid = \Illuminate\Support\Str::uuid()->toString();
                $invoiceId = DB::table('orgInvoice')->insertGetId([
                    'uuid' => $invoiceUuid,
                    'org_id' => $orgId,
                    'orgUserPlan_id' => $orgUserPlan->id,
                    'orgUser_id' => $orgUser->id,
                    'total' => 0, // Free plan - zero amount
                    'totalPaid' => 0, // Free plan - zero amount
                    'currency' => $this->plan->currency ?? 'KWD',
                    'status' => \App\Enums\InvoiceStatus::FREE->value, // FREE status for free plans
                    'pp' => null, // No payment gateway for free plans
                    'isDeleted' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                
                Log::info('PurchasePlan: Created free orgInvoice', [
                    'invoice_id' => $invoiceId,
                    'uuid' => $invoiceUuid,
                ]);
                
                // Step 3: Create orgInvoicePayment
                $paymentUuid = \Illuminate\Support\Str::uuid()->toString();
                $dbPaymentId = DB::table('orgInvoicePayment')->insertGetId([
                    'uuid' => $paymentUuid,
                    'org_id' => $orgId,
                    'orgInvoice_id' => $invoiceId,
                    'amount' => 0, // Free plan - zero amount
                    'currency' => $this->plan->currency ?? 'KWD',
                    'method' => \App\Models\OrgInvoicePayment::METHOD_FREE,
                    'status' => \App\Models\OrgInvoicePayment::STATUS_PAID, // PAID status (free = paid)
                    'gateway' => null, // No payment gateway
                    'pp' => null, // No payment provider
                    'paid_at' => time(),
                    'created_by' => $orgUser->id,
                    'isCanceled' => 0,
                    'isDeleted' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
                
                Log::info('PurchasePlan: Created free orgInvoicePayment', [
                    'payment_id' => $dbPaymentId,
                    'uuid' => $paymentUuid,
                ]);
                
                DB::commit();
                
                Log::info('PurchasePlan: Free membership, invoice, and payment created successfully', [
                    'membership_id' => $orgUserPlan->id,
                    'invoice_id' => $invoiceId,
                    'payment_id' => $dbPaymentId,
                ]);
                
                // Redirect to success page with reference (format: plan_{plan_uuid}_{orgUser_id})
                $ref = 'plan_' . $this->plan->uuid . '_' . $orgUser->id;
                return redirect()->route('payment.success', ['ref' => $ref]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (\Exception $e) {
            $this->error = 'An error occurred while processing your membership. Please try again.';
            Log::error('PurchasePlan: Error creating free membership', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            $this->loading = false;
        }
    }
    
    /**
     * Get supported countries list for country code dropdown
     */
    public function getSupportedCountries(): array
    {
        return [
            'US' => ['code' => '1', 'name' => 'United States', 'flag' => '🇺🇸'],
            'CA' => ['code' => '1', 'name' => 'Canada', 'flag' => '🇨🇦'],
            'GB' => ['code' => '44', 'name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'AU' => ['code' => '61', 'name' => 'Australia', 'flag' => '🇦🇺'],
            'DE' => ['code' => '49', 'name' => 'Germany', 'flag' => '🇩🇪'],
            'FR' => ['code' => '33', 'name' => 'France', 'flag' => '🇫🇷'],
            'ES' => ['code' => '34', 'name' => 'Spain', 'flag' => '🇪🇸'],
            'IT' => ['code' => '39', 'name' => 'Italy', 'flag' => '🇮🇹'],
            'JP' => ['code' => '81', 'name' => 'Japan', 'flag' => '🇯🇵'],
            'KR' => ['code' => '82', 'name' => 'South Korea', 'flag' => '🇰🇷'],
            'AE' => ['code' => '971', 'name' => 'United Arab Emirates', 'flag' => '🇦🇪'],
            'SA' => ['code' => '966', 'name' => 'Saudi Arabia', 'flag' => '🇸🇦'],
            'QA' => ['code' => '974', 'name' => 'Qatar', 'flag' => '🇶🇦'],
            'JO' => ['code' => '962', 'name' => 'Jordan', 'flag' => '🇯🇴'],
            'KW' => ['code' => '965', 'name' => 'Kuwait', 'flag' => '🇰🇼'],
            'BH' => ['code' => '973', 'name' => 'Bahrain', 'flag' => '🇧🇭'],
            'OM' => ['code' => '968', 'name' => 'Oman', 'flag' => '🇴🇲'],
            'LB' => ['code' => '961', 'name' => 'Lebanon', 'flag' => '🇱🇧'],
            'IN' => ['code' => '91', 'name' => 'India', 'flag' => '🇮🇳'],
            'EG' => ['code' => '20', 'name' => 'Egypt', 'flag' => '🇪🇬'],
        ];
    }
    
    public function render()
    {
        return view('livewire.customer.purchase-plan', [
            'navigationPages' => $this->navigationPages ?? collect(),
        ])->layout('components.layouts.templates.fitness', [
            'navigationPages' => $this->navigationPages ?? collect(),
        ]);
    }
}

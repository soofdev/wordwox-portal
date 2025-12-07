<?php

namespace App\Http\Controllers;

use App\Models\OrgUserPlan;
use App\Models\OrgInvoicePayment;
use App\Services\MyFatoorahPaymentApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Payment Callback Controller
 * 
 * Handles payment callbacks from MyFatoorah/wodworx-pay service.
 * Implements retention strategy: Updates existing PENDING records to ACTIVE/PAID,
 * or creates new records if they don't exist.
 */
class PaymentCallbackController extends Controller
{
    /**
     * Handle payment callback from MyFatoorah
     * 
     * This endpoint is called by MyFatoorah directly after payment completion.
     * MyFatoorah sends a GET request with query parameters:
     * - paymentId (or PaymentId): MyFatoorah Payment ID
     * - Id: MyFatoorah Invoice ID
     * 
     * The callback is executed:
     * 1. After user completes payment (success or failure)
     * 2. MyFatoorah processes the payment
     * 3. MyFatoorah sends GET request to CallBackUrl
     * 4. This handler verifies payment and updates records
     * 
     * Note: MyFatoorah may call this multiple times, so handler must be idempotent.
     */
    public function handleCallback(Request $request)
    {
        // MyFatoorah sends parameters as query string (GET request)
        // Support both camelCase and PascalCase parameter names
        $paymentId = $request->query('paymentId') 
            ?? $request->query('PaymentId') 
            ?? $request->input('paymentId') 
            ?? $request->input('PaymentId');
        
        $invoiceId = $request->query('Id') 
            ?? $request->query('InvoiceId') 
            ?? $request->input('Id') 
            ?? $request->input('invoiceId');
        
        $orgId = $request->query('org_id') ?? $request->input('org_id');
        
        Log::info('PaymentCallback: Received callback from MyFatoorah', [
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
            'org_id' => $orgId,
            'plan_uuid' => $request->query('plan'),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'all_query_params' => $request->query(),
            'all_input_params' => $request->all(),
        ]);
        
        // Check if payment identifiers are present
        if (!$paymentId && !$invoiceId) {
            Log::warning('PaymentCallback: Missing payment identifiers (paymentId/Id)', [
                'url' => $request->fullUrl(),
                'query_params' => $request->query(),
                'note' => 'MyFatoorah should send paymentId and Id parameters. This might be a direct access or test.',
            ]);
            
            // If we have plan UUID, we can try to find session by plan
            $planUuid = $request->query('plan');
            if ($planUuid) {
                Log::info('PaymentCallback: Attempting to find session by plan UUID', [
                    'plan_uuid' => $planUuid,
                ]);
                
                // Try to find session by plan UUID
                foreach (Session::all() as $key => $data) {
                    if (str_starts_with($key, 'payment_pending_') && isset($data['plan_uuid']) && $data['plan_uuid'] == $planUuid) {
                        $sessionKey = $key;
                        $sessionData = $data;
                        
                        Log::info('PaymentCallback: Found session by plan UUID', [
                            'session_key' => $sessionKey,
                            'payment_id' => $sessionData['payment_id'] ?? null,
                            'invoice_id' => $sessionData['invoice_id'] ?? null,
                        ]);
                        
                        // If we have payment IDs in session, use them
                        if (isset($sessionData['payment_id']) || isset($sessionData['invoice_id'])) {
                            $paymentId = $paymentId ?? $sessionData['payment_id'] ?? null;
                            $invoiceId = $invoiceId ?? $sessionData['invoice_id'] ?? null;
                            break;
                        }
                    }
                }
            }
            
            // If still no payment identifiers, return error
            if (!$paymentId && !$invoiceId) {
                Log::error('PaymentCallback: Cannot proceed without payment identifiers', [
                    'url' => $request->fullUrl(),
                ]);
                return redirect()->route('payment.success')->with('error', 'Payment information not found. Please contact support with your payment reference.');
            }
        }
        
        try {
            // Step 1: Verify payment status with MyFatoorah API
            Log::info('PaymentCallback: Starting payment verification', [
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'verification_type' => $paymentId ? 'PaymentId' : 'InvoiceId',
                'verification_value' => $paymentId ?? $invoiceId,
            ]);
            
            $paymentService = app(MyFatoorahPaymentApiService::class);
            $verificationResult = $paymentService->verifyPayment(
                $paymentId ?? $invoiceId,
                $paymentId ? 'PaymentId' : 'InvoiceId',
                $orgId // Pass org_id to the verify payment API
            );
            
            Log::info('PaymentCallback: Payment verification response', [
                'success' => $verificationResult['success'] ?? false,
                'status_code' => $verificationResult['status_code'] ?? null,
                'response_data_keys' => isset($verificationResult['data']) ? array_keys($verificationResult['data']) : [],
                'full_response' => $verificationResult,
            ]);
            
            if (!$verificationResult['success']) {
                Log::error('PaymentCallback: Payment verification failed', [
                    'result' => $verificationResult,
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoiceId,
                ]);
                return redirect()->route('payment.success')->with('error', 'Failed to verify payment status. Please contact support.');
            }
            
            // Extract payment status from various possible response formats
            $paymentStatus = $verificationResult['data']['payment_status'] 
                ?? $verificationResult['data']['status'] 
                ?? $verificationResult['data']['InvoiceStatus']
                ?? $verificationResult['data']['InvoiceStatus'] 
                ?? $verificationResult['data']['Data']['InvoiceStatus'] ?? null;
            
            Log::info('PaymentCallback: Payment status extracted', [
                'payment_status' => $paymentStatus,
                'is_paid' => ($paymentStatus === 'Paid' || $paymentStatus === 'paid'),
                'all_status_fields' => [
                    'payment_status' => $verificationResult['data']['payment_status'] ?? null,
                    'status' => $verificationResult['data']['status'] ?? null,
                    'InvoiceStatus' => $verificationResult['data']['InvoiceStatus'] ?? null,
                    'Data.InvoiceStatus' => $verificationResult['data']['Data']['InvoiceStatus'] ?? null,
                ],
            ]);
            
            if ($paymentStatus !== 'Paid' && $paymentStatus !== 'paid') {
                Log::warning('PaymentCallback: Payment not paid', [
                    'status' => $paymentStatus,
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoiceId,
                    'full_verification_data' => $verificationResult['data'],
                ]);
                return redirect()->route('payment.success')->with('error', 'Payment is not completed. Status: ' . ($paymentStatus ?? 'Unknown'));
            }
            
            Log::info('PaymentCallback: Payment verified as PAID', [
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'status' => $paymentStatus,
            ]);
            
            // Extract actual invoice_id from verification response (may differ from callback params)
            // The verification response has the actual invoice_id that matches what was stored during payment creation
            $verificationInvoiceId = $verificationResult['data']['invoice_id'] ?? null;
            if ($verificationInvoiceId && $verificationInvoiceId != $invoiceId) {
                Log::info('PaymentCallback: Found different invoice_id in verification response', [
                    'callback_invoice_id' => $invoiceId,
                    'verification_invoice_id' => $verificationInvoiceId,
                    'payment_id' => $paymentId,
                ]);
                // Use the actual invoice_id from verification response for session lookup
                // But keep original invoiceId for database queries
            }
            
            // Step 2: Check if records already exist (idempotent check)
            $existingPayment = DB::table('orgInvoicePayment')
                ->where('pp', 'myfatoorah')
                ->where(function($query) use ($paymentId, $invoiceId) {
                    if ($paymentId) {
                        $query->where('pp_id', $paymentId);
                    }
                    if ($invoiceId) {
                        $query->orWhere('pp_number', $invoiceId);
                    }
                })
                ->where('isDeleted', 0)
                ->first();
            
            // If payment already processed, just redirect to success
            if ($existingPayment && $existingPayment->status == OrgInvoicePayment::STATUS_PAID) {
                Log::info('PaymentCallback: Payment already processed', [
                    'payment_id' => $existingPayment->id,
                    'status' => 'already_paid',
                ]);
                
                $invoice = DB::table('orgInvoice')
                    ->where('id', $existingPayment->orgInvoice_id)
                    ->where('isDeleted', 0)
                    ->first();
                
                if ($invoice) {
                    $membership = OrgUserPlan::find($invoice->orgUserPlan_id);
                    if ($membership) {
                        $ref = 'plan_' . $membership->uuid . '_' . $membership->orgUser_id;
                        return redirect()->route('payment.success', ['ref' => $ref]);
                    }
                }
                
                return redirect()->route('payment.success');
            }
            
            // Step 3: Find payment data by payment/invoice ID
            // Try multiple sources: database cache, session, and fallback to verification data
            $sessionKey = null;
            $sessionData = null;
            
            // First, try to find in database cache using verification invoice_id (most likely to match)
            // This is the invoice_id from wodworx-pay that was stored during payment creation
            $cacheKey = null;
            if ($verificationInvoiceId) {
                $cacheKey = 'payment_pending_' . $verificationInvoiceId;
                $sessionData = \Illuminate\Support\Facades\Cache::get($cacheKey);
                if ($sessionData) {
                    $sessionKey = $cacheKey; // Set sessionKey for cleanup
                    Log::info('PaymentCallback: Found session data using verification invoice_id', [
                        'verification_invoice_id' => $verificationInvoiceId,
                        'cache_key' => $cacheKey,
                    ]);
                }
            }
            
            // Try callback invoice_id if verification invoice_id didn't work
            if (!$sessionData && $invoiceId) {
                $cacheKey = 'payment_pending_' . $invoiceId;
                $sessionData = \Illuminate\Support\Facades\Cache::get($cacheKey);
                if ($sessionData) {
                    $sessionKey = $cacheKey; // Set sessionKey for cleanup
                }
            }
            
            // Try paymentId as last resort
            if (!$sessionData && $paymentId) {
                $cacheKey = 'payment_pending_' . $paymentId;
                $sessionData = \Illuminate\Support\Facades\Cache::get($cacheKey);
                if ($sessionData) {
                    $sessionKey = $cacheKey; // Set sessionKey for cleanup
                }
            }
            
            // If not in cache, try session (for same-browser scenarios)
            if (!$sessionData) {
                // Try verification invoice_id first (most likely to match)
                if ($verificationInvoiceId) {
                    $sessionKey = 'payment_pending_' . $verificationInvoiceId;
                    $sessionData = Session::get($sessionKey);
                }
                
                // Try callback invoice_id
                if (!$sessionData && $invoiceId) {
                    $sessionKey = 'payment_pending_' . $invoiceId;
                    $sessionData = Session::get($sessionKey);
                }
                
                // Try paymentId
                if (!$sessionData && $paymentId) {
                    $sessionKey = 'payment_pending_' . $paymentId;
                    $sessionData = Session::get($sessionKey);
                }
                
                // If still not found, search all payment sessions
                if (!$sessionData) {
                    foreach (Session::all() as $key => $data) {
                        if (str_starts_with($key, 'payment_pending_')) {
                            // Match by verification invoice_id first
                            if ($verificationInvoiceId && isset($data['invoice_id']) && $data['invoice_id'] == $verificationInvoiceId) {
                                $sessionKey = $key;
                                $sessionData = $data;
                                break;
                            }
                            // Match by callback invoice_id
                            if (isset($data['invoice_id']) && $data['invoice_id'] == $invoiceId) {
                                $sessionKey = $key;
                                $sessionData = $data;
                                break;
                            }
                            // Match by payment_id
                            if (isset($data['payment_id']) && $data['payment_id'] == $paymentId) {
                                $sessionKey = $key;
                                $sessionData = $data;
                                break;
                            }
                        }
                    }
                }
            }
            
            // If still not found, try to reconstruct from verification data and plan UUID
            if (!$sessionData) {
                $planUuid = $request->query('plan');
                if ($planUuid && $orgId) {
                    Log::info('PaymentCallback: Attempting to reconstruct payment data', [
                        'plan_uuid' => $planUuid,
                        'org_id' => $orgId,
                        'payment_id' => $paymentId,
                        'invoice_id' => $invoiceId,
                    ]);
                    
                    // Try to find plan and get user from verification response
                    $plan = \App\Models\OrgPlan::where('uuid', $planUuid)
                        ->where('org_id', $orgId)
                        ->first();
                    
                    if ($plan && isset($verificationResult['data'])) {
                        // Try to extract customer info from verification response
                        $customerName = $verificationResult['data']['CustomerName'] ?? 
                                       $verificationResult['data']['customer_name'] ?? null;
                        $customerEmail = $verificationResult['data']['CustomerEmail'] ?? 
                                        $verificationResult['data']['customer_email'] ?? null;
                        
                        // Try to find user by email or name
                        $orgUser = null;
                        if ($customerEmail) {
                            $orgUser = \App\Models\OrgUser::where('email', $customerEmail)
                                ->where('org_id', $orgId)
                                ->first();
                        }
                        
                        if ($orgUser) {
                            Log::info('PaymentCallback: Reconstructed payment data from verification', [
                                'org_user_id' => $orgUser->id,
                                'plan_id' => $plan->id,
                            ]);
                            
                            // Reconstruct session data
                            $sessionData = [
                                'org_id' => $orgId,
                                'org_user_id' => $orgUser->id,
                                'org_plan_id' => $plan->id,
                                'plan_uuid' => $plan->uuid,
                                'plan_name' => $plan->name,
                                'plan_price' => $verificationResult['data']['InvoiceValue'] ?? $plan->price ?? 0,
                                'payment_id' => $paymentId,
                                'invoice_id' => $invoiceId,
                                'membership_data' => [
                                    'org_id' => $orgId,
                                    'orgUser_id' => $orgUser->id,
                                    'orgPlan_id' => $plan->id,
                                    'invoiceStatus' => OrgUserPlan::INVOICE_STATUS_PAID,
                                    'invoiceMethod' => 'online',
                                    'status' => OrgUserPlan::STATUS_ACTIVE,
                                    'created_by' => $orgUser->id,
                                    'sold_by' => $orgUser->id,
                                    'note' => 'Purchased online via customer portal (Reconstructed from callback)',
                                    'startDateLoc' => now()->format('Y-m-d'),
                                ],
                                'payment_data' => [],
                            ];
                        }
                    }
                }
            }
            
            if (!$sessionData) {
                Log::error('PaymentCallback: Payment data not found in any source', [
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoiceId,
                    'org_id' => $orgId,
                    'plan_uuid' => $request->query('plan'),
                    'session_keys_searched' => [
                        'cache_invoice' => $invoiceId ? 'payment_pending_' . $invoiceId : null,
                        'cache_payment' => $paymentId ? 'payment_pending_' . $paymentId : null,
                    ],
                    'verification_data_keys' => isset($verificationResult['data']) ? array_keys($verificationResult['data']) : [],
                ]);
                return redirect()->route('payment.success')->with('error', 'Payment information not found. If you just completed a payment, please wait a moment and refresh the page. If the issue persists, please contact support with your payment reference: ' . ($paymentId ?? $invoiceId ?? 'N/A'));
            }
            
            // Check if session expired
            if (isset($sessionData['expires_at'])) {
                $expiresAt = is_string($sessionData['expires_at']) 
                    ? \Carbon\Carbon::parse($sessionData['expires_at']) 
                    : $sessionData['expires_at'];
                    
                if (now()->isAfter($expiresAt)) {
                    Log::warning('PaymentCallback: Payment data expired', [
                        'session_key' => $sessionKey ?? $cacheKey,
                        'expires_at' => $sessionData['expires_at'],
                    ]);
                    if ($sessionKey) {
                        Session::forget($sessionKey);
                        \Illuminate\Support\Facades\Cache::forget($sessionKey);
                    }
                    if ($cacheKey) {
                        \Illuminate\Support\Facades\Cache::forget($cacheKey);
                    }
                    return redirect()->route('payment.success')->with('error', 'Payment session expired. Please contact support.');
                }
            }
            
            Log::info('PaymentCallback: Found session data', [
                'session_key' => $sessionKey,
                'has_membership_data' => isset($sessionData['membership_data']),
            ]);
            
            // Step 4: Create records synchronously (without queue job)
            $membershipData = $sessionData['membership_data'] ?? [];
            $paymentData = $sessionData['payment_data'] ?? [];
            $planPrice = $sessionData['plan_price'] ?? 0;
            $finalOrgId = $orgId ?? $sessionData['org_id'] ?? 0;
            $orgUserId = $sessionData['org_user_id'] ?? 0;
            $orgPlanId = $sessionData['org_plan_id'] ?? 0;
            $finalPaymentId = $paymentId ?? '';
            $finalInvoiceId = $invoiceId ?? '';
            
            try {
                // Check if user already has an active plan before creating new one
                $existingMembership = OrgUserPlan::where('orgUser_id', $orgUserId)
                    ->whereIn('status', [
                        OrgUserPlan::STATUS_ACTIVE,
                        OrgUserPlan::STATUS_UPCOMING,
                        OrgUserPlan::STATUS_PENDING,
                    ])
                    ->where('isCanceled', false)
                    ->where('isDeleted', false)
                    ->first();

                if ($existingMembership) {
                    Log::warning('PaymentCallback: User already has active/upcoming/pending membership', [
                        'org_user_id' => $orgUserId,
                        'existing_membership_id' => $existingMembership->id,
                        'existing_plan_id' => $existingMembership->orgPlan_id,
                        'status' => $existingMembership->status,
                        'new_plan_id' => $orgPlanId,
                    ]);
                    
                    // Payment was successful, but skip creating duplicate membership
                    // Clear session and redirect to success
                    if ($sessionKey) {
                        Session::forget($sessionKey);
                        \Illuminate\Support\Facades\Cache::forget($sessionKey);
                    }
                    if ($cacheKey && $cacheKey !== $sessionKey) {
                        \Illuminate\Support\Facades\Cache::forget($cacheKey);
                    }
                    
                    $planUuid = $sessionData['plan_uuid'] ?? 'unknown';
                    $ref = 'plan_' . $planUuid . '_' . $orgUserId;
                    return redirect()->route('payment.success', ['ref' => $ref]);
                }

                // Update membership data to ACTIVE/PAID status
                $membershipData['status'] = OrgUserPlan::STATUS_ACTIVE;
                $membershipData['invoiceStatus'] = OrgUserPlan::INVOICE_STATUS_PAID;
                $membershipData['note'] = 'Purchased online via customer portal';

                DB::beginTransaction();

                try {
                    // Step 1: Create orgUserPlan (membership)
                    $planService = app(\App\Services\OrgUserPlanService::class);
                    $orgUserPlan = $planService->create($membershipData);

                    Log::info('PaymentCallback: Created orgUserPlan', [
                        'membership_id' => $orgUserPlan->id,
                        'uuid' => $orgUserPlan->uuid,
                    ]);

                    // Step 2: Create orgInvoice
                    $invoiceUuid = \Illuminate\Support\Str::uuid()->toString();
                    $dbInvoiceId = DB::table('orgInvoice')->insertGetId([
                        'uuid' => $invoiceUuid,
                        'org_id' => $finalOrgId,
                        'orgUserPlan_id' => $orgUserPlan->id,
                        'orgUser_id' => $orgUserId,
                        'total' => $planPrice,
                        'totalPaid' => $planPrice, // Full payment received
                        'currency' => $paymentData['currency_iso'] ?? 'KWD',
                        'status' => \App\Enums\InvoiceStatus::PAID->value,
                        'pp' => 'myfatoorah',
                        'isDeleted' => 0,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);

                    Log::info('PaymentCallback: Created orgInvoice', [
                        'invoice_id' => $dbInvoiceId,
                        'uuid' => $invoiceUuid,
                    ]);

                    // Step 3: Create orgInvoicePayment
                    // Note: gateway column is integer, so we set it to NULL or omit it
                    $paymentUuid = \Illuminate\Support\Str::uuid()->toString();
                    $dbPaymentId = DB::table('orgInvoicePayment')->insertGetId([
                        'uuid' => $paymentUuid,
                        'org_id' => $finalOrgId,
                        'orgInvoice_id' => $dbInvoiceId,
                        'amount' => $planPrice,
                        'currency' => $paymentData['currency_iso'] ?? 'KWD',
                        'method' => \App\Models\OrgInvoicePayment::METHOD_ONLINE,
                        'status' => \App\Models\OrgInvoicePayment::STATUS_PAID,
                        'gateway' => null, // Gateway is integer column, use NULL or gateway ID if available
                        'pp' => 'myfatoorah',
                        'pp_id' => $finalPaymentId,
                        'pp_number' => $finalInvoiceId,
                        'paid_at' => time(),
                        'created_by' => $orgUserId,
                        'isCanceled' => 0,
                        'isDeleted' => 0,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ]);

                    Log::info('PaymentCallback: Created orgInvoicePayment', [
                        'payment_id' => $dbPaymentId,
                        'uuid' => $paymentUuid,
                        'pp_id' => $finalPaymentId,
                        'pp_number' => $finalInvoiceId,
                    ]);

                    DB::commit();

                    Log::info('PaymentCallback: Successfully created all records', [
                        'membership_id' => $orgUserPlan->id,
                        'invoice_id' => $dbInvoiceId,
                        'payment_id' => $dbPaymentId,
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('PaymentCallback: Error creating records', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }

            } catch (\Exception $e) {
                Log::error('PaymentCallback: Failed to create payment records', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Clear session and redirect with error
                if ($sessionKey) {
                    Session::forget($sessionKey);
                    \Illuminate\Support\Facades\Cache::forget($sessionKey);
                }
                if ($cacheKey && $cacheKey !== $sessionKey) {
                    \Illuminate\Support\Facades\Cache::forget($cacheKey);
                }
                return redirect()->route('payment.success')->with('error', 'Payment was successful but failed to create records. Please contact support with payment reference: ' . ($finalPaymentId ?? $finalInvoiceId ?? 'N/A'));
            }
            
            // Clear session and cache after successful creation
            if ($sessionKey) {
                Session::forget($sessionKey);
                \Illuminate\Support\Facades\Cache::forget($sessionKey);
            }
            if ($cacheKey && $cacheKey !== $sessionKey) {
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }
            
            // Redirect to success page
            $planUuid = $sessionData['plan_uuid'] ?? 'unknown';
            $ref = 'plan_' . $planUuid . '_' . $orgUserId;
            return redirect()->route('payment.success', ['ref' => $ref]);
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            Log::error('PaymentCallback: Unexpected error', [
                'error' => $errorMessage,
                'error_class' => get_class($e),
                'payment_id' => $paymentId ?? null,
                'invoice_id' => $invoiceId ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Provide more specific error messages based on error type
            $userFriendlyMessage = 'An unexpected error occurred during payment processing.';
            
            if (str_contains($errorMessage, 'Validation failed')) {
                $userFriendlyMessage = 'Payment verification failed. The payment service returned a validation error. Please contact support with your payment reference: ' . ($paymentId ?? $invoiceId ?? 'N/A');
            } elseif (str_contains($errorMessage, 'Failed to verify payment')) {
                $userFriendlyMessage = 'Unable to verify payment status. Please wait a moment and refresh the page. If the issue persists, contact support with your payment reference: ' . ($paymentId ?? $invoiceId ?? 'N/A');
            } elseif (str_contains($errorMessage, 'not found') || str_contains($errorMessage, '404')) {
                $userFriendlyMessage = 'Payment service endpoint not found. Please contact support.';
            } elseif (str_contains($errorMessage, 'Authentication') || str_contains($errorMessage, '401') || str_contains($errorMessage, '403')) {
                $userFriendlyMessage = 'Payment service authentication error. Please contact support.';
            } elseif (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'Connection')) {
                $userFriendlyMessage = 'Payment service connection timeout. Please wait a moment and refresh the page.';
            }
            
            return redirect()->route('payment.success')->with('error', $userFriendlyMessage);
        }
    }
}


<?php

namespace App\Livewire\Customer;

use App\Models\CmsPage;
use App\Models\OrgUser;
use App\Models\User;
use App\Services\SmsService;
use App\Services\Yii2QueueDispatcher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Attributes\Validate;

/**
 * Customer Login Component
 * 
 * Allows customers to login using OTP sent via phone or email
 * Separate from CMS admin login
 */
class CustomerLogin extends Component
{
    #[Validate('required|string')]
    public string $identifier = ''; // Email address
    
    public string $loginMethod = 'email'; // 'email' or 'phone'
    
    public string $phoneCountry = 'US'; // Country code for phone login
    #[Validate('nullable|string')]
    public string $phoneNumber = ''; // Phone number for phone login
    
    public bool $otpSent = false;
    public string $message = '';
    
    #[Validate('required|string|size:4')]
    public string $otp = '';
    
    public int $resendCooldown = 0; // Remaining seconds for resend cooldown
    
    public $navigationPages;
    
    public function mount()
    {
        $this->loadNavigationPages();
    }
    
    /**
     * Load navigation pages for the navbar
     */
    protected function loadNavigationPages()
    {
        $orgId = env('CMS_DEFAULT_ORG_ID', 8);
        $this->navigationPages = CmsPage::where('org_id', $orgId)
            ->where('status', 'published')
            ->where('show_in_navigation', true)
            ->where('is_homepage', false)
            ->where('slug', '!=', 'home')
            ->orderBy('sort_order', 'asc')
            ->get();
    }
    
    /**
     * Send OTP to customer's email or phone
     */
    public function sendOtp()
    {
        // Validate based on login method
        if ($this->loginMethod === 'email') {
            $this->validate([
                'identifier' => 'required|email',
            ]);
        } else {
            $this->validate([
                'phoneCountry' => 'required|string|min:1|max:4',
                'phoneNumber' => 'required|string',
            ]);
        }
        
        // Find OrgUser by email or phone
        $orgUser = null;
        
        if ($this->loginMethod === 'email') {
            $orgUser = OrgUser::where('email', $this->identifier)
                ->where('isCustomer', true)
                ->where(function($query) {
                    $query->where('isDeleted', false)
                          ->orWhereNull('isDeleted');
                })
                ->first();
        } else {
            // Phone login - find by phoneCountry and phoneNumber
            // Convert ISO country code to dialing code (database stores dialing code, not ISO)
            $dialingCode = $this->convertIsoToDialingCode($this->phoneCountry);
            
            if (!$dialingCode) {
                $this->message = 'Invalid country code selected.';
                Log::warning('Invalid country code in phone login', [
                    'phone_country_iso' => $this->phoneCountry,
                    'phone_number' => $this->phoneNumber
                ]);
                return;
            }
            
            // Clean phone number (remove spaces, dashes, parentheses, plus signs)
            $cleanPhone = preg_replace('/[\s\+\-\(\)]/', '', $this->phoneNumber);
            $cleanPhone = ltrim($cleanPhone, '0'); // Remove leading zeros
            
            Log::info('Phone login lookup', [
                'iso_country' => $this->phoneCountry,
                'dialing_code' => $dialingCode,
                'original_phone' => $this->phoneNumber,
                'cleaned_phone' => $cleanPhone
            ]);
            
            // Primary lookup: exact match with phoneCountry (dialing code) and phoneNumber
            $orgUser = OrgUser::where('phoneCountry', $dialingCode)
                ->where('phoneNumber', $cleanPhone)
                ->where('isCustomer', true)
                ->where(function($query) {
                    $query->where('isDeleted', false)
                          ->orWhereNull('isDeleted');
                })
                ->whereNull('deleted_at') // Also check soft deletes
                ->first();
            
            // If not found, try alternative formats
            if (!$orgUser) {
                // Try with phone number that might have country code prefix
                $fullPhone = $dialingCode . $cleanPhone;
                $orgUser = OrgUser::where('isCustomer', true)
                    ->where(function($query) {
                        $query->where('isDeleted', false)
                              ->orWhereNull('isDeleted');
                    })
                    ->whereNull('deleted_at')
                    ->where(function($query) use ($dialingCode, $cleanPhone, $fullPhone) {
                        // Try exact match first
                        $query->where(function($q) use ($dialingCode, $cleanPhone) {
                            $q->where('phoneCountry', $dialingCode)
                              ->where('phoneNumber', $cleanPhone);
                        })
                        // Try concatenated format
                        ->orWhereRaw("CONCAT(phoneCountry, phoneNumber) = ?", [$fullPhone])
                        // Try with plus sign
                        ->orWhereRaw("CONCAT('+', phoneCountry, phoneNumber) = ?", ['+' . $fullPhone])
                        // Fallback: try phone number without country code (in case country code is missing in DB)
                        ->orWhere(function($q) use ($cleanPhone) {
                            $q->where('phoneNumber', $cleanPhone)
                              ->whereNotNull('phoneNumber')
                              ->where('phoneNumber', '!=', '');
                        });
                    })
                    ->first();
            }
            
            // Log result
            if ($orgUser) {
                Log::info('OrgUser found by phone', [
                    'org_user_id' => $orgUser->id,
                    'stored_phone_country' => $orgUser->phoneCountry,
                    'stored_phone_number' => $orgUser->phoneNumber,
                    'searched_dialing_code' => $dialingCode,
                    'searched_phone' => $cleanPhone
                ]);
            } else {
                Log::warning('OrgUser not found by phone', [
                    'searched_dialing_code' => $dialingCode,
                    'searched_phone' => $cleanPhone,
                    'iso_country' => $this->phoneCountry
                ]);
            }
        }
        
        if (!$orgUser) {
            $this->message = 'No account found with this ' . ($this->loginMethod === 'email' ? 'email' : 'phone number') . '.';
            return;
        }
        
        // Find or create User account
        // First, try to find by orgUser_id (including soft-deleted)
        $user = User::withTrashed()->where('orgUser_id', $orgUser->id)->first();
        
        if ($user && $user->trashed()) {
            // If user is soft-deleted, restore it
            $user->restore();
            Log::info('Restored soft-deleted User', [
                'user_id' => $user->id,
                'org_user_id' => $orgUser->id
            ]);
        }
        
        if (!$user) {
            // If not found by orgUser_id, check if User exists with same phone number
            // This handles cases where phoneNumber has a unique constraint
            // IMPORTANT: Check by phoneNumber regardless of how user originally signed up (phone or email)
            // This prevents duplicate User records when user logs in by phone but originally signed up by email
            if ($orgUser->phoneNumber) {
                // Strategy 1: Try exact match with phoneCountry (if available)
                if ($orgUser->phoneCountry) {
                    $user = User::withTrashed()
                        ->where('phoneNumber', $orgUser->phoneNumber)
                        ->where('phoneCountry', $orgUser->phoneCountry)
                        ->first();
                }
                
                // Strategy 2: Try exact match without phoneCountry (in case phoneNumber is globally unique)
                if (!$user) {
                    $user = User::withTrashed()
                        ->where('phoneNumber', $orgUser->phoneNumber)
                        ->first();
                }
                
                // Strategy 3: Try normalized phone number (remove leading zeros)
                if (!$user) {
                    $normalizedPhone = ltrim($orgUser->phoneNumber, '0');
                    if ($normalizedPhone !== $orgUser->phoneNumber && $normalizedPhone !== '') {
                        if ($orgUser->phoneCountry) {
                            $user = User::withTrashed()
                                ->where('phoneNumber', $normalizedPhone)
                                ->where('phoneCountry', $orgUser->phoneCountry)
                                ->first();
                        }
                        
                        if (!$user) {
                            $user = User::withTrashed()
                                ->where('phoneNumber', $normalizedPhone)
                                ->first();
                        }
                    }
                }
                
                if ($user) {
                    // If user is soft-deleted, restore it
                    if ($user->trashed()) {
                        $user->restore();
                        Log::info('Restored soft-deleted User by phone number', [
                            'user_id' => $user->id,
                            'phone_number' => $orgUser->phoneNumber,
                            'phone_country' => $orgUser->phoneCountry,
                            'found_phone_number' => $user->phoneNumber,
                            'found_phone_country' => $user->phoneCountry
                        ]);
                    }
                    
                    // User exists with this phone number - update orgUser_id and other fields if different
                    // This links the existing User (whether they signed up by phone or email) to the current OrgUser
                    $updated = false;
                    if ($user->orgUser_id != $orgUser->id) {
                        $user->orgUser_id = $orgUser->id;
                        $updated = true;
                        Log::info('Linking existing User to OrgUser (phone number match)', [
                            'user_id' => $user->id,
                            'old_org_user_id' => $user->orgUser_id,
                            'new_org_user_id' => $orgUser->id,
                            'phone_number' => $orgUser->phoneNumber
                        ]);
                    }
                    if ($user->fullName != $orgUser->fullName) {
                        $user->fullName = $orgUser->fullName;
                        $updated = true;
                    }
                    if ($user->email != $orgUser->email) {
                        $user->email = $orgUser->email;
                        $updated = true;
                    }
                    // Update phone fields to match OrgUser (in case they differ)
                    if ($user->phoneNumber != $orgUser->phoneNumber) {
                        $user->phoneNumber = $orgUser->phoneNumber;
                        $updated = true;
                    }
                    if ($user->phoneCountry != $orgUser->phoneCountry) {
                        $user->phoneCountry = $orgUser->phoneCountry;
                        $updated = true;
                    }
                    
                    if ($updated) {
                        Log::info('Updated existing User with OrgUser data', [
                            'user_id' => $user->id,
                            'org_user_id' => $orgUser->id,
                            'phone_number' => $orgUser->phoneNumber
                        ]);
                        $user->save();
                    }
                }
            }
            
            // If still no user found, create a new one
            // Handle race conditions by catching unique constraint violations
            if (!$user) {
                // Create user account if it doesn't exist
                // For OTP-based login, password_hash is required but not used for authentication
                $user = new User();
                $user->orgUser_id = $orgUser->id;
                $user->fullName = $orgUser->fullName;
                $user->email = $orgUser->email;
                $user->phoneNumber = $orgUser->phoneNumber;
                $user->phoneCountry = $orgUser->phoneCountry;
                $user->uuid = \Illuminate\Support\Str::uuid();
                $user->auth_key = \Illuminate\Support\Str::random(32); // Required auth_key field
                $user->password_hash = Hash::make(\Illuminate\Support\Str::random(32)); // Required field, not used for OTP login
                $user->status = 10; // Set status to active/verified
                
                try {
                    $user->save();
                    
                    // Dispatch Yii2 queue job for User creation (matching Yii pattern)
                    try {
                        $dispatcher = new Yii2QueueDispatcher();
                        $dispatcher->dispatch('common\jobs\user\UserCreateCompleteJob', ['id' => $user->id]);
                        
                        Log::info('Customer User account created and Yii2 job dispatched', [
                            'user_id' => $user->id,
                            'org_user_id' => $orgUser->id
                        ]);
                    } catch (\Exception $e) {
                        // Log warning but don't fail login for background job issues
                        Log::warning('Failed to dispatch UserCreateCompleteJob', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle unique constraint violation (race condition or duplicate phone)
                    if ($e->getCode() == 23000) {
                        $errorMessage = $e->getMessage();
                        Log::warning('Unique constraint violation when creating User', [
                            'org_user_id' => $orgUser->id,
                            'phone_number' => $orgUser->phoneNumber,
                            'phone_country' => $orgUser->phoneCountry,
                            'error' => $errorMessage
                        ]);
                        
                        // Try to find the existing user by phoneNumber (most common case)
                        if (str_contains($errorMessage, 'phoneNumber')) {
                            // Try exact match
                            $user = User::withTrashed()
                                ->where('phoneNumber', $orgUser->phoneNumber)
                                ->first();
                            
                            // Try with phoneCountry
                            if (!$user && $orgUser->phoneCountry) {
                                $user = User::withTrashed()
                                    ->where('phoneNumber', $orgUser->phoneNumber)
                                    ->where('phoneCountry', $orgUser->phoneCountry)
                                    ->first();
                            }
                            
                            // Try normalized version
                            if (!$user) {
                                $normalizedPhone = ltrim($orgUser->phoneNumber, '0');
                                if ($normalizedPhone !== $orgUser->phoneNumber && $normalizedPhone !== '') {
                                    $user = User::withTrashed()
                                        ->where('phoneNumber', $normalizedPhone)
                                        ->first();
                                }
                            }
                        }
                        
                        // If not found by phone, try by orgUser_id (fallback)
                        if (!$user) {
                            $user = User::withTrashed()->where('orgUser_id', $orgUser->id)->first();
                        }
                        
                        if ($user) {
                            // If user is soft-deleted, restore it
                            if ($user->trashed()) {
                                $user->restore();
                                Log::info('Restored soft-deleted User after constraint violation', [
                                    'user_id' => $user->id
                                ]);
                            }
                            
                            // Update orgUser_id and other fields if different
                            $updated = false;
                            if ($user->orgUser_id != $orgUser->id) {
                                $user->orgUser_id = $orgUser->id;
                                $updated = true;
                            }
                            if ($user->fullName != $orgUser->fullName) {
                                $user->fullName = $orgUser->fullName;
                                $updated = true;
                            }
                            if ($user->email != $orgUser->email) {
                                $user->email = $orgUser->email;
                                $updated = true;
                            }
                            
                            if ($updated) {
                                $user->save();
                                Log::info('Updated existing User after constraint violation', [
                                    'user_id' => $user->id,
                                    'org_user_id' => $orgUser->id
                                ]);
                            }
                        } else {
                            // If we still can't find it, log error but don't throw
                            Log::error('Unique constraint violation but user not found by any method', [
                                'org_user_id' => $orgUser->id,
                                'phone_number' => $orgUser->phoneNumber,
                                'phone_country' => $orgUser->phoneCountry,
                                'error' => $errorMessage
                            ]);
                            
                            // Last resort: try to find by orgUser_id one more time
                            $user = User::withTrashed()->where('orgUser_id', $orgUser->id)->first();
                            if ($user && $user->trashed()) {
                                $user->restore();
                            }
                            
                            // If still no user, we can't proceed - show user-friendly message
                            if (!$user) {
                                $this->message = 'Unable to create login account. Please contact support.';
                                Log::error('Cannot proceed with login - user not found after constraint violation', [
                                    'org_user_id' => $orgUser->id
                                ]);
                                return;
                            }
                        }
                    } else {
                        // Other database errors - rethrow
                        throw $e;
                    }
                } catch (\Exception $e) {
                    // Other exceptions - rethrow
                    throw $e;
                }
            }
        }
        
        // Generate OTP
        $user->generateOTP();
        
        // Send OTP via email or SMS
        if ($this->loginMethod === 'email' && $orgUser->email) {
            try {
                Mail::raw("Your OTP code is: {$user->otp}\n\nThis code will expire in 15 minutes.", function ($message) use ($orgUser) {
                    $message->to($orgUser->email)
                            ->subject('Your Login OTP Code');
                });
                
                $this->otpSent = true;
                $this->message = ''; // Clear message to hide success alert
                $this->otp = ''; // Clear any previous OTP input
                $this->resendCooldown = 60; // Set 60 second cooldown
                
                // Store login method and OTP sent timestamp in session
                session([
                    'customer_otp_method' => 'email',
                    'customer_otp_sent_at' => now()->timestamp
                ]);
                
                // Dispatch browser event to focus OTP input and start countdown
                $this->dispatch('otp-sent', cooldown: 60);
                
                Log::info('Customer OTP sent via email', [
                    'org_user_id' => $orgUser->id,
                    'email' => $orgUser->email
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send OTP email', [
                    'org_user_id' => $orgUser->id,
                    'error' => $e->getMessage()
                ]);
                $this->message = 'Failed to send OTP. Please try again.';
            }
        } elseif ($this->loginMethod === 'phone' && $orgUser->phoneNumber) {
            try {
                $smsService = new SmsService();
                $fullPhone = $orgUser->phoneCountry . $orgUser->phoneNumber;
                $message = "Your OTP code is: {$user->otp}. This code will expire in 15 minutes.";
                
                $smsService->send(
                    $fullPhone,
                    $message,
                    $orgUser->org_id,
                    $orgUser->id
                );
                
                $this->otpSent = true;
                $this->message = ''; // Clear message to hide success alert
                $this->otp = ''; // Clear any previous OTP input
                $this->resendCooldown = 60; // Set 60 second cooldown
                
                // Store login method and OTP sent timestamp in session
                session([
                    'customer_otp_method' => 'phone',
                    'customer_otp_sent_at' => now()->timestamp
                ]);
                
                // Dispatch browser event to focus OTP input and start countdown
                $this->dispatch('otp-sent', cooldown: 60);
                
                Log::info('Customer OTP sent via SMS', [
                    'org_user_id' => $orgUser->id,
                    'phone' => $fullPhone
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send OTP SMS', [
                    'org_user_id' => $orgUser->id,
                    'error' => $e->getMessage()
                ]);
                $this->message = 'Failed to send OTP. Please try again.';
            }
        } else {
            $this->message = 'No ' . ($this->loginMethod === 'email' ? 'email' : 'phone number') . ' found for this account.';
        }
        
        // Store user ID in session for OTP verification
        session(['customer_otp_user_id' => $user->id]);
    }
    
    /**
     * Verify OTP and login customer
     */
    public function verifyOtp()
    {
        $this->validate([
            'otp' => 'required|string|size:4',
        ]);
        
        $userId = session('customer_otp_user_id');
        if (!$userId) {
            $this->message = 'Session expired. Please request a new OTP.';
            return;
        }
        
        $user = User::find($userId);
        if (!$user) {
            $this->message = 'Invalid session. Please request a new OTP.';
            return;
        }
        
        // Check if OTP is expired
        if ($user->otp_expire && $user->otp_expire < now()->timestamp) {
            $this->message = 'OTP has expired. Please request a new one.';
            $user->clearOTP();
            session()->forget('customer_otp_user_id');
            $this->otpSent = false;
            return;
        }
        
        // Verify OTP
        if ($user->otp != $this->otp) {
            $this->message = 'Invalid OTP code. Please try again.';
            $this->otp = ''; // Clear the input
            return;
        }
        
        // OTP is valid - login the user using 'customer' guard to separate from CMS admin
        \Illuminate\Support\Facades\Auth::guard('customer')->login($user);
        
        // Clear OTP and session data
        $user->clearOTP();
        session()->forget('customer_otp_user_id');
        session()->forget('customer_otp_method');
        
        Log::info('Customer logged in via OTP', [
            'user_id' => $user->id,
            'org_user_id' => $user->orgUser_id
        ]);
        
        // Dispatch Yii2 queue job for customer login (matching Yii pattern)
        try {
            $dispatcher = new Yii2QueueDispatcher();
            $dispatcher->dispatch('common\jobs\user\UserLoginCompleteJob', [
                'id' => $user->id,
                'orgUser_id' => $user->orgUser_id
            ]);
            
            Log::info('Customer login Yii2 job dispatched', [
                'user_id' => $user->id,
                'org_user_id' => $user->orgUser_id
            ]);
        } catch (\Exception $e) {
            // Log warning but don't fail login for background job issues
            Log::warning('Failed to dispatch UserLoginCompleteJob', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Redirect to home page after successful login
        return $this->redirect(route('home'), navigate: false);
    }
    
    /**
     * Resend OTP code
     * Checks cooldown period before allowing resend
     */
    public function resendOtp()
    {
        // Check if cooldown period has passed
        $otpSentAt = session('customer_otp_sent_at');
        if ($otpSentAt) {
            $elapsed = now()->timestamp - $otpSentAt;
            $remaining = 60 - $elapsed;
            
            if ($remaining > 0) {
                $this->resendCooldown = $remaining;
                $this->message = "Please wait {$remaining} seconds before requesting a new OTP.";
                return;
            }
        }
        
        // Clear previous OTP and message
        $this->otp = '';
        $this->message = '';
        
        // Resend OTP by calling sendOtp again
        $this->sendOtp();
    }
    
    /**
     * Get remaining cooldown time for resend button
     * Used by the view to display countdown
     */
    public function getResendCooldownRemaining(): int
    {
        $otpSentAt = session('customer_otp_sent_at');
        if (!$otpSentAt) {
            return 0;
        }
        
        $elapsed = now()->timestamp - $otpSentAt;
        $remaining = 60 - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Convert ISO country code to dialing code for database lookup
     */
    private function convertIsoToDialingCode(string $isoCode): string
    {
        $countryCodes = [
            'US' => '1',
            'CA' => '1',
            'GB' => '44',
            'AU' => '61',
            'DE' => '49',
            'FR' => '33',
            'ES' => '34',
            'IT' => '39',
            'JP' => '81',
            'KR' => '82',
            'AE' => '971',
            'SA' => '966',
            'QA' => '974',
            'JO' => '962',
        ];
        
        return $countryCodes[$isoCode] ?? $isoCode;
    }
    
    public function render()
    {
        // Update resend cooldown if OTP was sent
        if ($this->otpSent) {
            $this->resendCooldown = $this->getResendCooldownRemaining();
            // Dispatch event to update countdown in real-time
            $this->dispatch('resend-cooldown-updated', cooldown: $this->resendCooldown);
        }
        
        return view('livewire.customer.customer-login')
            ->layout('components.layouts.templates.fitness', [
                'navigationPages' => $this->navigationPages ?? collect(),
            ]);
    }
}


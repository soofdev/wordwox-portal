<?php

namespace App\Livewire\Customer;

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
    public string $identifier = ''; // Can be email or phone
    
    public string $loginMethod = 'email'; // 'email' or 'phone'
    
    public bool $otpSent = false;
    public string $message = '';
    
    /**
     * Send OTP to customer's email or phone
     */
    public function sendOtp()
    {
        $this->validate([
            'identifier' => 'required|string',
        ]);
        
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
            // Phone login - try to find by phone number (with or without country code)
            // Remove spaces, +, and 00 prefix
            $phone = preg_replace('/[\s\+\-\(\)]/', '', $this->identifier);
            $phone = preg_replace('/^00/', '', $phone);
            
            // Try exact match first
            $orgUser = OrgUser::where('phoneNumber', $phone)
                ->where('isCustomer', true)
                ->where(function($query) {
                    $query->where('isDeleted', false)
                          ->orWhereNull('isDeleted');
                })
                ->first();
            
            // If not found, try matching with country code concatenated
            if (!$orgUser) {
                $orgUser = OrgUser::where('isCustomer', true)
                    ->where(function($query) {
                        $query->where('isDeleted', false)
                              ->orWhereNull('isDeleted');
                    })
                    ->where(function($query) use ($phone) {
                        $query->whereRaw("CONCAT(phoneCountry, phoneNumber) = ?", [$phone])
                              ->orWhereRaw("CONCAT('+', phoneCountry, phoneNumber) = ?", [$phone]);
                    })
                    ->first();
            }
        }
        
        if (!$orgUser) {
            $this->message = 'No account found with this ' . ($this->loginMethod === 'email' ? 'email' : 'phone number') . '.';
            return;
        }
        
        // Find or create User account
        $user = User::where('orgUser_id', $orgUser->id)->first();
        
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
            $user->save();
            
            // Dispatch Yii2 queue job for User creation (similar to OrgUser pattern)
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
                $this->message = 'OTP has been sent to your email address.';
                
                // Store login method in session for verify-otp page
                session(['customer_otp_method' => 'email']);
                
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
                $this->message = 'OTP has been sent to your phone number.';
                
                // Store login method in session for verify-otp page
                session(['customer_otp_method' => 'phone']);
                
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
    
    public function render()
    {
        return view('livewire.customer.customer-login')
            ->layout('components.layouts.templates.fitness');
    }
}


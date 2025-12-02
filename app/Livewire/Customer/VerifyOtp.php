<?php

namespace App\Livewire\Customer;

use App\Models\User;
use App\Services\Yii2QueueDispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\Validate;

/**
 * Customer OTP Verification Component
 * 
 * Verifies OTP code and logs in the customer
 */
class VerifyOtp extends Component
{
    #[Validate('required|string|size:4')]
    public string $otp = '';
    
    public string $message = '';
    
    public function mount()
    {
        // Check if user ID is in session (from sendOtp step)
        if (!session()->has('customer_otp_user_id')) {
            return redirect()->route('login');
        }
    }
    
    /**
     * Verify OTP and login customer
     */
    public function verifyOtp()
    {
        $this->validate();
        
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
            return;
        }
        
        // Verify OTP
        if ($user->otp != $this->otp) {
            $this->message = 'Invalid OTP code. Please try again.';
            return;
        }
        
        // OTP is valid - login the user
        Auth::login($user);
        
        // Clear OTP and session data
        $user->clearOTP();
        session()->forget('customer_otp_user_id');
        session()->forget('customer_otp_method');
        
        Log::info('Customer logged in via OTP', [
            'user_id' => $user->id,
            'org_user_id' => $user->orgUser_id
        ]);
        
        // Dispatch Yii2 queue job for customer login (similar to other user operations)
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
        
        // Redirect to package purchase page or home
        return $this->redirect(route('customer.purchase-plan'), navigate: false);
    }
    
    public function resendOtp()
    {
        return redirect()->route('login');
    }
    
    public function render()
    {
        return view('livewire.customer.verify-otp')
            ->layout('components.layouts.templates.fitness');
    }
}


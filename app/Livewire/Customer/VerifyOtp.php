<?php

namespace App\Livewire\Customer;

use App\Models\CmsPage;
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
    public $navigationPages;
    
    public function mount()
    {
        // Check if user ID is in session (from sendOtp step)
        if (!session()->has('customer_otp_user_id')) {
            return redirect()->route('login');
        }
        
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
        
        // OTP is valid - login the user using 'customer' guard to separate from CMS admin
        Auth::guard('customer')->login($user);
        
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
    
    public function resendOtp()
    {
        return redirect()->route('login');
    }
    
    public function render()
    {
        return view('livewire.customer.verify-otp')
            ->layout('components.layouts.templates.fitness', [
                'navigationPages' => $this->navigationPages ?? collect(),
            ]);
    }
}


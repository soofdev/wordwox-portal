<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Mount the component - redirect if already authenticated via CMS guard
     */
    public function mount(): void
    {
        // If user is already authenticated via CMS guard, redirect to dashboard
        if (Auth::guard('cms')->check()) {
            $this->redirect(route('cms.dashboard'), navigate: false);
        }
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        Log::info('🚀 LOGIN PROCESS STARTED', [
            'timestamp' => now()->toDateTimeString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ]);

        // Step 1: Validation
        Log::info('📝 Starting form validation', [
            'email' => $this->email,
            'password_provided' => !empty($this->password),
            'password_length' => strlen($this->password),
            'remember' => $this->remember
        ]);

        $this->validate();
        Log::info('✅ Form validation passed');

        // Step 2: Rate limiting check
        Log::info('🛡️ Checking rate limiting', [
            'throttle_key' => $this->throttleKey(),
            'attempts' => RateLimiter::attempts($this->throttleKey())
        ]);

        $this->ensureIsNotRateLimited();
        Log::info('✅ Rate limiting check passed');

        // Step 3: Authentication attempt
        Log::info('🔐 Starting authentication attempt', [
            'email' => $this->email,
            'password_length' => strlen($this->password),
            'remember' => $this->remember,
            'auth_guard' => config('auth.defaults.guard'),
            'auth_provider' => config('auth.guards.' . config('auth.defaults.guard') . '.provider')
        ]);

        // Use 'cms' guard for CMS admin login to separate from customer login
        $authAttempt = Auth::guard('cms')->attempt(['email' => $this->email, 'password' => $this->password], $this->remember);
        
        if (!$authAttempt) {
            Log::error('❌ AUTHENTICATION FAILED', [
                'email' => $this->email,
                'reason' => 'Invalid credentials',
                'throttle_key' => $this->throttleKey(),
                'attempts_before' => RateLimiter::attempts($this->throttleKey())
            ]);
            
            RateLimiter::hit($this->throttleKey());
            
            Log::warning('🚫 Rate limiter hit applied', [
                'attempts_after' => RateLimiter::attempts($this->throttleKey())
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Step 4: Authentication successful
        $user = Auth::guard('cms')->user();
        Log::info('🎉 AUTHENTICATION SUCCESSFUL', [
            'user_id' => $user->id,
            'user_name' => $user->fullName,
            'email' => $user->email,
            'current_orgUser_id' => $user->orgUser_id,
            'session_regenerated' => session()->getId()
        ]);

        // Step 5: Check user's FOH access status
        Log::info('🏢 Checking FOH access status', [
            'user_id' => $user->id,
            'current_orgUser_id' => $user->orgUser_id,
            'has_current_orgUser' => !is_null($user->orgUser),
        ]);

        if ($user->orgUser) {
            Log::info('🏢 Current OrgUser details', [
                'orgUser_id' => $user->orgUser->id,
                'org_id' => $user->orgUser->org_id,
                'org_name' => $user->orgUser->org->name ?? 'Unknown',
                'isFohUser' => $user->orgUser->isFohUser,
                'has_foh_access_current' => (bool)$user->orgUser->isFohUser
            ]);
        } else {
            Log::warning('⚠️ No current orgUser set for user');
        }

        $hasAnyFohAccess = $user->hasAnyFohAccess();
        Log::info('🌐 FOH access check results', [
            'has_any_foh_access' => $hasAnyFohAccess,
            'total_foh_orgs' => $user->fohOrgUsers()->count()
        ]);

        // Step 6: Clear rate limiter and prepare redirect
        RateLimiter::clear($this->throttleKey());
        Log::info('✅ Rate limiter cleared');

        // Step 7: Redirect to CMS admin dashboard
        $dashboardRoute = route('cms.dashboard');
        Log::info('🚀 Redirecting to CMS admin dashboard', [
            'redirect_url' => $dashboardRoute,
            'navigate' => false,
            'middleware_will_check' => 'EnsureFohAccess middleware will validate FOH permissions'
        ]);
        
        // Use standard redirect instead of Livewire navigation to preserve session
        $this->redirect($dashboardRoute, navigate: false);
        
        Log::info('📤 LOGIN PROCESS COMPLETED - Redirect issued');
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}

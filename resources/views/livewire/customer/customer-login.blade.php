<div>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="card-title text-center mb-4 fw-bold">Customer Login</h2>
                        <p class="text-center text-muted mb-4">Login to purchase packages and manage your account</p>
                        
                        @if($message && !$otpSent)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ $message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        @if($message && $otpSent)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ $message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        @if(!$otpSent)
                            <form wire:submit="sendOtp">
                                <!-- Login Method Selection -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Login Method</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="loginMethod" id="loginEmail" value="email" wire:model.live="loginMethod" checked>
                                        <label class="btn btn-outline-primary" for="loginEmail">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="loginMethod" id="loginPhone" value="phone" wire:model.live="loginMethod">
                                        <label class="btn btn-outline-primary" for="loginPhone">
                                            <i class="fas fa-phone me-2"></i>Phone
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Email Input -->
                                @if($loginMethod === 'email')
                                    <div class="mb-3">
                                        <label for="identifier" class="form-label fw-semibold">Email Address</label>
                                        <input 
                                            type="email" 
                                            class="form-control @error('identifier') is-invalid @enderror" 
                                            id="identifier"
                                            wire:model="identifier"
                                            placeholder="Enter your email"
                                            required
                                            autofocus>
                                        @error('identifier')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @else
                                    <!-- Phone Input with Country Code -->
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label fw-semibold">Phone Number</label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select 
                                                    wire:model.live="phoneCountry" 
                                                    class="form-select @error('phoneCountry') is-invalid @enderror" 
                                                    id="phoneCountry" 
                                                    required>
                                                    <option value="US">🇺🇸 +1</option>
                                                    <option value="CA">🇨🇦 +1</option>
                                                    <option value="GB">🇬🇧 +44</option>
                                                    <option value="AU">🇦🇺 +61</option>
                                                    <option value="DE">🇩🇪 +49</option>
                                                    <option value="FR">🇫🇷 +33</option>
                                                    <option value="ES">🇪🇸 +34</option>
                                                    <option value="IT">🇮🇹 +39</option>
                                                    <option value="JP">🇯🇵 +81</option>
                                                    <option value="KR">🇰🇷 +82</option>
                                                    <option value="AE">🇦🇪 +971</option>
                                                    <option value="SA">🇸🇦 +966</option>
                                                    <option value="QA">🇶🇦 +974</option>
                                                    <option value="JO">🇯🇴 +962</option>
                                                </select>
                                                @error('phoneCountry')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-8">
                                                <input 
                                                    type="tel" 
                                                    class="form-control @error('phoneNumber') is-invalid @enderror" 
                                                    id="phoneNumber"
                                                    wire:model="phoneNumber"
                                                    placeholder="Enter phone number"
                                                    required
                                                    autofocus>
                                                @error('phoneNumber')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <button type="submit" class="btn btn-primary w-100 package-btn">
                                    <i class="fas fa-paper-plane me-2"></i>Send OTP
                                </button>
                            </form>
                        @else
                            <!-- OTP Input Form -->
                            <form wire:submit="verifyOtp">
                                <p class="text-center text-muted mb-4">
                                    Enter the 4-digit code sent to your {{ $loginMethod === 'email' ? 'email' : 'phone' }}
                                </p>
                                
                                <div class="mb-4">
                                    <label for="otp" class="form-label fw-semibold">OTP Code</label>
                                    <input 
                                        type="text" 
                                        class="form-control form-control-lg text-center @error('otp') is-invalid @enderror" 
                                        id="otp"
                                        wire:model="otp"
                                        placeholder="0000"
                                        maxlength="4"
                                        pattern="[0-9]{4}"
                                        required
                                        autofocus
                                        style="font-size: 2rem; letter-spacing: 0.5rem;">
                                    @error('otp')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted text-center d-block mt-2">Enter the 4-digit code</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 package-btn mb-3">
                                    <i class="fas fa-check me-2"></i>Verify & Login
                                </button>
                                
                                <div class="text-center mb-3">
                                    <button 
                                        type="button" 
                                        wire:click="resendOtp" 
                                        class="btn w-100 package-btn resend-otp-btn"
                                        id="resendOtpBtn"
                                        @if($resendCooldown > 0) disabled @endif>
                                        <span id="resendOtpText">
                                            @if($resendCooldown > 0)
                                                <i class="fas fa-redo me-2"></i>Resend OTP (<span id="countdownTimer">{{ $resendCooldown }}</span>s)
                                            @else
                                                <i class="fas fa-redo me-2"></i>Resend OTP
                                            @endif
                                        </span>
                                    </button>
                                </div>
                                
                                <button type="button" wire:click="$set('otpSent', false)" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                            </form>
                        @endif
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted small mb-0">
                                Don't have an account? 
                                <a href="{{ route('customer.signup') }}" class="text-decoration-none">Sign Up</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-check:checked + .btn-outline-primary {
            background: var(--fitness-primary, #ff6b6b);
            border-color: var(--fitness-primary, #ff6b6b);
            color: white;
        }
        
    </style>

    <script>
        let resendCountdownInterval = null;
        
        // Function to start/reset the resend countdown timer
        function startResendCountdown(seconds) {
            const resendBtn = document.getElementById('resendOtpBtn');
            const resendText = document.getElementById('resendOtpText');
            const countdownTimer = document.getElementById('countdownTimer');
            
            if (!resendBtn) return;
            
            // Clear any existing interval
            if (resendCountdownInterval) {
                clearInterval(resendCountdownInterval);
                resendCountdownInterval = null;
            }
            
            let remaining = parseInt(seconds) || 60;
            
            // Update button state immediately
            resendBtn.disabled = true;
            
            // Ensure countdown timer element exists
            if (!countdownTimer) {
                // Create countdown element if it doesn't exist
                resendText.innerHTML = `<i class="fas fa-redo me-2"></i>Resend OTP (<span id="countdownTimer">${remaining}</span>s)`;
            } else {
                // Update existing countdown element
                countdownTimer.textContent = remaining;
            }
            
            // Start countdown
            resendCountdownInterval = setInterval(() => {
                remaining--;
                
                const timerEl = document.getElementById('countdownTimer');
                if (timerEl) {
                    timerEl.textContent = remaining;
                }
                
                if (remaining <= 0) {
                    // Cooldown finished - enable button
                    clearInterval(resendCountdownInterval);
                    resendCountdownInterval = null;
                    resendBtn.disabled = false;
                    resendText.innerHTML = '<i class="fas fa-redo me-2"></i>Resend OTP';
                }
            }, 1000);
        }
        
        // Format OTP input to only accept numbers and auto-focus
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                });
            }
            
            // Start countdown if button is disabled on page load
            const resendBtn = document.getElementById('resendOtpBtn');
            const countdownTimer = document.getElementById('countdownTimer');
            if (resendBtn && resendBtn.disabled && countdownTimer) {
                const initialSeconds = parseInt(countdownTimer.textContent) || {{ $resendCooldown }};
                startResendCountdown(initialSeconds);
            }
        });
        
        // Auto-focus OTP input and start countdown when OTP is sent
        document.addEventListener('livewire:init', () => {
            Livewire.on('otp-sent', (event) => {
                const cooldown = event.detail?.cooldown || 60;
                
                setTimeout(() => {
                    const otpInput = document.getElementById('otp');
                    if (otpInput) {
                        otpInput.focus();
                    }
                    
                    // Start countdown timer after a short delay to ensure DOM is ready
                    startResendCountdown(cooldown);
                }, 300);
            });
            
            // Update countdown when Livewire updates the cooldown value
            Livewire.on('resend-cooldown-updated', (event) => {
                const cooldown = event.detail?.cooldown || 0;
                if (cooldown > 0) {
                    startResendCountdown(cooldown);
                }
            });
        });
        
        // Also listen for Livewire updates after navigation
        document.addEventListener('livewire:navigated', () => {
            const resendBtn = document.getElementById('resendOtpBtn');
            const countdownTimer = document.getElementById('countdownTimer');
            if (resendBtn && resendBtn.disabled && countdownTimer) {
                const initialSeconds = parseInt(countdownTimer.textContent) || 60;
                startResendCountdown(initialSeconds);
            }
        });
        
        // Clean up interval on page unload
        window.addEventListener('beforeunload', () => {
            if (resendCountdownInterval) {
                clearInterval(resendCountdownInterval);
            }
        });
    </script>
</div>


<div>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="card-title text-center mb-4 fw-bold">Customer Login</h2>
                        <p class="text-center text-muted mb-4">Login to purchase packages and manage your account</p>
                        
                        @if($message)
                            <div class="alert {{ $otpSent ? 'alert-success' : 'alert-danger' }} alert-dismissible fade show" role="alert">
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
                                        <input type="radio" class="btn-check" name="loginMethod" id="loginEmail" value="email" wire:model="loginMethod" checked>
                                        <label class="btn btn-outline-primary" for="loginEmail">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="loginMethod" id="loginPhone" value="phone" wire:model="loginMethod">
                                        <label class="btn btn-outline-primary" for="loginPhone">
                                            <i class="fas fa-phone me-2"></i>Phone
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Email/Phone Input -->
                                <div class="mb-3">
                                    <label for="identifier" class="form-label fw-semibold">
                                        {{ $loginMethod === 'email' ? 'Email Address' : 'Phone Number' }}
                                    </label>
                                    <input 
                                        type="{{ $loginMethod === 'email' ? 'email' : 'tel' }}" 
                                        class="form-control @error('identifier') is-invalid @enderror" 
                                        id="identifier"
                                        wire:model="identifier"
                                        placeholder="{{ $loginMethod === 'email' ? 'Enter your email' : 'Enter your phone number' }}"
                                        required
                                        autofocus>
                                    @error('identifier')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 package-btn">
                                    <i class="fas fa-paper-plane me-2"></i>Send OTP
                                </button>
                            </form>
                        @else
                            <div class="text-center">
                                <p class="text-success mb-4">
                                    <i class="fas fa-check-circle fa-2x mb-3"></i><br>
                                    OTP has been sent! Please check your {{ $loginMethod === 'email' ? 'email' : 'phone' }}.
                                </p>
                                <a href="{{ route('customer.verify-otp') }}" class="btn btn-primary w-100 package-btn">
                                    <i class="fas fa-key me-2"></i>Enter OTP Code
                                </a>
                                <button wire:click="$set('otpSent', false)" class="btn btn-outline-secondary w-100 mt-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                            </div>
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
</div>


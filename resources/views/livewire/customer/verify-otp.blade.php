<div>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="card-title text-center mb-4 fw-bold">Verify OTP</h2>
                        <p class="text-center text-muted mb-4">Enter the 4-digit code sent to your {{ session('customer_otp_method', 'email') }}</p>
                        
                        @if($message)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ $message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        <form wire:submit="verifyOtp">
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
                                <small class="form-text text-muted">Enter the 4-digit code</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 package-btn mb-3">
                                <i class="fas fa-check me-2"></i>Verify & Login
                            </button>
                            
                            <div class="text-center">
                                <button type="button" wire:click="resendOtp" class="btn btn-link text-decoration-none">
                                    <i class="fas fa-redo me-2"></i>Resend OTP
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left me-2"></i>Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus and format OTP input
        document.addEventListener('livewire:init', () => {
            Livewire.on('otp-verified', () => {
                // Handle successful verification if needed
            });
        });
        
        // Format OTP input to only accept numbers
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.getElementById('otp');
            if (otpInput) {
                otpInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/[^0-9]/g, '');
                });
            }
        });
    </script>
</div>


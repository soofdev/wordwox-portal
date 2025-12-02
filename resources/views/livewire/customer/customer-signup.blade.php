<div>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="card-title text-center mb-4 fw-bold">Create Account</h2>
                        <p class="text-center text-muted mb-4">Sign up to purchase packages and manage your account</p>
                        
                        @if(session('registration_success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                Registration successful! Please login to continue.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        @if($message && !$registrationSuccess)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ $message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        @if(!$registrationSuccess)
                            <form wire:submit="register">
                                <!-- Full Name -->
                                <div class="mb-3">
                                    <label for="fullName" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                    <input 
                                        type="text" 
                                        class="form-control @error('fullName') is-invalid @enderror" 
                                        id="fullName"
                                        wire:model="fullName"
                                        placeholder="Enter your full name"
                                        required>
                                    @error('fullName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Login Method Selection -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Login Method <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="loginMethod" id="signupEmail" value="email" wire:model="loginMethod" checked>
                                        <label class="btn btn-outline-primary" for="signupEmail">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="loginMethod" id="signupPhone" value="phone" wire:model="loginMethod">
                                        <label class="btn btn-outline-primary" for="signupPhone">
                                            <i class="fas fa-phone me-2"></i>Phone
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Email Input (required if email login) -->
                                @if($loginMethod === 'email')
                                    <div class="mb-3">
                                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <input 
                                            type="email" 
                                            class="form-control @error('email') is-invalid @enderror" 
                                            id="email"
                                            wire:model="email"
                                            placeholder="Enter your email address"
                                            required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @else
                                    <input type="hidden" wire:model="email">
                                @endif
                                
                                <!-- Phone Input (required if phone login) -->
                                @if($loginMethod === 'phone')
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select @error('phoneCountry') is-invalid @enderror" wire:model="phoneCountry" id="phoneCountry" required>
                                                    <option value="US">US (+1)</option>
                                                    <option value="GB">UK (+44)</option>
                                                    <option value="CA">CA (+1)</option>
                                                    <option value="AU">AU (+61)</option>
                                                    <option value="AE">AE (+971)</option>
                                                    <option value="SA">SA (+966)</option>
                                                    <option value="JO">JO (+962)</option>
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
                                                    required>
                                                @error('phoneNumber')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <!-- Optional phone for email login -->
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label fw-semibold">Phone Number (Optional)</label>
                                        <div class="row">
                                            <div class="col-4">
                                                <select class="form-select" wire:model="phoneCountry" id="phoneCountry">
                                                    <option value="US">US (+1)</option>
                                                    <option value="GB">UK (+44)</option>
                                                    <option value="CA">CA (+1)</option>
                                                    <option value="AU">AU (+61)</option>
                                                    <option value="AE">AE (+971)</option>
                                                    <option value="SA">SA (+966)</option>
                                                    <option value="JO">JO (+962)</option>
                                                </select>
                                            </div>
                                            <div class="col-8">
                                                <input 
                                                    type="tel" 
                                                    class="form-control @error('phoneNumber') is-invalid @enderror" 
                                                    id="phoneNumber"
                                                    wire:model="phoneNumber"
                                                    placeholder="Enter phone number">
                                                @error('phoneNumber')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Date of Birth -->
                                <div class="mb-3">
                                    <label for="dob" class="form-label fw-semibold">Date of Birth (Optional)</label>
                                    <input 
                                        type="date" 
                                        class="form-control @error('dob') is-invalid @enderror" 
                                        id="dob"
                                        wire:model="dob"
                                        max="{{ date('Y-m-d', strtotime('-1 day')) }}">
                                    @error('dob')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- Gender -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Gender (Optional)</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="gender" id="genderMale" value="1" wire:model="gender">
                                        <label class="btn btn-outline-secondary" for="genderMale">Male</label>
                                        
                                        <input type="radio" class="btn-check" name="gender" id="genderFemale" value="2" wire:model="gender">
                                        <label class="btn btn-outline-secondary" for="genderFemale">Female</label>
                                    </div>
                                </div>
                                
                                <!-- Address -->
                                <div class="mb-4">
                                    <label for="address" class="form-label fw-semibold">Address (Optional)</label>
                                    <textarea 
                                        class="form-control @error('address') is-invalid @enderror" 
                                        id="address"
                                        wire:model="address"
                                        rows="2"
                                        placeholder="Enter your address"></textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 package-btn">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </form>
                        @endif
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="text-muted small mb-0">
                                Already have an account? 
                                <a href="{{ route('login') }}" class="text-decoration-none">Login</a>
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
        
        .btn-check:checked + .btn-outline-secondary {
            background: var(--fitness-primary, #ff6b6b);
            border-color: var(--fitness-primary, #ff6b6b);
            color: white;
        }
    </style>
</div>


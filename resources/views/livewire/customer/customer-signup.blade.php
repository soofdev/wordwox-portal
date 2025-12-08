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
                                        <input type="radio" class="btn-check" name="loginMethod" id="signupEmail" value="email" wire:model.live="loginMethod" checked>
                                        <label class="btn btn-outline-primary" for="signupEmail">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="loginMethod" id="signupPhone" value="phone" wire:model.live="loginMethod">
                                        <label class="btn btn-outline-primary" for="signupPhone">
                                            <i class="fas fa-phone me-2"></i>Phone
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Email Input (required if email login) -->
                                @if($loginMethod === 'email')
                                    <div class="mb-3" wire:key="email-input-field">
                                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                        <input 
                                            type="email" 
                                            class="form-control @error('email') is-invalid @enderror" 
                                            id="email"
                                            wire:model="email"
                                            placeholder="Enter your email address"
                                            required
                                            autofocus>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                @endif
                                
                                <!-- Phone Input (required if phone login, optional if email login) -->
                                @if($loginMethod === 'phone')
                                    <div class="mb-3" wire:key="phone-input-field">
                                        <label for="phoneNumber" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                                        <div class="row">
                                            <div class="col-5">
                                                <select class="form-select @error('phoneCountry') is-invalid @enderror" wire:model.live="phoneCountry" id="phoneCountry" required>
                                                    @foreach($this->getSupportedCountries() as $code => $country)
                                                        <option value="{{ $code }}">
                                                            {{ $country['flag'] }} +{{ $country['code'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('phoneCountry')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-7">
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
                                @else
                                    <!-- Optional phone for email login -->
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label fw-semibold">Phone Number (Optional)</label>
                                        <div class="row">
                                            <div class="col-5">
                                                <select class="form-select" wire:model="phoneCountry" id="phoneCountry">
                                                    @foreach($this->getSupportedCountries() as $code => $country)
                                                        <option value="{{ $code }}">
                                                            {{ $country['flag'] }} +{{ $country['code'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-7">
                                                <input 
                                                    type="tel" 
                                                    class="form-control @error('phoneNumber') is-invalid @enderror" 
                                                    id="phoneNumber"
                                                    wire:model="phoneNumber"
                                                    placeholder="Enter phone number (optional)">
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
        /* Package Button Styling - Match Buy Button */
        .package-btn {
            font-size: 0.9rem;
            padding: 0.625rem 1.25rem;
            background: var(--fitness-button-bg, #4285F4) !important;
            border: 2px solid var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-text, #ffffff) !important;
            box-shadow: none !important;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .package-btn:hover:not(:disabled) {
            background: var(--fitness-primary-light) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-bg, #4285F4) !important;
            transform: translateY(-1px);
            box-shadow: none !important;
        }
        
        .package-btn:active:not(:disabled) {
            transform: translateY(0);
            box-shadow: none !important;
        }
        
        .package-btn:disabled {
            background: var(--fitness-button-bg, #4285F4) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-text, #ffffff) !important;
            opacity: 0.7;
            cursor: not-allowed;
            box-shadow: none !important;
        }
        
        /* Radio Button Groups - Login Method (Email/Phone) - Use button colors */
        .btn-group .btn-outline-primary,
        label.btn-outline-primary[for="signupEmail"],
        label.btn-outline-primary[for="signupPhone"] {
            border: 2px solid var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-bg, #4285F4) !important;
            background: transparent !important;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
        }
        
        .btn-group .btn-outline-primary:hover,
        label.btn-outline-primary[for="signupEmail"]:hover,
        label.btn-outline-primary[for="signupPhone"]:hover {
            background: rgba(66, 133, 244, 0.1) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-bg, #4285F4) !important;
            transform: translateY(-1px);
        }
        
        .btn-check:checked + .btn-outline-primary {
            background: var(--fitness-button-bg, #4285F4) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-text, #ffffff) !important;
            box-shadow: none !important;
        }
        
        /* Radio Button Groups - Gender (Male/Female) - Use button colors */
        .btn-group .btn-outline-secondary,
        label.btn-outline-secondary[for="genderMale"],
        label.btn-outline-secondary[for="genderFemale"] {
            border: 2px solid var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-bg, #4285F4) !important;
            background: transparent !important;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
        }
        
        .btn-group .btn-outline-secondary:hover,
        label.btn-outline-secondary[for="genderMale"]:hover,
        label.btn-outline-secondary[for="genderFemale"]:hover {
            background: rgba(66, 133, 244, 0.1) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-bg, #4285F4) !important;
            transform: translateY(-1px);
        }
        
        .btn-check:checked + .btn-outline-secondary {
            background: var(--fitness-button-bg, #4285F4) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-text, #ffffff) !important;
            box-shadow: none !important;
        }
        
        /* Ensure country code select has proper border styling */
        #phoneCountry.form-select {
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
        }
        
        #phoneCountry.form-select:hover {
            border-color: var(--fitness-button-bg, #4285F4);
        }
        
        #phoneCountry.form-select:focus {
            border-color: var(--fitness-button-bg, #4285F4);
            box-shadow: 0 0 0 0.25rem rgba(66, 133, 244, 0.25);
        }
        
        #phoneCountry.form-select.is-invalid {
            border-color: #dc3545;
        }
        
        /* Form inputs hover effect */
        .form-control:hover:not(:disabled):not(:focus) {
            border-color: var(--fitness-button-bg, #4285F4);
        }
        
        .form-control:focus {
            border-color: var(--fitness-button-bg, #4285F4);
            box-shadow: 0 0 0 0.25rem rgba(66, 133, 244, 0.25);
        }
        
        /* Login link styling */
        a.text-decoration-none {
            color: var(--fitness-button-bg, #4285F4) !important;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        a.text-decoration-none:hover {
            color: var(--fitness-button-bg, #4285F4) !important;
            opacity: 0.8;
            text-decoration: underline !important;
        }
    </style>
</div>


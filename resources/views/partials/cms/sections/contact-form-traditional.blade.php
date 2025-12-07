{{-- Traditional Contact Form (Similar to Yii Project) --}}
@php
    $orgId = $page->org_id ?? session('org_id') ?? env('CMS_DEFAULT_ORG_ID', 8);
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form id="contact-form" action="{{ route('contact.submit') }}" method="post" class="contact-form">
    @csrf
    <input type="hidden" name="org_id" value="{{ $orgId }}">
    
    <div class="form-group field-contactform-name required mb-3">
        <label for="contactform-name" class="form-label contact-form-label">Name</label>
        <input type="text" 
               id="contactform-name" 
               class="form-control contact-form-input @error('name') is-invalid @enderror" 
               name="name" 
               value="{{ old('name') }}"
               autofocus 
               aria-required="true"
               required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group field-contactform-email required mb-3">
        <label for="contactform-email" class="form-label contact-form-label">Email</label>
        <input type="email" 
               id="contactform-email" 
               class="form-control contact-form-input @error('email') is-invalid @enderror" 
               name="email" 
               value="{{ old('email') }}"
               aria-required="true"
               required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group field-contactform-subject required mb-3">
        <label for="contactform-subject" class="form-label contact-form-label">Subject</label>
        <input type="text" 
               id="contactform-subject" 
               class="form-control contact-form-input @error('subject') is-invalid @enderror" 
               name="subject" 
               value="{{ old('subject') }}"
               aria-required="true"
               required>
        @error('subject')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group field-contactform-body required mb-3">
        <label for="contactform-body" class="form-label contact-form-label">Body</label>
        <textarea id="contactform-body" 
                  class="form-control contact-form-textarea @error('body') is-invalid @enderror" 
                  name="body" 
                  rows="5"
                  aria-required="true"
                  required>{{ old('body') }}</textarea>
        @error('body')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Verification Code (CAPTCHA) --}}
    <div class="form-group field-contactform-verification required mb-3">
        <label for="contactform-verification" class="form-label contact-form-label">Verification Code</label>
        <div class="row g-2 align-items-center">
            <div class="col">
                <input type="text" 
                       id="contactform-verification" 
                       class="form-control contact-form-input @error('verification_code') is-invalid @enderror" 
                       name="verification_code" 
                       value="{{ old('verification_code') }}"
                       autocomplete="off"
                       aria-required="true"
                       required>
                @error('verification_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-auto">
                <img src="{{ route('captcha') }}?t={{ time() }}" 
                     alt="Verification Code" 
                     id="captcha-image"
                     class="border rounded"
                     style="cursor: pointer; height: 38px;"
                     onclick="this.src='{{ route('captcha') }}?t=' + Date.now()"
                     title="Click to refresh">
            </div>
        </div>
        <small class="form-text text-muted">Click on the image to refresh the code</small>
    </div>
    
    <div class="form-group mt-4">
        <button type="submit" class="btn btn-primary btn-fitness contact-submit-btn" name="contact-button" id="contact-submit-btn">
            <span class="submit-text">Submit</span>
            <span class="submit-loading d-none">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Sending...
            </span>
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('contact-form');
        const submitBtn = document.getElementById('contact-submit-btn');
        const submitText = submitBtn.querySelector('.submit-text');
        const submitLoading = submitBtn.querySelector('.submit-loading');
        
        if (form && submitBtn) {
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitText.classList.add('d-none');
                submitLoading.classList.remove('d-none');
            });
        }
    });
</script>


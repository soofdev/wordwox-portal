<div>
    @if($plan)
        <!-- Centered Success Block with Green Background -->
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6">
                    <div class="card shadow-lg border-0" style="background-color: #d4edda;">
                        <div class="card-body text-center p-5">
                            <!-- Success Message -->
                            <div class="mb-4">
                                <h4 class="fw-bold mb-0" style="color: #000000; font-size: 1.25rem;">
                                    Success! Thank you for your Payment.
                                </h4>
                            </div>
                            
                            <hr class="my-4" style="border-color: #000000; opacity: 0.2;">
                            
                            <!-- New Plan Heading -->
                            <h2 class="fw-bold mb-4" style="color: #000000; font-size: 1.75rem;">New Plan</h2>
                            
                            <!-- Plan Name -->
                            <h3 class="mb-4" style="color: #000000; font-size: 1.5rem; font-weight: 600;">{{ $plan->name }}</h3>
                            
                            <!-- Organization Name -->
                            @if(Auth::user() && Auth::user()->orgUser && Auth::user()->orgUser->org)
                                <p class="mb-0" style="color: #000000; font-size: 1rem;">{{ Auth::user()->orgUser->org->name }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @if($error)
                        <div class="alert alert-danger" role="alert">
                            <h4 class="alert-heading">Payment Processing Error</h4>
                            <p>{{ $error }}</p>
                            <hr>
                            <p class="mb-0">If you just completed a payment, please wait a moment and refresh the page. If the issue persists, please contact support.</p>
                            <div class="mt-3">
                                <a href="{{ route('home') }}" class="btn btn-primary me-2">Return to Home</a>
                                <button onclick="window.location.reload()" class="btn btn-secondary">Refresh Page</button>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <h4 class="alert-heading">Payment Information Not Found</h4>
                            <p>We couldn't find the payment details. If you just completed a payment, please wait a moment and refresh the page.</p>
                            <hr>
                            <p class="mb-0">The payment may still be processing. Please wait a few moments and refresh this page.</p>
                            <div class="mt-3">
                                <a href="{{ route('home') }}" class="btn btn-primary me-2">Return to Home</a>
                                <button onclick="window.location.reload()" class="btn btn-secondary">Refresh Page</button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>


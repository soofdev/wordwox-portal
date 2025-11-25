{{-- Fallback Content Partial --}}
@if($isFitness)
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h3 class="mb-3">Content Coming Soon</h3>
                    <p class="text-muted">We're working hard to bring you amazing content. Check back soon!</p>
                </div>
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
        </div>
    </div>
@elseif($isMeditative)
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 text-center">
                    <div class="mb-4">
                        <h2 class="mb-4">Content Coming Soon</h2>
                        <p>We're preparing something special for you. Please check back later.</p>
                    </div>
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <span class="icon-home mr-2"></span>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </section>
@else
    {{-- Default Fallback for Modern Template --}}
    <div class="max-w-4xl mx-auto text-center">
        <div class="mb-8">
            <i class="fas fa-exclamation-triangle text-6xl text-yellow-500 mb-6"></i>
            <h2 class="text-3xl font-bold mb-4">Content Coming Soon</h2>
            <p class="text-xl text-gray-600 mb-8">We're working hard to bring you amazing content. Check back soon!</p>
        </div>
        <a href="{{ url('/') }}" class="inline-flex items-center bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors">
            <i class="fas fa-home mr-2"></i>
            Back to Home
        </a>
    </div>
@endif
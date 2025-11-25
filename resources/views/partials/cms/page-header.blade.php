{{-- Page Header Partial --}}
@if($isMeditative)
        {{-- Meditative Template Hero Header --}}
        <section class="hero-wrap hero-wrap-2" style="background-image: url('{{ asset('images/bg_3.jpg') }}');" data-stellar-background-ratio="0.5">
            <div class="overlay"></div>
            <div class="container">
                <div class="row no-gutters slider-text js-fullheight align-items-center justify-content-center">
                    <div class="col-md-9 ftco-animate text-center">
                        <h1 class="mb-3 bread">{{ $page->title }}</h1>
                        @if($page->description)
                        <p class="breadcrumbs"><span class="mr-2"><a href="/">Home</a></span> <span>{{ $page->title }}</span></p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @elseif($isFitness)
        {{-- Fitness Template Hero Header --}}
        <section class="hero-section" style="background: linear-gradient(rgba(255,107,107,0.8), rgba(78,205,196,0.8)), url('{{ asset('images/fitness-bg.jpg') }}') center/cover;">
            <div class="container">
                <div class="row align-items-center justify-content-center text-center" style="min-height: 400px;">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold text-white mb-4">{{ $page->title }}</h1>
                        @if($page->description)
                        <p class="lead text-white mb-4">{{ $page->description }}</p>
                        @endif
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb justify-content-center bg-transparent">
                                <li class="breadcrumb-item">
                                    <a href="/" class="text-white text-decoration-none">
                                        <i class="fas fa-home me-1"></i>Home
                                    </a>
                                </li>
                                <li class="breadcrumb-item active text-white-50">{{ $page->title }}</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </section>
    @else
        {{-- Default Template Header --}}
        <div class="page-header bg-gray-50 py-16">
            <div class="container mx-auto px-6 text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ $page->title }}</h1>
                @if($page->description)
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">{{ $page->description }}</p>
                @endif
            </div>
        </div>
    @endif
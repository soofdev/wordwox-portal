<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@stack('title', config('app.name', 'Fitness Gym'))</title>
    
    @stack('meta')

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Fitness Template CSS -->
    @vite(['resources/css/fitness.css', 'resources/js/fitness.js'])
    @livewireStyles

    @stack('head')

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
        }
        
        .fitness-template {
            min-height: 100vh;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            color: white;
            padding: 100px 0;
        }
        
        .section-padding {
            padding: 80px 0;
        }
        
        .btn-fitness {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        
        .btn-fitness:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .card-fitness {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .card-fitness:hover {
            transform: translateY(-5px);
        }
        
        .navbar-fitness {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        .footer-fitness {
            background: #2c3e50;
            color: white;
            padding: 50px 0 20px 0;
        }
        
        @php
            // Determine page type for specific styling
            $pageType = $page->type ?? 'default';
            $pageSlug = $page->slug ?? 'home';
        @endphp
    </style>
</head>
<body class="fitness-template">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-fitness fixed-top">
        <div class="container">
            @php
                $orgId = env('CMS_DEFAULT_ORG_ID', 8);
                $org = \App\Models\Org::find($orgId);
                $orgName = $org?->name ?? config('app.name', 'Wodworx');
                $orgLogo = $org?->logoFilePath;
                
                // Construct S3 URL for logo
                $logoUrl = null;
                if ($orgLogo) {
                    $bucket = env('AWS_BUCKET', 'wodworx-dev');
                    $region = env('AWS_DEFAULT_REGION', 'us-east-1');
                    $logoUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$orgLogo}";
                }
            @endphp
            <a class="navbar-brand fw-bold fs-3" href="/">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $orgName }}" class="me-2" style="height: 40px; width: auto;">
                @else
                    <i class="fas fa-dumbbell text-danger me-2"></i>
                @endif
                {{ $orgName }}
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Home Link -->
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('/') || request()->is('home') ? 'active fw-bold' : '' }}" 
                           href="/">Home</a>
                    </li>
                    
                    <!-- Dynamic Navigation Pages -->
                    @if(isset($navigationPages) && $navigationPages->count() > 0)
                        @foreach($navigationPages as $navPage)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is($navPage->slug) || request()->is($navPage->slug . '/*') ? 'active fw-bold' : '' }}" 
                                   href="/{{ $navPage->slug }}">{{ $navPage->title }}</a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="fitness-content" style="margin-top: 80px;">
        @php
            // Add page-specific classes and styling based on page type
            $pageClasses = match($pageType) {
                'home' => 'home-page',
                'about' => 'about-page',
                'contact' => 'contact-page',
                'blog' => 'blog-page',
                'coaches' => 'coaches-page',
                'packages' => 'packages-page',
                'schedule' => 'schedule-page',
                default => 'default-page'
            };
        @endphp
        
        <div class="{{ $pageClasses }}">
            {{ $slot }}
        </div>
    </main>

    <!-- Footer -->
    @php
        // Get dynamic footer blocks instead of static footer data
        $footerBlocks = \App\Models\CmsSection::where('container', 'footer')
            ->where('cms_page_id', null)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    @endphp
    <footer class="footer-fitness">
        <div class="container">
            @if($footerBlocks->count() > 0)
                <!-- Dynamic Footer Blocks in 4-Column Grid -->
                <div class="row g-4">
                    @foreach($footerBlocks->take(4) as $index => $block)
                        <div class="col-lg-3 col-md-6 mb-4">
                            <div class="footer-widget">
                                @switch($block->type)
                                    @case('heading')
                                        @if($block->content)
                                            <h5 class="fw-bold mb-3 text-white">
                                                <i class="fas fa-dumbbell text-danger me-2"></i>
                                                {{ $block->content }}
                                            </h5>
                                        @endif
                                        @break
                                    
                                    @case('text')
                                        @if($block->content)
                                            <div class="text-white-50">
                                                {{ $block->content }}
                                            </div>
                                        @endif
                                        @break
                                    
                                    @case('paragraph')
                                        @if($block->content)
                                            <div class="text-white-50">
                                                {!! $block->content !!}
                                            </div>
                                        @endif
                                        @break
                                    
                                    @case('html')
                                        @if($block->content)
                                            <div class="footer-html-content">
                                                {!! $block->content !!}
                                            </div>
                                        @endif
                                        @break
                                    
                                    @case('links')
                                        @php
                                            $links = [];
                                            try {
                                                if (is_string($block->content)) {
                                                    $links = json_decode($block->content, true) ?? [];
                                                } elseif (is_array($block->content)) {
                                                    $links = $block->content;
                                                }
                                            } catch (\Exception $e) {
                                                $links = [];
                                            }
                                        @endphp
                                        @if(is_array($links) && count($links) > 0)
                                            <ul class="list-unstyled">
                                                @foreach($links as $link)
                                                    @if(is_array($link) && isset($link['label']) && isset($link['url']))
                                                        <li class="mb-2">
                                                            <a href="{{ $link['url'] }}" class="text-white-50 text-decoration-none">
                                                                <i class="fas fa-chevron-right me-2"></i>{{ $link['label'] }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                        @break
                                    
                                    @case('contact')
                                        @php
                                            $contactData = is_array($block->data) ? $block->data : (json_decode($block->data ?? '{}', true) ?? []);
                                        @endphp
                                        <div class="contact-info">
                                            @if(!empty($contactData['address']))
                                                <p class="mb-2 text-white-50">
                                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                                    {{ $contactData['address'] }}
                                                </p>
                                            @endif
                                            @if(!empty($contactData['phone']))
                                                <p class="mb-2">
                                                    <i class="fas fa-phone me-2 text-danger"></i>
                                                    <a href="tel:{{ $contactData['phone'] }}" class="text-white-50 text-decoration-none">
                                                        {{ $contactData['phone'] }}
                                                    </a>
                                                </p>
                                            @endif
                                            @if(!empty($contactData['email']))
                                                <p class="mb-2">
                                                    <i class="fas fa-envelope me-2 text-danger"></i>
                                                    <a href="mailto:{{ $contactData['email'] }}" class="text-white-50 text-decoration-none">
                                                        {{ $contactData['email'] }}
                                                    </a>
                                                </p>
                                            @endif
                                        </div>
                                        @break
                                    
                                    @case('image')
                                        @php
                                            $imageData = is_array($block->data) ? $block->data : (json_decode($block->data ?? '{}', true) ?? []);
                                            $imageUrl = $imageData['url'] ?? $block->content ?? null;
                                        @endphp
                                        @if($imageUrl)
                                            <div class="footer-image mb-3">
                                                <img src="{{ $imageUrl }}" 
                                                     alt="{{ $imageData['alt'] ?? 'Footer image' }}" 
                                                     class="img-fluid rounded">
                                                @if(!empty($imageData['caption']))
                                                    <p class="text-white-50 small mt-2 mb-0">{{ $imageData['caption'] }}</p>
                                                @endif
                                            </div>
                                        @endif
                                        @break
                                    
                                    @case('spacer')
                                        @php
                                            $height = $block->content ?: 30;
                                        @endphp
                                        <div style="height: {{ $height }}px;"></div>
                                        @break
                                    
                                    @default
                                        @if($block->content)
                                            <div class="text-white-50">
                                                {!! $block->content !!}
                                            </div>
                                        @endif
                                        @break
                                @endswitch
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback: Default Footer Content when no blocks exist -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">
                                <i class="fas fa-dumbbell text-danger me-2"></i>
                                {{ config('app.name', 'Fitness Gym') }}
                            </h5>
                            <p class="text-white-50 mb-3">Transform your body and mind with our comprehensive fitness programs designed for all levels.</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">Quick Links</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <a href="/" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Home
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="/about" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>About Us
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="/coaches" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Our Coaches
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">Classes</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <a href="#" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Strength Training
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Cardio Fitness
                                    </a>
                                </li>
                                <li class="mb-2">
                                    <a href="#" class="text-white-50 text-decoration-none">
                                        <i class="fas fa-chevron-right me-2"></i>Group Classes
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-widget">
                            <h5 class="fw-bold mb-3 text-white">Contact Info</h5>
                            <div class="contact-info">
                                <p class="mb-2 text-white-50">
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    123 Fitness Street, Gym City
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-phone me-2 text-danger"></i>
                                    <a href="tel:+1234567890" class="text-white-50 text-decoration-none">
                                        +1 (234) 567-890
                                    </a>
                                </p>
                                <p class="mb-2">
                                    <i class="fas fa-envelope me-2 text-danger"></i>
                                    <a href="mailto:info@fitnessgym.com" class="text-white-50 text-decoration-none">
                                        info@fitnessgym.com
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Copyright Section -->
            <hr class="my-4 border-secondary">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0 text-white-50">
                        &copy; {{ date('Y') }} {{ config('app.name', 'Fitness Gym') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
    @stack('scripts')
    
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-fitness');
            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            }
        });
    </script>
</body>
</html>
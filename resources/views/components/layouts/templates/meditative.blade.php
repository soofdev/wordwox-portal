<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@stack('title', config('app.name', 'Meditative'))</title>
    
    @stack('meta')

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=EB+Garamond:400,400i,500,500i,600,600i,700,700i&display=swap" rel="stylesheet">

    <!-- CSS Dependencies from CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.css">

    <!-- Scripts -->
    @vite(['resources/css/meditative.css', 'resources/js/meditative.js'])
    @livewireStyles

    @stack('head')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
        <div class="container">
            <div class="row m-auto">
                <div class="col-12 w-100 text-center">
                    @php
                        $orgId = env('CMS_DEFAULT_ORG_ID', 8);
                        $org = \App\Models\Org::find($orgId);
                        $orgName = $org?->name ?? config('app.name', 'Meditative');
                        $orgLogo = $org?->logoFilePath;
                        
                        // Construct S3 URL for logo
                        $logoUrl = null;
                        if ($orgLogo) {
                            $bucket = env('AWS_BUCKET', 'wodworx-dev');
                            $region = env('AWS_DEFAULT_REGION', 'us-east-1');
                            $logoUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$orgLogo}";
                        }
                    @endphp
                    <a class="navbar-brand w-100" href="/">
                        @if($logoUrl)
                            <div class="d-flex align-items-center justify-content-center">
                                <img src="{{ $logoUrl }}" alt="{{ $orgName }}" class="me-2" style="height: 35px; width: auto;">
                                <span>{{ $orgName }}</span>
                            </div>
                        @else
                            {{ $orgName }}
                        @endif
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="oi oi-menu"></span> Menu
                    </button>
                </div>
                <div class="col-12 w-100 text-center">
                    <div class="collapse navbar-collapse" id="ftco-nav">
                        <ul class="navbar-nav m-auto">
                            <!-- Home Link -->
                            <li class="nav-item {{ request()->is('/') || request()->is('home') || request()->is('site') ? 'active' : '' }}">
                                <a href="/site" class="nav-link">Home</a>
                            </li>
                            
                            <!-- Dynamic Navigation Pages (ordered by sort_order) -->
                            @if(isset($navigationPages) && $navigationPages->count() > 0)
                                @foreach($navigationPages as $navPage)
                                    <li class="nav-item {{ request()->is($navPage->slug) || request()->is($navPage->slug . '/*') ? 'active' : '' }}">
                                        <a href="/{{ $navPage->slug }}" class="nav-link">{{ $navPage->title }}</a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <!-- END nav -->

    <!-- Page Content -->
    <div class="meditative-template">
        {{ $slot }}
    </div>

    <!-- Footer -->
    @php
        // Get dynamic footer blocks instead of static footer data
        $footerBlocks = \App\Models\CmsSection::where('container', 'footer')
            ->where('cms_page_id', null)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    @endphp
    <footer class="ftco-footer ftco-section bg-light">
        <div class="container">
            @if($footerBlocks->count() > 0)
                <!-- Dynamic Footer Blocks in 4-Column Grid -->
                <div class="row d-flex">
                    @foreach($footerBlocks->take(4) as $index => $block)
                        <div class="col-md">
                            <div class="ftco-footer-widget mb-4 {{ $index > 0 ? 'ml-md-4' : '' }}">
                                @switch($block->type)
                                    @case('heading')
                                        @if($block->content)
                                            <h2 class="ftco-heading-2">{{ $block->content }}</h2>
                                        @endif
                                        @break
                                    
                                    @case('text')
                                        @if($block->name)
                                            <h2 class="ftco-heading-2">{{ $block->name }}</h2>
                                        @endif
                                        @if($block->content)
                                            <p>{{ $block->content }}</p>
                                        @endif
                                        @break
                                    
                                    @case('paragraph')
                                        @if($block->name)
                                            <h2 class="ftco-heading-2">{{ $block->name }}</h2>
                                        @endif
                                        @if($block->content)
                                            <div>{!! $block->content !!}</div>
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
                                        @if($block->name)
                                            <h2 class="ftco-heading-2">{{ $block->name }}</h2>
                                        @endif
                                        @if(is_array($links) && count($links) > 0)
                                            <ul class="list-unstyled">
                                                @foreach($links as $link)
                                                    @if(is_array($link) && isset($link['label']) && isset($link['url']))
                                                        <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        @endif
                                        @break
                                    
                                    @case('contact')
                                        @php
                                            $contactData = is_array($block->data) ? $block->data : (json_decode($block->data ?? '{}', true) ?? []);
                                        @endphp
                                        @if($block->content)
                                            <h2 class="ftco-heading-2">{{ $block->content }}</h2>
                                        @endif
                                        <div class="block-23 mb-3">
                                            <ul>
                                                @if(!empty($contactData['address']))
                                                    <li><span class="icon icon-map-marker"></span><span class="text">{{ $contactData['address'] }}</span></li>
                                                @endif
                                                @if(!empty($contactData['phone']))
                                                    <li><a href="tel:{{ $contactData['phone'] }}"><span class="icon icon-phone"></span><span class="text">{{ $contactData['phone'] }}</span></a></li>
                                                @endif
                                                @if(!empty($contactData['email']))
                                                    <li><a href="mailto:{{ $contactData['email'] }}"><span class="icon icon-envelope"></span><span class="text">{{ $contactData['email'] }}</span></a></li>
                                                @endif
                                            </ul>
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
                                                    <p class="small mt-2 mb-0">{{ $imageData['caption'] }}</p>
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
                                        @if($block->name)
                                            <h2 class="ftco-heading-2">{{ $block->name }}</h2>
                                        @endif
                                        @if($block->content)
                                            <div>{!! $block->content !!}</div>
                                        @endif
                                        @break
                                @endswitch
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback: Default Footer Content when no blocks exist -->
                <div class="row d-flex">
                    <div class="col-md">
                        <div class="ftco-footer-widget mb-4">
                            <h2 class="ftco-heading-2">{{ config('app.name', 'Meditative') }}</h2>
                            <p>Discover the perfect balance of strength and serenity. Our mindful approach to fitness nurtures both body and spirit.</p>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="ftco-footer-widget mb-4 ml-md-4">
                            <h2 class="ftco-heading-2">Quick Links</h2>
                            <ul class="list-unstyled">
                                <li><a href="/">Home</a></li>
                                <li><a href="/about">About</a></li>
                                <li><a href="/coaches">Coaches</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="ftco-footer-widget mb-4">
                            <h2 class="ftco-heading-2">Classes</h2>
                            <ul class="list-unstyled">
                                <li><a href="#">Yoga Flow</a></li>
                                <li><a href="#">Meditation</a></li>
                                <li><a href="#">Mindful Movement</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="ftco-footer-widget mb-4">
                            <h2 class="ftco-heading-2">Contact</h2>
                            <div class="block-23 mb-3">
                                <ul>
                                    <li><span class="icon icon-map-marker"></span><span class="text">123 Serenity Lane</span></li>
                                    <li><a href="tel:+1555123PEACE"><span class="icon icon-phone"></span><span class="text">+1 (555) 123-PEACE</span></a></li>
                                    <li><a href="mailto:hello@meditative.com"><span class="icon icon-envelope"></span><span class="text">hello@meditative.com</span></a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">
                        Copyright &copy; {{ date('Y') }} {{ config('app.name', 'Meditative') }}. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <!-- jQuery and Bootstrap from CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    
    <!-- Third-party plugins from CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.stellar/1.2.0/jquery.stellar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-animateNumber/0.0.14/jquery.animateNumber.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-timepicker/1.10.0/jquery.timepicker.min.js"></script>
    
    <!-- Scrollax from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/scrollax@1.0.0/scrollax.min.js"></script>
    
    @livewireScripts
    @stack('scripts')
</body>
</html>

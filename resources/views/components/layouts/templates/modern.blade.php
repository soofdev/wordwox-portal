<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@stack('title', config('app.name', 'Laravel'))</title>
    
    @stack('meta')

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/modern.css', 'resources/js/app.js', 'resources/js/modern.js'])
    @livewireStyles

    @stack('head')
</head>
<body class="font-sans antialiased h-full bg-gray-900 text-white">
    <div class="min-h-full modern-gradient">
        <!-- Modern Glass Navigation -->
        <nav class="fixed top-4 left-4 right-4 z-50 glass-effect rounded-2xl">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        @php
                            $orgId = env('CMS_DEFAULT_ORG_ID', 8);
                            $org = \App\Models\Org::find($orgId);
                            $orgName = $org?->name ?? config('app.name', 'SuperHero CrossFit');
                            $orgLogo = $org?->logoFilePath;
                            
                            // Construct S3 URL for logo
                            $logoUrl = null;
                            if ($orgLogo) {
                                $bucket = env('AWS_BUCKET', 'wodworx-dev');
                                $region = env('AWS_DEFAULT_REGION', 'us-east-1');
                                $logoUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$orgLogo}";
                            }
                        @endphp
                        <div class="flex-shrink-0">
                            <a href="/" class="text-2xl font-bold text-white neon-glow flex items-center">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $orgName }}" class="me-2" style="height: 32px; width: auto;">
                                @else
                                    ⚡ 
                                @endif
                                {{ $orgName }}
                            </a>
                        </div>

                        <div class="hidden md:ml-8 md:flex md:space-x-6">
                            <a href="/" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">🏠 Home</a>
                            <a href="/about-us" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">👥 About</a>
                            <a href="/packages" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">💎 Packages</a>
                            <a href="/coaches" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">🏋️ Coaches</a>
                            <a href="/schedule" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">📅 Schedule</a>
                            <a href="/contact-us" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">📞 Contact</a>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="/dashboard" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">📊 Dashboard</a>
                            <a href="/cms-admin" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-full text-sm font-medium hover:from-purple-600 hover:to-pink-600 transition-all neon-glow">⚙️ CMS</a>
                        @else
                            <a href="{{ route('cms.login') }}" class="text-white/90 hover:text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-white/10 transition-all">🔐 Login</a>
                            <a href="/packages" class="bg-gradient-to-r from-blue-500 to-purple-500 text-white px-6 py-2 rounded-full text-sm font-medium hover:from-blue-600 hover:to-purple-600 transition-all neon-glow">🚀 Get Started</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content with top padding for fixed nav -->
        <main class="pt-24 pb-16">
            {{ $slot }}
        </main>

        <!-- Modern Footer with Dynamic Blocks -->
        @php
            // Get dynamic footer blocks instead of static footer data
            $footerBlocks = \App\Models\CmsSection::where('container', 'footer')
                ->where('cms_page_id', null)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        @endphp
        <footer class="bg-black/30 backdrop-blur-lg border-t border-white/10">
            <div class="max-w-7xl mx-auto py-16 px-6">
                @if($footerBlocks->count() > 0)
                    <!-- Dynamic Footer Blocks in 4-Column Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        @foreach($footerBlocks->take(4) as $index => $block)
                            <div class="{{ $block->type === 'text' && $index === 0 ? 'col-span-1 md:col-span-2' : 'modern-card p-6 rounded-2xl' }}">
                                @switch($block->type)
                                    @case('heading')
                                        @if($block->content)
                                            <h3 class="text-2xl font-bold text-white mb-4 floating-animation">
                                                ⚡ {{ $block->content }}
                                            </h3>
                                        @endif
                                        @break
                                    
                                    @case('text')
                                        @if($block->name)
                                            <h3 class="text-2xl font-bold text-white mb-4 floating-animation">
                                                ⚡ {{ $block->name }}
                                            </h3>
                                        @endif
                                        @if($block->content)
                                            <p class="text-white/80 mb-6 text-lg">{{ $block->content }}</p>
                                        @endif
                                        @break
                                    
                                    @case('paragraph')
                                        @if($block->name)
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $block->name }}</h3>
                                        @endif
                                        @if($block->content)
                                            <div class="text-gray-700">{!! $block->content !!}</div>
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
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4">🔗 {{ $block->name }}</h3>
                                        @endif
                                        @if(is_array($links) && count($links) > 0)
                                            <ul class="space-y-3">
                                                @foreach($links as $link)
                                                    @if(is_array($link) && isset($link['label']) && isset($link['url']))
                                                        <li><a href="{{ $link['url'] }}" class="text-gray-700 hover:text-blue-600 transition-colors">{{ $link['label'] }}</a></li>
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
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4">📍 {{ $block->content }}</h3>
                                        @endif
                                        <ul class="space-y-3 text-gray-700">
                                            @if(!empty($contactData['address']))
                                                <li class="flex items-center">
                                                    <span class="mr-2">🏢</span>
                                                    {{ $contactData['address'] }}
                                                </li>
                                            @endif
                                            @if(!empty($contactData['phone']))
                                                <li class="flex items-center">
                                                    <span class="mr-2">📞</span>
                                                    <a href="tel:{{ $contactData['phone'] }}" class="hover:text-blue-600 transition-colors">{{ $contactData['phone'] }}</a>
                                                </li>
                                            @endif
                                            @if(!empty($contactData['email']))
                                                <li class="flex items-center">
                                                    <span class="mr-2">✉️</span>
                                                    <a href="mailto:{{ $contactData['email'] }}" class="hover:text-blue-600 transition-colors">{{ $contactData['email'] }}</a>
                                                </li>
                                            @endif
                                        </ul>
                                        @break
                                    
                                    @case('image')
                                        @php
                                            $imageData = is_array($block->data) ? $block->data : (json_decode($block->data ?? '{}', true) ?? []);
                                            $imageUrl = $imageData['url'] ?? $block->content ?? null;
                                        @endphp
                                        @if($imageUrl)
                                            <div class="footer-image mb-4">
                                                <img src="{{ $imageUrl }}" 
                                                     alt="{{ $imageData['alt'] ?? 'Footer image' }}" 
                                                     class="w-full rounded-xl">
                                                @if(!empty($imageData['caption']))
                                                    <p class="text-gray-600 text-sm mt-2">{{ $imageData['caption'] }}</p>
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
                                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $block->name }}</h3>
                                        @endif
                                        @if($block->content)
                                            <div class="text-gray-700">{!! $block->content !!}</div>
                                        @endif
                                        @break
                                @endswitch
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Fallback: Default Footer Content when no blocks exist -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <div class="col-span-1 md:col-span-2">
                            <h3 class="text-2xl font-bold text-white mb-4 floating-animation">
                                ⚡ {{ config('app.name', 'SuperHero CrossFit') }}
                            </h3>
                            <p class="text-white/80 mb-6 text-lg">Experience the future of fitness with our cutting-edge training programs and modern facilities.</p>
                            <div class="flex space-x-4">
                                <a href="#" class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform neon-glow">
                                    📘
                                </a>
                                <a href="#" class="w-12 h-12 bg-gradient-to-r from-pink-500 to-red-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform neon-glow">
                                    📷
                                </a>
                                <a href="#" class="w-12 h-12 bg-gradient-to-r from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform neon-glow">
                                    🐦
                                </a>
                            </div>
                        </div>
                        
                        <div class="modern-card p-6 rounded-2xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">🔗 Quick Links</h3>
                            <ul class="space-y-3">
                                <li><a href="/about-us" class="text-gray-700 hover:text-blue-600 transition-colors">About Us</a></li>
                                <li><a href="/packages" class="text-gray-700 hover:text-blue-600 transition-colors">Packages</a></li>
                                <li><a href="/coaches" class="text-gray-700 hover:text-blue-600 transition-colors">Coaches</a></li>
                                <li><a href="/schedule" class="text-gray-700 hover:text-blue-600 transition-colors">Schedule</a></li>
                            </ul>
                        </div>
                        
                        <div class="modern-card p-6 rounded-2xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">📍 Contact Info</h3>
                            <ul class="space-y-3 text-gray-700">
                                <li class="flex items-center">
                                    <span class="mr-2">🏢</span>
                                    123 Future Street
                                </li>
                                <li class="flex items-center">
                                    <span class="mr-2">📞</span>
                                    +1 (555) 123-HERO
                                </li>
                                <li class="flex items-center">
                                    <span class="mr-2">✉️</span>
                                    info@superhero.wodworx.com
                                </li>
                            </ul>
                        </div>
                    </div>
                @endif
                
                <div class="mt-12 pt-8 border-t border-white/20">
                    <p class="text-center text-white/60 text-sm">
                        &copy; {{ date('Y') }} {{ config('app.name', 'SuperHero CrossFit') }}. Powered by the future. ⚡
                    </p>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>

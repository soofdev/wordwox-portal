<div>
    @if($page)
        {{-- Set page title and meta tags --}}
        @push('title')
            {{ $page->seo_title ?? $page->title }}
        @endpush

        @if($page->seo_description)
            @push('meta')
                <meta name="description" content="{{ $page->seo_description }}">
            @endpush
        @endif

        @if($page->seo_keywords)
            @push('meta')
                <meta name="keywords" content="{{ $page->seo_keywords }}">
            @endpush
        @endif

        <div class="cms-page" data-page-type="{{ $page->type }}" data-page-id="{{ $page->id }}">
            
            {{-- Page Header (only show for non-home pages without hero sections) --}}
            @if($page->type !== 'home' && !$this->hasHeroSection())
                <div class="page-header bg-gray-50 py-16">
                    <div class="container mx-auto px-6 text-center">
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">{{ $page->title }}</h1>
                        @if($page->description)
                            <p class="text-xl text-gray-600 max-w-3xl mx-auto">{{ $page->description }}</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Page Sections --}}
            @if($page->sections && $page->sections->count() > 0)
                <div class="cms-sections">
                    @foreach($page->sections as $section)
                        <div class="cms-section cms-section-{{ $section->type }}" id="section-{{ $section->id }}">
                            @switch($section->type)
                                @case('hero')
                                    @php
                                        $settings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
                                        $bgColor = $settings['background_color'] ?? '#1f2937';
                                        $textColor = $settings['text_color'] ?? '#ffffff';
                                    @endphp
                                    <div class="hero-section py-20 px-6" 
                                         style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                                        <div class="container mx-auto text-center">
                                            @if($section->title)
                                                <h1 class="text-5xl md:text-6xl font-bold mb-6">{{ $section->title }}</h1>
                                            @endif
                                            @if($section->subtitle)
                                                <p class="text-xl md:text-2xl mb-8 opacity-90">{{ $section->subtitle }}</p>
                                            @endif
                                            @if($section->content)
                                                <div class="text-lg mb-8 max-w-3xl mx-auto">{!! $section->content !!}</div>
                                            @endif
                                        </div>
                                    </div>
                                    @break

                                @case('heading')
                                    <div class="heading-section py-8">
                                        <div class="container mx-auto px-6">
                                            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">{{ $section->content }}</h2>
                                        </div>
                                    </div>
                                    @break

                                @case('paragraph')
                                    <div class="paragraph-section py-6">
                                        <div class="container mx-auto px-6">
                                            <div class="max-w-none text-base leading-relaxed text-gray-700 ck-content">
                                                {!! $section->content !!}
                                            </div>
                                        </div>
                                    </div>
                                    @break

                                @case('quote')
                                    <div class="quote-section py-12 bg-gray-50">
                                        <div class="container mx-auto px-6 text-center">
                                            <blockquote class="text-2xl md:text-3xl italic text-gray-700 mb-6">
                                                "{{ $section->content }}"
                                            </blockquote>
                                            @if($section->title)
                                                <cite class="text-lg text-gray-600">— {{ $section->title }}</cite>
                                            @endif
                                        </div>
                                    </div>
                                    @break

                                @case('list')
                                    <div class="list-section py-6">
                                        <div class="container mx-auto px-6">
                                            @php
                                                $items = explode("\n", $section->content);
                                                $items = array_filter(array_map('trim', $items));
                                            @endphp
                                            <ul class="space-y-3 text-lg">
                                                @foreach($items as $item)
                                                    @php
                                                        $item = preg_replace('/^[•\-\*]\s*/', '', $item);
                                                    @endphp
                                                    <li class="flex items-start">
                                                        <span class="text-blue-600 mr-3 mt-1">•</span>
                                                        <span>{{ $item }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                    @break

                                @case('button')
                                    <div class="button-section py-8">
                                        <div class="container mx-auto px-6 text-center">
                                            <a href="{{ $section->title ?: '#' }}" 
                                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-4 rounded-lg transition-colors">
                                                {{ $section->content ?: 'Click me' }}
                                            </a>
                                        </div>
                                    </div>
                                    @break

                                @case('spacer')
                                    @php
                                        $height = (int)($section->content ?: 50);
                                    @endphp
                                    <div class="spacer-section" style="height: {{ $height }}px;"></div>
                                    @break

                                @case('code')
                                    <div class="code-section py-6">
                                        <div class="container mx-auto px-6">
                                            <pre class="bg-gray-900 text-green-400 p-6 rounded-lg overflow-x-auto"><code>{{ $section->content }}</code></pre>
                                        </div>
                                    </div>
                                    @break

                                @case('cta')
                                    @php
                                        $data = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                                        $buttons = $data['buttons'] ?? [];
                                    @endphp
                                    <div class="cta-section py-16 bg-blue-600 text-white">
                                        <div class="container mx-auto px-6 text-center">
                                            @if($section->title)
                                                <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ $section->title }}</h2>
                                            @endif
                                            @if($section->content)
                                                <p class="text-xl mb-8 opacity-90">{{ $section->content }}</p>
                                            @endif
                                            @if(!empty($buttons))
                                                <div class="flex flex-wrap justify-center gap-4">
                                                    @foreach($buttons as $button)
                                                        @php
                                                            $buttonClass = 'inline-block px-8 py-4 rounded-lg font-semibold transition-colors ';
                                                            switch($button['style'] ?? 'primary') {
                                                                case 'primary':
                                                                    $buttonClass .= 'bg-white text-blue-600 hover:bg-gray-100';
                                                                    break;
                                                                case 'secondary':
                                                                    $buttonClass .= 'bg-gray-600 text-white hover:bg-gray-700';
                                                                    break;
                                                                case 'outline':
                                                                    $buttonClass .= 'border-2 border-white text-white hover:bg-white hover:text-blue-600';
                                                                    break;
                                                            }
                                                        @endphp
                                                        <a href="{{ $button['url'] ?? '#' }}" class="{{ $buttonClass }}">
                                                            {{ $button['text'] ?? 'Button' }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @break

                                @case('image')
                                    <div class="image-section py-8">
                                        <div class="container mx-auto px-6 text-center">
                                            <div class="bg-gray-200 rounded-lg p-12 mb-4">
                                                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <p class="mt-4 text-gray-500">Image placeholder</p>
                                            </div>
                                            @if($section->title)
                                                <p class="text-sm text-gray-600 italic">{{ $section->title }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @break

                                @case('content')
                                    <div class="content-section py-12">
                                        <div class="container mx-auto px-6">
                                            @if($section->title)
                                                <h2 class="text-3xl font-bold text-gray-900 mb-6">{{ $section->title }}</h2>
                                            @endif
                                            @if($section->subtitle)
                                                <p class="text-xl text-gray-600 mb-6">{{ $section->subtitle }}</p>
                                            @endif
                                            @if($section->content)
                                                <div class="prose prose-lg max-w-none">
                                                    {!! $section->content !!}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    @break

                                @case('video')
                                    @php
                                        // Get video data
                                        $videoData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                                        if (!is_array($videoData)) {
                                            $videoData = [];
                                        }
                                        $videoUrl = $videoData['video_url'] ?? $section->content ?? '';
                                        $videoPath = $videoData['video_path'] ?? '';
                                        $isUploaded = !empty($videoPath);
                                        
                                        // Check if it's a YouTube or Vimeo URL
                                        $isYouTube = preg_match('/(youtube\.com|youtu\.be)/', $videoUrl);
                                        $isVimeo = preg_match('/vimeo\.com/', $videoUrl);
                                    @endphp
                                    <div class="video-section py-12">
                                        <div class="container mx-auto px-6">
                                            @if($section->title)
                                                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 text-center">{{ $section->title }}</h2>
                                            @endif
                                            
                                            @if($videoUrl)
                                                @if($isYouTube || $isVimeo)
                                                    <!-- YouTube/Vimeo Embed -->
                                                    <div class="aspect-video w-full max-w-4xl mx-auto">
                                                        @if($isYouTube)
                                                            @php
                                                                // Extract YouTube video ID
                                                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $videoUrl, $matches);
                                                                $youtubeId = $matches[1] ?? '';
                                                            @endphp
                                                            <iframe 
                                                                class="w-full h-full rounded-lg"
                                                                src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                                                frameborder="0"
                                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                                allowfullscreen
                                                            ></iframe>
                                                        @elseif($isVimeo)
                                                            @php
                                                                // Extract Vimeo video ID
                                                                preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches);
                                                                $vimeoId = $matches[1] ?? '';
                                                            @endphp
                                                            <iframe 
                                                                class="w-full h-full rounded-lg"
                                                                src="https://player.vimeo.com/video/{{ $vimeoId }}"
                                                                frameborder="0"
                                                                allow="autoplay; fullscreen; picture-in-picture"
                                                                allowfullscreen
                                                            ></iframe>
                                                        @endif
                                                    </div>
                                                @else
                                                    <!-- Direct Video File -->
                                                    <div class="w-full max-w-4xl mx-auto">
                                                        <video 
                                                            controls 
                                                            class="w-full rounded-lg shadow-lg"
                                                            preload="metadata"
                                                        >
                                                            <source src="{{ $isUploaded ? asset('storage/' . $videoPath) : $videoUrl }}" type="video/mp4">
                                                            <source src="{{ $isUploaded ? asset('storage/' . $videoPath) : $videoUrl }}" type="video/webm">
                                                            <source src="{{ $isUploaded ? asset('storage/' . $videoPath) : $videoUrl }}" type="video/ogg">
                                                            Your browser does not support the video tag.
                                                        </video>
                                                    </div>
                                                @endif
                                            @endif
                                            
                                            @if($section->subtitle)
                                                <p class="text-gray-600 mt-4 text-center">{{ $section->subtitle }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    @break

                                @default
                                    {{-- Default content section --}}
                                    <div class="default-section py-8">
                                        <div class="container mx-auto px-6">
                                            @if($section->title)
                                                <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ $section->title }}</h3>
                                            @endif
                                            @if($section->subtitle)
                                                <p class="text-lg text-gray-600 mb-4">{{ $section->subtitle }}</p>
                                            @endif
                                            @if($section->content)
                                                <div class="prose max-w-none">
                                                    {!! $section->content !!}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                            @endswitch
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Fallback content if no sections --}}
                <div class="container mx-auto px-6 py-16">
                    @if($page->content)
                        <div class="prose prose-lg max-w-none">
                            {!! $page->content !!}
                        </div>
                    @else
                        <div class="text-center text-gray-500">
                            <p class="text-lg">This page is currently being updated. Please check back soon.</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Add some basic styling --}}
        <style>
            .cms-page {
                min-height: 50vh;
            }
            
            .container {
                max-width: 1200px;
            }
            
            .prose {
                color: #374151;
                line-height: 1.75;
            }
            
            .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
                color: #111827;
                font-weight: 600;
            }
            
            .prose p {
                margin-bottom: 1.25em;
            }
            
            .prose ul, .prose ol {
                margin: 1.25em 0;
                padding-left: 1.625em;
            }
            
            .prose li {
                margin: 0.5em 0;
            }
            
            .prose a {
                color: #2563eb;
                text-decoration: underline;
            }
            
            .prose a:hover {
                color: #1d4ed8;
            }
        </style>

    @else
        {{-- Page not found --}}
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="text-center">
                <div class="mb-8">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Page Not Found</h1>
                <p class="text-xl text-gray-600 mb-8">The requested page "{{ $slug }}" could not be found.</p>
                <div class="space-x-4">
                    <a href="/" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Go Home
                    </a>
                    <a href="/cms-admin/pages" class="inline-block border border-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-50 transition">
                        Manage Pages
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
{{-- Hero Section Partial --}}
@if($section->type === 'hero' && ($isMeditative || $isFitness))
    {{-- Hero sections don't need the container wrapper --}}
    @if($isMeditative)
        {{-- Meditative Template Hero Slider --}}
        <section class="home-slider js-fullheight owl-carousel">
            <div class="slider-item js-fullheight" style="background-image:url({{ asset('images/bg_1.jpg') }});">
                <div class="overlay"></div>
                <div class="container">
                    <div class="row no-gutters slider-text js-fullheight align-items-center justify-content-center" data-scrollax-parent="true">
                        <div class="col-md-10 text ftco-animate text-center">
                            @if($section->title)
                            <h1 class="mb-4">{{ $section->title }}</h1>
                            @endif
                            @if($section->subtitle)
                            <h3 class="subheading">{{ $section->subtitle }}</h3>
                            @endif
                            @if($section->content)
                            <div class="mt-4">{!! $section->content !!}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @elseif($isFitness)
        {{-- Fitness Template Hero Section with Auto-Playing Carousel --}}
        @php
            $settings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
            $data = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
            
            // Check if we have multiple slides in data
            $slides = $data['slides'] ?? [];
            
            // If no slides in data, create a default slide from section content
            if (empty($slides)) {
                $slides = [[
                    'title' => $section->title,
                    'subtitle' => $section->subtitle,
                    'content' => $section->content,
                    'background_image' => $settings['background_image'] ?? null,
                    'background_color' => $settings['background_color'] ?? 'var(--fitness-primary, #4285F4)',
                ]];
            }
            
            // Height settings
            $height = $settings['height'] ?? ($settings['custom_height'] ?? '500');
            $heightValue = is_numeric($height) ? max(1, intval($height)) : 500;
            $minHeightStyle = 'min-height: ' . $heightValue . 'px;';
            
            // Text color
            $textColor = $settings['text_color'] ?? 'var(--fitness-text-light, #ffffff)';
            
            // Carousel ID
            $carouselId = 'heroCarousel' . $section->id;
        @endphp
        
        @if(count($slides) > 1)
            {{-- Multiple slides - Bootstrap Carousel with Auto-Play --}}
            <div id="{{ $carouselId }}" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000" style="{{ $minHeightStyle }}">
                {{-- Carousel Indicators --}}
                <div class="carousel-indicators">
                    @foreach($slides as $idx => $slide)
                        <button type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide-to="{{ $idx }}" 
                                class="{{ $idx === 0 ? 'active' : '' }}" 
                                aria-current="{{ $idx === 0 ? 'true' : 'false' }}" 
                                aria-label="Slide {{ $idx + 1 }}"></button>
                    @endforeach
                </div>
                
                {{-- Carousel Inner --}}
                <div class="carousel-inner" style="{{ $minHeightStyle }}">
                    @foreach($slides as $idx => $slide)
                        @php
                            $slideBgColor = $slide['background_color'] ?? 'var(--fitness-primary, #4285F4)';
                            $slideBgImage = $slide['background_image'] ?? null;
                            $slideTitle = $slide['title'] ?? '';
                            $slideSubtitle = $slide['subtitle'] ?? '';
                            $slideContent = $slide['content'] ?? '';
                            
                            $slideStyle = '';
                            if ($slideBgImage) {
                                $slideStyle = "background-image: url('{$slideBgImage}'); background-size: cover; background-position: center;";
                            } else {
                                $slideStyle = "background-color: {$slideBgColor};";
                            }
                        @endphp
                        <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}" style="{{ $slideStyle }} {{ $minHeightStyle }}">
                            <div class="carousel-overlay" style="background: rgba(0, 0, 0, 0.3); position: absolute; top: 0; left: 0; right: 0; bottom: 0;"></div>
                            <div class="container position-relative" style="z-index: 1; {{ $minHeightStyle }}">
                                <div class="row align-items-center justify-content-center text-center hero-content-row" style="{{ $minHeightStyle }}">
                                    <div class="col-12 col-md-10 col-lg-10">
                                        @if($slideTitle)
                                        <h1 class="display-2 fw-bold mb-3 mb-md-4 hero-title-custom" style="color: {{ $textColor }};">{{ $slideTitle }}</h1>
                                        @endif
                                        @if($slideSubtitle)
                                        <h3 class="mb-3 mb-md-4 hero-subtitle-custom" style="color: {{ $textColor }};">{{ $slideSubtitle }}</h3>
                                        @endif
                                        @if($slideContent)
                                        <div class="lead mb-4 mb-md-5" style="color: {{ $textColor }};">{!! $slideContent !!}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Carousel Controls --}}
                <button class="carousel-control-prev" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        @else
            {{-- Single slide - Static Hero Section --}}
            @php
                $slide = $slides[0] ?? [
                    'title' => $section->title,
                    'subtitle' => $section->subtitle,
                    'content' => $section->content,
                    'background_image' => $settings['background_image'] ?? null,
                    'background_color' => $settings['background_color'] ?? 'var(--fitness-primary, #4285F4)',
                ];
                
                $bgColor = $slide['background_color'] ?? 'var(--fitness-primary, #4285F4)';
                $bgImage = $slide['background_image'] ?? null;
                
                $backgroundStyle = '';
                if ($bgImage) {
                    $backgroundStyle = "background-image: url('{$bgImage}'); background-size: cover; background-position: center;";
                } else {
            $useGradient = !isset($settings['background_color']) || $settings['background_color'] === '#ff6b6b' || $settings['background_color'] === 'var(--fitness-primary, #ff6b6b)';
            $backgroundStyle = $useGradient 
                        ? 'background: var(--fitness-gradient, linear-gradient(135deg, #4285F4 0%, #e03e2d 100%));' 
                : 'background-color: ' . $bgColor . ';';
                }
        @endphp
        <section class="hero-section-custom" style="{{ $backgroundStyle }} color: {{ $textColor }}; {{ $minHeightStyle }}">
            <div class="container">
                <div class="row align-items-center justify-content-center text-center hero-content-row" style="{{ $minHeightStyle }}">
                    <div class="col-12 col-md-10 col-lg-10">
                            @if($slide['title'] ?? $section->title)
                            <h1 class="display-2 fw-bold mb-3 mb-md-4 hero-title-custom" style="color: {{ $textColor }};">{{ $slide['title'] ?? $section->title }}</h1>
                        @endif
                            @if($slide['subtitle'] ?? $section->subtitle)
                            <h3 class="mb-3 mb-md-4 hero-subtitle-custom" style="color: {{ $textColor }};">{{ $slide['subtitle'] ?? $section->subtitle }}</h3>
                        @endif
                            @if($slide['content'] ?? $section->content)
                            <div class="lead mb-4 mb-md-5" style="color: {{ $textColor }};">{!! $slide['content'] ?? $section->content !!}</div>
                        @endif
                        </div>
                </div>
            </div>
        </section>
        @endif
    @endif
@else
    {{-- Default hero section for other templates --}}
    @php
        $settings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
        $bgColor = $settings['background_color'] ?? '#1f2937';
        $textColor = $settings['text_color'] ?? '#ffffff';
    @endphp
    @if($section->title)
        <h1 class="text-5xl md:text-6xl font-bold mb-6">{{ $section->title }}</h1>
    @endif
    @if($section->subtitle)
        <p class="text-xl md:text-2xl mb-8 opacity-90">{{ $section->subtitle }}</p>
    @endif
    @if($section->content)
        <div class="text-lg mb-8 max-w-3xl mx-auto">{!! $section->content !!}</div>
    @endif
@endif
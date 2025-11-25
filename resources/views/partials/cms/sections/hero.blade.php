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
        {{-- Fitness Template Hero Section --}}
        @php
            $settings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
            $bgColor = $settings['background_color'] ?? '#ff6b6b';
            $textColor = $settings['text_color'] ?? '#ffffff';
            
            // Use gradient as default if no custom background is set
            $useGradient = !isset($settings['background_color']) || $settings['background_color'] === '#ff6b6b';
            $backgroundStyle = $useGradient 
                ? 'background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);' 
                : 'background-color: ' . $bgColor . ';';
        @endphp
        <section class="hero-section-custom" style="{{ $backgroundStyle }} color: {{ $textColor }}; padding: 100px 0;">
            <div class="container">
                <div class="row align-items-center justify-content-center text-center" style="min-height: 600px;">
                    <div class="col-lg-10">
                        @if($section->title)
                        <h1 class="display-2 fw-bold mb-4" style="color: {{ $textColor }};">{{ $section->title }}</h1>
                        @endif
                        @if($section->subtitle)
                        <h3 class="mb-4" style="color: {{ $textColor }};">{{ $section->subtitle }}</h3>
                        @endif
                        @if($section->content)
                        <div class="lead mb-5" style="color: {{ $textColor }};">{!! $section->content !!}</div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
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
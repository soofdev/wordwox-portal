{{-- Contact Form Section Partial --}}
@if($isFitness)
    @php
        $contactSettings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
        $contactTitleFontSize = $contactSettings['title_font_size'] ?? '';
        $contactSubtitleFontSize = $contactSettings['subtitle_font_size'] ?? '';
        $contactTitleStyle = '';
        $contactSubtitleStyle = '';
        if (!empty($contactTitleFontSize)) {
            $numericValue = is_numeric($contactTitleFontSize) ? (float) $contactTitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $contactTitleFontSize, $matches) ? (float) $matches[1] : null);
            if ($numericValue && $numericValue >= 1) {
                $contactTitleStyle = 'font-size: ' . $numericValue . 'px;';
            }
        }
        if (!empty($contactSubtitleFontSize)) {
            $numericValue = is_numeric($contactSubtitleFontSize) ? (float) $contactSubtitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $contactSubtitleFontSize, $matches) ? (float) $matches[1] : null);
            if ($numericValue && $numericValue >= 1) {
                $contactSubtitleStyle = 'font-size: ' . $numericValue . 'px;';
            }
        }
    @endphp
    
    @php
        // Get map URL
        if (!isset($mapUrl)) {
            $contactData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
            if (!is_array($contactData)) {
                $contactData = [];
            }
            $mapUrl = $contactData['map_url'] ?? '';
        }
    @endphp
    
    <div class="container py-4" style="max-width: 1200px;">
        {{-- Title --}}
        @if($section->title)
        <h1 class="h2 mb-4 fw-bold" style="{{ $contactTitleStyle }}">{{ $section->title }}</h1>
        @endif
        
        <div class="row g-4">
            {{-- Left Side: Contact Info + Map --}}
            <div class="col-lg-6">
                {{-- Introductory Text --}}
                @if($section->content)
                <p class="mb-4" style="line-height: 1.6; color: #6c757d;">{{ strip_tags($section->content) }}</p>
                @endif
                
                {{-- Contact Information --}}
                <div class="mb-4">
                    @if($orgContact['email'])
                    <div class="mb-3">
                        <strong style="display: block; margin-bottom: 4px;">Email:</strong>
                        <a href="mailto:{{ $orgContact['email'] }}" class="text-decoration-none" style="color: #007bff;">{{ $orgContact['email'] }}</a>
                    </div>
                    @endif
                    @if($orgContact['phone'])
                    <div class="mb-3">
                        <strong style="display: block; margin-bottom: 4px;">Phone:</strong>
                        <a href="tel:{{ $orgContact['phone'] }}" class="text-decoration-none" style="color: #007bff;">{{ $orgContact['phone'] }}</a>
                    </div>
                    @endif
                </div>
                
                {{-- Google Maps Embed --}}
                @if($mapUrl && str_contains($mapUrl, 'google.com/maps/embed'))
                <div class="mt-4">
                    <iframe 
                        src="{{ $mapUrl }}" 
                        width="100%" 
                        height="450" 
                        style="border:0; border-radius: 4px;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                @elseif($orgContact['address'])
                {{-- Fallback: Show address if no valid map URL --}}
                <div class="mt-4">
                    <strong style="display: block; margin-bottom: 4px;">Address:</strong>
                    <p class="mb-0">{{ $orgContact['address'] }}</p>
                    @if($mapUrl && !str_contains($mapUrl, 'google.com/maps/embed'))
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle"></i> Please update the Google Maps Embed URL in the CMS settings. Current value is not a valid embed URL.
                    </small>
                    @endif
                </div>
                @endif
            </div>
            
            {{-- Right Side: Contact Form --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0" style="border-radius: 8px;">
                    <div class="card-body p-4 p-md-5">
                        @include('partials.cms.sections.contact-form-traditional', ['page' => $page])
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif($isMeditative)
    <section class="ftco-section contact-section ftco-degree-bg">
        <div class="container">
            <div class="row d-flex mb-4 mb-md-5 contact-info">
                <div class="col-12 col-md-12 mb-3 mb-md-4">
                    @if($section->title)
                    <h2 class="h4 h5-md">{{ $section->title }}</h2>
                    @endif
                    @if($section->subtitle)
                    <p class="mb-2 mb-md-3">{{ $section->subtitle }}</p>
                    @endif
                    @if($section->content)
                    <div>{!! $section->content !!}</div>
                    @endif
                </div>
            </div>
            <div class="row block-9">
                <div class="col-12 col-md-6 mb-4 mb-md-0 pr-md-5">
                    {{-- Dynamic Contact Form --}}
                    <div class="bg-light p-4 p-md-5 contact-form">
                        @include('partials.cms.sections.contact-form-traditional', ['page' => $page])
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    {{-- Contact Information --}}
                    @if($showContactInfo && ($orgContact['email'] || $orgContact['phone'] || $orgContact['address']))
                        <div class="contact-info bg-primary p-4 p-md-5 h-100">
                            <h3 class="h4 h5-md text-white mb-3 mb-md-4">Contact Information</h3>
                            @if($orgContact['address'])
                                <p class="text-white-50 mb-2 mb-md-3">
                                    <span class="text-white font-weight-bold">Address:</span><br>
                                    {{ $orgContact['address'] }}
                                </p>
                            @endif
                            @if($orgContact['phone'])
                                <p class="text-white-50 mb-2 mb-md-3">
                                    <span class="text-white font-weight-bold">Phone:</span><br>
                                    <a href="tel:{{ $orgContact['phone'] }}" class="text-white">{{ $orgContact['phone'] }}</a>
                                </p>
                            @endif
                            @if($orgContact['email'])
                                <p class="text-white-50 mb-2 mb-md-3">
                                    <span class="text-white font-weight-bold">Email:</span><br>
                                    <a href="mailto:{{ $orgContact['email'] }}" class="text-white">{{ $orgContact['email'] }}</a>
                                </p>
                            @endif
                        </div>
                    @else
                        <div id="map" style="min-height: 300px; height: 100%;"></div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@else
    {{-- Default Contact Form for Modern Template - Matches Image Layout --}}
    @php
        // Use mapUrl from section-wrapper if available, otherwise get from section data
        if (!isset($mapUrl)) {
            $contactData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
            if (!is_array($contactData)) {
                $contactData = [];
            }
            $mapUrl = $contactData['map_url'] ?? '';
        }
    @endphp
    
    <div class="container py-4">
        <div class="row g-4">
            {{-- Left Side: Contact Info + Map --}}
            <div class="col-lg-6">
                {{-- Title --}}
                @if($section->title)
                <h1 class="h2 mb-3 fw-bold" style="margin-top: 0;">{{ $section->title }}</h1>
                @endif
                
                {{-- Introductory Text --}}
                @if($section->content)
                <p class="mb-4" style="line-height: 1.6; color: #6c757d;">{{ strip_tags($section->content) }}</p>
                @endif
                
                {{-- Contact Information --}}
                <div class="mb-4">
                    @if($orgContact['email'])
                    <div class="mb-3">
                        <strong style="display: block; margin-bottom: 4px;">Email:</strong>
                        <a href="mailto:{{ $orgContact['email'] }}" class="text-decoration-none" style="color: #007bff;">{{ $orgContact['email'] }}</a>
                    </div>
                    @endif
                    @if($orgContact['phone'])
                    <div class="mb-3">
                        <strong style="display: block; margin-bottom: 4px;">Phone:</strong>
                        <a href="tel:{{ $orgContact['phone'] }}" class="text-decoration-none" style="color: #007bff;">{{ $orgContact['phone'] }}</a>
                    </div>
                    @endif
                </div>
                
                {{-- Google Maps Embed --}}
                @if($mapUrl && str_contains($mapUrl, 'google.com/maps/embed'))
                <div class="mt-4">
                    <iframe 
                        src="{{ $mapUrl }}" 
                        width="100%" 
                        height="450" 
                        style="border:0; border-radius: 4px;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                @elseif($orgContact['address'])
                {{-- Fallback: Show address if no valid map URL --}}
                <div class="mt-4">
                    <strong style="display: block; margin-bottom: 4px;">Address:</strong>
                    <p class="mb-0">{{ $orgContact['address'] }}</p>
                    @if($mapUrl && !str_contains($mapUrl, 'google.com/maps/embed'))
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle"></i> Please update the Google Maps Embed URL in the CMS settings. Current value is not a valid embed URL.
                    </small>
                    @endif
                </div>
                @endif
            </div>
            
            {{-- Right Side: Contact Form --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0" style="border-radius: 8px;">
                    <div class="card-body p-4 p-md-5">
                        @include('partials.cms.sections.contact-form-traditional', ['page' => $page])
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
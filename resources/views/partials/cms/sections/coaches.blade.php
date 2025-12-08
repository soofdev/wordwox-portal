{{-- Coaches Section Partial --}}
@if($isFitness)
    @php
        $coachesSettings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
        $cardTitleFontSize = $coachesSettings['card_title_font_size'] ?? '';
        $cardTitleStyle = '';
        if (!empty($cardTitleFontSize)) {
            $numericValue = is_numeric($cardTitleFontSize) ? (float) $cardTitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $cardTitleFontSize, $matches) ? (float) $matches[1] : null);
            if ($numericValue && $numericValue >= 1) {
                $cardTitleStyle = 'font-size: ' . $numericValue . 'px;';
            }
        }
    @endphp
    <div class="container my-4 my-md-5 coaches-section">
        @if($section->title)
        <div class="text-center mb-4 mb-md-5">
            <h2 class="section-heading">{{ $section->title }}</h2>
            @if($section->subtitle)
            <p class="text-muted coaches-subtitle">{{ $section->subtitle }}</p>
            @endif
        </div>
        @endif

        @if($section->content)
        <div class="text-center mb-4 mb-md-5 coaches-content">{!! $section->content !!}</div>
        @endif

        @if(isset($coaches) && $coaches->count() > 0)
            {{-- Grid Layout - Matching SuperHero CrossFit Packages Design --}}
            <div class="row">
                @foreach($coaches as $coach)
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card plan-card">
                            @if($showPhoto)
                                <div class="coach-img-container position-relative overflow-hidden">
                                    @if($coach->profileImageUrl)
                                        <img src="{{ $coach->profileImageUrl }}" 
                                             class="card-img-top coach-img" 
                                             alt="Coach {{ $coach->fullName }}">
                                    @elseif($coach->portraitImageUrl)
                                        <img src="{{ $coach->portraitImageUrl }}" 
                                             class="card-img-top coach-img" 
                                             alt="Coach {{ $coach->fullName }}">
                                    @else
                                        <div class="card-img-top coach-img d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-user-tie coach-placeholder-icon text-muted"></i>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <div class="card-body">
                                <h4 class="card-title">{{ $coach->fullName }}</h4>
                                @if($coach->title || $coach->bio)
                                    <p class="card-text text-overflow">
                                        @if($coach->title)
                                            {{ $coach->title }}
                                        @elseif($coach->bio)
                                            {{ Str::limit(strip_tags($coach->bio), 100) }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                            <div class="card-footer">
                                <a class="btn btn-md btn-block btn-dark" href="{{ route('coach.view', ['id' => $coach->uuid]) }}" target="_blank">
                                    {{ $viewProfileText ?? 'View Profile' }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-light rounded">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No coaches available at this time.</p>
            </div>
        @endif
    </div>

    <style>
        /* Responsive Coaches Section */
        .coaches-section {
            padding: 40px 15px;
            background: var(--fitness-bg-coaches, #f8f9fa);
        }
        
        .coaches-subtitle {
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .coaches-content {
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        /* Ensure the row properly wraps columns */
        .coaches-section .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -0.75rem;
            margin-right: -0.75rem;
        }
        
        .coaches-section .row > [class*="col-"] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        /* Coach Cards - Matching SuperHero CrossFit Packages Design */
        .plan-card {
            height: 350px;
            display: flex;
            flex-direction: column;
            border-radius: 0;
            border: 1px solid rgba(0,0,0,0.125);
            box-shadow: none !important;
        }
        
        .plan-card .coach-img-container {
            height: 200px;
            overflow: hidden;
        }
        
        .plan-card .coach-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .plan-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1rem;
        }
        
        .plan-card .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #212529;
        }
        
        .plan-card .card-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .plan-card .card-body .text-overflow {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .plan-card .card-footer {
            margin-top: auto;
            background-color: #fff;
            border-top: 1px solid rgba(0,0,0,0.125);
            padding: 1rem;
        }
        
        .coach-card {
            transition: transform 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: none !important;
        }
        
        .coach-card:hover {
            transform: none;
            box-shadow: none !important;
        }
        
        .coach-img-container {
            height: 250px;
        }
        
        .coach-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .coach-card .card-body {
            padding: 1.25rem;
        }
        
        .coach-card .card-title {
            font-size: 1rem;
            line-height: 1.4;
        }
        
        .coach-overlay {
            display: none;
        }
        
        .coach-card:hover .coach-overlay {
            display: none;
        }
        
        .coach-card:hover .coach-img {
            transform: none;
        }
        
        .social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .social-link:hover {
            background: white;
            color: #333;
            transform: scale(1.1);
        }
        
        .coach-certifications {
            margin-top: 15px;
        }
        
        .coach-certifications .badge {
            font-size: 0.75rem;
        }
        
        .coach-placeholder-icon {
            font-size: 3rem;
        }
        
        /* View Profile Button - Use button colors from database */
        .plan-card .btn-dark {
            background-color: var(--fitness-button-bg, #4285F4) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-text, #ffffff) !important;
            font-size: 0.9rem;
            padding: 0.625rem 1.25rem;
            transition: all 0.3s ease;
        }
        
        .plan-card .btn-dark:hover:not(:disabled) {
            background: var(--fitness-primary-light) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-bg, #4285F4) !important;
            transform: translateY(-1px);
        }
        
        .view-profile-btn,
        .view-profile-btn.btn-primary,
        .view-profile-btn.btn-primary.btn-sm {
            font-size: 0.9rem;
            padding: 0.625rem 1.25rem;
            background: var(--fitness-primary, #4285F4) !important;
            border: 2px solid var(--fitness-primary, #4285F4) !important;
            color: var(--fitness-text-light, #ffffff) !important;
            box-shadow: none !important;
        }
        
        .view-profile-btn:hover,
        .view-profile-btn.btn-primary:hover,
        .view-profile-btn.btn-primary.btn-sm:hover,
        .view-profile-btn:focus,
        .view-profile-btn:active {
            background: var(--fitness-primary-light) !important;
            border-color: var(--fitness-primary, #4285F4) !important;
            color: var(--fitness-primary, #4285F4) !important;
            box-shadow: none !important;
        }
        
        /* Responsive adjustments */
        @media (min-width: 576px) {
            .coaches-section {
                padding: 50px 20px;
            }
            .coaches-subtitle {
                font-size: 1rem;
            }
            .coaches-content {
                font-size: 1rem;
            }
            .coach-img-container {
                height: 280px;
            }
            .coach-card .card-title {
                font-size: 1.1rem;
            }
        }
        
        @media (min-width: 768px) {
            .coaches-section {
                padding: 60px 25px;
            }
            .coaches-subtitle {
                font-size: 1.125rem;
            }
            .coaches-content {
                font-size: 1.125rem;
            }
            .coach-img-container {
                height: 300px;
            }
            .coach-card .card-body {
                padding: 1.5rem;
            }
            .coach-card .card-title {
                font-size: 1.25rem;
            }
            .coach-placeholder-icon {
                font-size: 4rem;
            }
        }
        
        @media (min-width: 992px) {
            .coaches-section {
                padding: 80px 29px;
            }
        }
        
        /* Empty state responsive */
        .coaches-section .text-center.py-12 {
            padding: 3rem 1rem;
        }
        
        @media (min-width: 768px) {
            .coaches-section .text-center.py-12 {
                padding: 4rem 2rem;
            }
        }
    </style>

@elseif($isMeditative)
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center pb-5 mb-3">
                <div class="col-md-7 heading-section text-center ftco-animate">
                    @if($section->title)
                    <h2>{{ $section->title }}</h2>
                    @endif
                    @if($section->subtitle)
                    <span class="subheading">{{ $section->subtitle }}</span>
                    @endif
                    @if($section->content)
                    <div class="mt-3">{!! $section->content !!}</div>
                    @endif
                </div>
            </div>
            @if(isset($coaches) && $coaches->count() > 0)
                @php
                    // Calculate Bootstrap column classes based on columns setting
                    $columnsValue = $columns ?? 3;
                    if (is_string($columnsValue)) {
                        $columnsValue = (int)$columnsValue;
                    }
                    $columnsInt = (int)$columnsValue;
                    $meditativeCols = match($columnsInt) {
                        2 => 'col-12 col-md-6',
                        4 => 'col-12 col-md-6 col-lg-3',
                        default => 'col-12 col-md-6 col-lg-4' // 3 columns default
                    };
                @endphp
                <div class="row">
                    @foreach($coaches as $coach)
                        <div class="{{ $meditativeCols }} d-flex mb-sm-4 ftco-animate">
                            <div class="staff">
                                @if($showPhoto)
                                    @if($coach->profileImageUrl)
                                        <div class="img mb-4" style="background-image: url({{ $coach->profileImageUrl }});"></div>
                                    @elseif($coach->portraitImageUrl)
                                        <div class="img mb-4" style="background-image: url({{ $coach->portraitImageUrl }});"></div>
                                    @else
                                        <div class="img mb-4 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-user-tie fa-3x text-muted"></i>
                                        </div>
                                    @endif
                                @endif
                                <div class="info text-center">
                                    <h3 style="{{ $cardTitleStyle }}">{{ $coach->fullName }}</h3>
                                    <a href="{{ route('coach.view', ['id' => $coach->uuid]) }}" class="btn btn-primary btn-sm mt-3" target="_blank">
                                        {{ $viewProfileText ?? 'View Profile' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="row">
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No coaches available at this time.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>
@else
    {{-- Default Coaches for Modern Template --}}
    <div class="max-w-7xl mx-auto">
        @if($section->title)
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold mb-4">{{ $section->title }}</h2>
            @if($section->subtitle)
            <p class="text-xl text-gray-600">{{ $section->subtitle }}</p>
            @endif
        </div>
        @endif

        @if($section->content)
        <div class="text-center mb-12">{!! $section->content !!}</div>
        @endif

        @if(isset($coaches) && $coaches->count() > 0)
            @php
                // Get layout setting (default to grid) 
                $layoutMode = $layout ?? 'grid';
                
                // Calculate grid columns for both list and grid (they use the same layout)
                    $gridCols = match((int)($columns ?? 3)) {
                        2 => 'md:grid-cols-2',
                        4 => 'md:grid-cols-4',
                        default => 'md:grid-cols-3'
                    };
            @endphp
            {{-- Both list and grid use the same simple layout with grid columns from settings --}}
                <div class="grid grid-cols-1 {{ $gridCols }} gap-8">
                @foreach($coaches as $coach)
                    <div class="bg-white rounded-lg overflow-hidden transition-shadow text-center">
                        @if($showPhoto)
                            @if($coach->profileImageUrl)
                                <img src="{{ $coach->profileImageUrl }}" alt="Coach {{ $coach->fullName }}" class="w-full h-64 object-cover" height="256">
                            @elseif($coach->portraitImageUrl)
                                <img src="{{ $coach->portraitImageUrl }}" alt="Coach {{ $coach->fullName }}" class="w-full h-64 object-cover" height="256">
                            @else
                                <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-user-tie text-4xl text-gray-400"></i>
                                </div>
                            @endif
                        @endif
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-3" style="{{ $cardTitleStyle }}">{{ $coach->fullName }}</h3>
                            <a href="{{ route('coach.view', ['id' => $coach->uuid]) }}" 
                               class="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm"
                               target="_blank">
                                {{ $viewProfileText ?? 'View Profile' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 rounded-lg">
                <i class="fas fa-users text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">No coaches available at this time.</p>
            </div>
        @endif
    </div>
@endif
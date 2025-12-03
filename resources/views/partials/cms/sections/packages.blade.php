{{-- Packages Section Partial --}}
@if($isFitness)
    @php
        $packagesSettings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
        $cardTitleFontSize = $packagesSettings['card_title_font_size'] ?? '';
        $cardTitleStyle = '';
        if (!empty($cardTitleFontSize)) {
            $numericValue = is_numeric($cardTitleFontSize) ? (float) $cardTitleFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $cardTitleFontSize, $matches) ? (float) $matches[1] : null);
            if ($numericValue && $numericValue >= 1) {
                $cardTitleStyle = 'font-size: ' . $numericValue . 'px;';
            }
        }
    @endphp
    <div class="container my-4 my-md-5 packages-section">
        @if($section->title)
        <div class="text-center mb-4 mb-md-5">
            <h2 class="section-heading">{{ $section->title }}</h2>
            @if($section->subtitle)
            <p class="text-muted packages-subtitle">{{ $section->subtitle }}</p>
            @endif
        </div>
        @endif

        @if($section->content)
        <div class="text-center mb-4 mb-md-5 packages-content">{!! $section->content !!}</div>
        @endif

        @if(isset($plans) && $plans->count() > 0)
            @php
                // Get layout setting from section data (default to grid)
                $layoutMode = $layout ?? 'grid';
                
                // Calculate Bootstrap 5 column class based on columns setting (only for grid layout)
                // Mobile: 1 column, Tablet: 2 columns, Desktop: based on setting
                if ($layoutMode === 'grid') {
                    $fitnessBootstrapCols = match((int)($columns ?? 3)) {
                        2 => 'col-12 col-sm-6 col-md-6 col-lg-6',
                        4 => 'col-12 col-sm-6 col-md-4 col-lg-3',
                        default => 'col-12 col-sm-6 col-md-6 col-lg-4' // 3 columns default
                    };
                }
                
                // Use theme colors with fallbacks
                $colors = [
                    'var(--fitness-primary, #ff6b6b)', 
                    'var(--fitness-secondary, #4ecdc4)', 
                    '#45b7d1', 
                    '#96ceb4', 
                    '#ffeaa7', 
                    '#dda0dd'
                ];
                $icons = ['dumbbell', 'fire', 'crown', 'medal', 'star', 'trophy'];
            @endphp
            @if($layoutMode === 'list')
                {{-- List Layout --}}
                <div class="row g-3 g-md-4">
                    @foreach($plans as $index => $plan)
                        @php
                            $color = $colors[$index % count($colors)];
                            $isPopular = $index === 1;
                        @endphp
                        <div class="col-12">
                            <div class="card h-100 border-0 package-card-list" style="border-left: 4px solid {{ $color }};">
                                <div class="row g-0">
                                    <div class="col-12 col-md-4 d-flex align-items-center justify-content-center p-3 p-md-4 package-price-col" style="background: linear-gradient(135deg, {{ $color }}20, {{ $color }}10);">
                                        <div class="text-center">
                                            <div class="price mb-2">
                                                <span class="package-price-display">${{ number_format($plan->price, 2) }}</span>
                                            </div>
                                            <span class="text-muted package-duration">/{{ $plan->duration_text }}</span>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-8">
                                        <div class="card-body p-3 p-md-4">
                                            <div class="d-flex justify-content-between align-items-start mb-2 mb-md-3">
                                                <h4 class="card-title package-name" style="{{ $cardTitleStyle }}">{{ $plan->name }}</h4>
                                                @if($showPrograms)
                                                    <span class="badge bg-primary package-badge">{{ $plan->type_label ?? 'Standard Plan' }}</span>
                                                @endif
                                            </div>
                                            @if($showDescription && $plan->description)
                                                <p class="card-text mb-2 mb-md-3 package-description">{{ Str::limit($plan->description, 150) }}</p>
                                            @endif
                                            @php
                                                $canSellOnline = true;
                                                $planUuid = $plan->uuid ?? $plan->id;
                                            @endphp
                                            @if($canSellOnline)
                                                <a href="/org-plan/index?plan={{ $planUuid }}" class="btn btn-outline-primary package-btn">
                                                    {{ $buyButtonText ?? 'Buy' }} <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-outline-secondary package-btn" disabled>
                                                    {{ $purchaseAtGymText ?? 'Purchase at the Gym' }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Grid Layout --}}
                <div class="row g-3 g-md-4">
                    @foreach($plans as $index => $plan)
                        @php
                            $color = $colors[$index % count($colors)];
                            $icon = $icons[$index % count($icons)];
                            $isPopular = $index === 1; // Make second plan popular
                        @endphp
                        <div class="{{ $fitnessBootstrapCols }}">
                            <div class="card h-100 border-0 package-card-grid" style="border-top: 4px solid {{ $color }};">
                                <div class="card-body text-center p-4 p-md-5">
                                    <h4 class="card-title mb-2 mb-md-3 package-name" style="{{ $cardTitleStyle }}">{{ $plan->name }}</h4>
                                    <div class="price mb-3 mb-md-4">
                                        <span class="package-price-display">${{ number_format($plan->price, 2) }}</span>
                                        <span class="text-muted package-duration">/{{ $plan->duration_text }}</span>
                                </div>
                                @if($showDescription && $plan->description)
                                        <p class="card-text mb-3 mb-md-4 package-description">{{ Str::limit($plan->description, 120) }}</p>
                                @endif
                                @if($showPrograms)
                                        <div class="mb-2 mb-md-3">
                                            <span class="badge bg-primary package-badge">{{ $plan->type_label ?? 'Standard Plan' }}</span>
                                    </div>
                                @endif
                                @php
                                    $canSellOnline = true;
                                    $planUuid = $plan->uuid ?? $plan->id;
                                @endphp
                                @if($canSellOnline)
                                        <a href="/org-plan/index?plan={{ $planUuid }}" class="btn btn-lg w-100 package-btn">
                                        {{ $buyButtonText ?? 'Buy' }} <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                @else
                                        <button class="btn btn-outline-secondary btn-lg w-100 package-btn" disabled>
                                        {{ $purchaseAtGymText ?? 'Purchase at the Gym' }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @else
            {{-- No Plans Available --}}
            <div class="text-center py-4 py-md-5">
                <div class="card card-fitness">
                    <div class="card-body p-4 p-md-5">
                        <i class="fas fa-dumbbell packages-empty-icon text-muted mb-3"></i>
                        <h4 class="fw-bold text-muted packages-empty-title">No packages available at this time.</h4>
                        <p class="text-muted packages-empty-text">Check back soon for our fitness packages!</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        /* Responsive Packages Section */
        .packages-section {
            background-color: var(--fitness-bg-packages, #f2f4f6);
            padding: 40px 15px !important;
        }
        
        .packages-subtitle {
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .packages-content {
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        /* Package Cards */
        .package-card-grid,
        .package-card-list {
            border-radius: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .package-card-grid:hover,
        .package-card-list:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        }
        
        .package-name {
            font-size: 1.1rem;
            line-height: 1.3;
        }
        
        .package-price-display {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .package-duration {
            font-size: 0.9rem;
        }
        
        .package-description {
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .package-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        
        .package-btn {
            font-size: 0.9rem;
            padding: 0.625rem 1.25rem;
            background: var(--fitness-primary, #ff6b6b) !important;
            border: none !important;
            color: var(--fitness-text-light, white) !important; /* White text like view-profile-btn */
            box-shadow: none !important;
        }
        
        .package-btn:hover:not(:disabled) {
            background: var(--fitness-primary-light) !important;
            border-color: var(--fitness-primary, #ff6b6b) !important;
            color: var(--fitness-primary, #ff6b6b) !important;
            box-shadow: none !important;
        }
        
        .package-btn:disabled {
            background: var(--fitness-primary, #ff6b6b) !important;
            color: var(--fitness-text-light, white) !important;
            opacity: 0.7;
            cursor: not-allowed;
            box-shadow: none !important;
        }
        
        .package-price-col {
            min-height: 120px;
        }
        
        /* Empty State */
        .packages-empty-icon {
            font-size: 3rem;
        }
        
        .packages-empty-title {
            font-size: 1.25rem;
        }
        
        .packages-empty-text {
            font-size: 0.95rem;
        }
        
        /* Responsive adjustments */
        @media (min-width: 576px) {
            .packages-section {
                padding: 50px 20px !important;
            }
            .packages-subtitle {
                font-size: 1rem;
            }
            .packages-content {
                font-size: 1rem;
            }
            .package-name {
                font-size: 1.25rem;
            }
            .package-price-display {
                font-size: 1.5rem;
            }
            .package-description {
                font-size: 0.95rem;
            }
            .package-btn {
                font-size: 0.95rem;
            }
        }
        
        @media (min-width: 768px) {
            .packages-section {
                padding: 60px 25px !important;
            }
            .packages-subtitle {
                font-size: 1.125rem;
            }
            .packages-content {
                font-size: 1.125rem;
            }
            .package-name {
                font-size: 1.5rem;
            }
            .package-price-display {
                font-size: 1.5rem;
            }
            .package-duration {
                font-size: 1rem;
            }
            .package-description {
                font-size: 1rem;
            }
            .package-badge {
                font-size: 0.875rem;
            }
            .package-btn {
                font-size: 1rem;
            }
            .package-price-col {
                min-height: 150px;
            }
            .packages-empty-icon {
                font-size: 4rem;
            }
            .packages-empty-title {
                font-size: 1.5rem;
            }
            .packages-empty-text {
                font-size: 1rem;
            }
        }
        
        @media (min-width: 992px) {
            .packages-section {
                padding: 80px 29px !important;
            }
            .package-price-display {
                font-size: 1.5rem;
            }
        }
        
        /* List layout responsive improvements */
        @media (max-width: 767px) {
            .package-card-list .row {
                flex-direction: column;
            }
            .package-price-col {
                min-height: 100px;
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
            @if(isset($plans) && $plans->count() > 0)
                <div class="row">
                    @foreach($plans as $plan)
                        <div class="col-md-6 col-lg-4">
                            <div class="block-7">
                                <div class="img" style="background-image: url({{ asset('images/pricing-' . ($loop->index % 3 + 1) . '.jpg') }});"></div>
                                <div class="text-center p-4">
                                    <span class="price"><sup>${{ $plan->currency === 'USD' ? '' : $plan->currency }}</sup> <span class="number">{{ number_format($plan->price, 0) }}</span> <sub>/{{ Str::limit($plan->duration_text, 3) }}</sub></span>
                                    <span class="excerpt d-block">{{ $plan->type_label }}</span>
                                    <h3 class="heading mb-3"><a href="/org-plan/index?plan={{ $plan->uuid ?? $plan->id }}">{{ $plan->name }}</a></h3>
                                    @if($showDescription && $plan->description)
                                        <p>{{ Str::limit($plan->description, 100) }}</p>
                                    @else
                                        <p>{{ $plan->type_label }} membership with flexible scheduling and great value.</p>
                                    @endif
                                    <a href="/org-plan/index?plan={{ $plan->uuid ?? $plan->id }}" class="btn btn-primary d-block px-2 py-3">{{ $buyButtonText ?? 'Get Started' }}</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-gray-600">No packages available at this time.</p>
                </div>
            @endif
        </div>
    </section>
@else
    {{-- Default Packages for Modern Template --}}
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

        @if(isset($plans) && $plans->count() > 0)
            @php
                // Get layout setting (default to grid)
                $layoutMode = $layout ?? 'grid';
                
                if ($layoutMode === 'grid') {
                    $gridCols = match((int)($columns ?? 3)) {
                        2 => 'md:grid-cols-2',
                        4 => 'md:grid-cols-4',
                        default => 'md:grid-cols-3'
                    };
                }
            @endphp
            @if($layoutMode === 'list')
                {{-- List Layout --}}
                <div class="space-y-6">
            @else
                {{-- Grid Layout --}}
                <div class="grid grid-cols-1 {{ $gridCols }} gap-8">
            @endif
                @foreach($plans as $index => $plan)
                    @php
                        $isPopular = $index === 1; // Make second plan popular
                        $planUuid = $plan->uuid ?? $plan->id;
                        $canSellOnline = true;
                    @endphp
                    <div class="bg-white rounded-lg p-8 border-2 {{ $isPopular ? 'border-indigo-500 transform scale-105' : 'border-gray-200 hover:border-indigo-500' }} transition-colors {{ $isPopular ? 'relative' : '' }}">
                        @if($isPopular)
                            <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                                <span class="bg-indigo-500 text-white px-4 py-2 rounded-full text-sm font-semibold">POPULAR</span>
                            </div>
                        @endif
                        <div class="text-center">
                            <h3 class="text-2xl font-bold mb-4">{{ $plan->name }}</h3>
                            <div class="mb-6">
                                <span class="text-5xl font-bold">${{ number_format($plan->price, 2) }}</span>
                                <span class="text-gray-600">/{{ $plan->duration_text }}</span>
                            </div>
                            @if($showDescription && $plan->description)
                                <p class="text-gray-700 mb-4 text-sm">{{ Str::limit($plan->description, 150) }}</p>
                            @endif
                            @if($showPrograms)
                                <div class="mb-4">
                                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $plan->type_label }}
                                    </span>
                                </div>
                            @endif
                            @if($canSellOnline)
                                <a href="/org-plan/index?plan={{ $planUuid }}" 
                                   class="w-full {{ $isPopular ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' }} py-3 px-6 rounded-lg transition-colors inline-block text-center">
                                    {{ $buyButtonText ?? 'Choose Plan' }}
                                </a>
                            @else
                                <button class="w-full bg-gray-200 text-gray-700 py-3 px-6 rounded-lg cursor-not-allowed">
                                    {{ $purchaseAtGymText ?? 'Purchase at the Gym' }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 bg-gray-50 rounded-lg">
                <p class="text-gray-600">No packages available at this time.</p>
            </div>
        @endif
    </div>
@endif
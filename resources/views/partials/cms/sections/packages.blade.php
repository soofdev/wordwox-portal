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
                
                // Use SuperHero CrossFit grid layout: col-lg-4 col-md-6 mb-4 (3 columns on large, 2 on medium)
                // This matches the exact layout from https://superhero.wodworx.com/org-plan/index
                if ($layoutMode === 'grid') {
                    // Always use SuperHero CrossFit layout regardless of columns setting
                    $fitnessBootstrapCols = 'col-lg-4 col-md-6 mb-4';
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
                                                // Check if user has active membership for this plan
                                                $hasActivePlan = isset($userActivePlans[$plan->id]);
                                                $planStatus = $userActivePlans[$plan->id] ?? null;
                                            @endphp
                                            @if($canSellOnline)
                                                @if($hasActivePlan)
                                                    @if($planStatus == \App\Models\OrgUserPlan::STATUS_ACTIVE)
                                                        <button class="btn btn-success package-btn" disabled>
                                                            <i class="fas fa-check-circle me-1"></i>Active
                                                        </button>
                                                    @elseif($planStatus == \App\Models\OrgUserPlan::STATUS_UPCOMING)
                                                        <button class="btn btn-info package-btn" disabled>
                                                            <i class="fas fa-clock me-1"></i>Upcoming
                                                        </button>
                                                    @else
                                                        <button class="btn btn-warning package-btn" disabled>
                                                            <i class="fas fa-hourglass-half me-1"></i>Pending
                                                        </button>
                                                    @endif
                                                @else
                                                    <a href="/org-plan/index?plan={{ $planUuid }}" class="btn btn-outline-primary package-btn">
                                                        {{ $buyButtonText ?? 'Buy' }} <i class="fas fa-arrow-right ms-1"></i>
                                                    </a>
                                                @endif
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
                {{-- Grid Layout - Matching SuperHero CrossFit Design --}}
                <div class="row">
                    @foreach($plans as $index => $plan)
                        @php
                            $canSellOnline = true;
                            $planUuid = $plan->uuid ?? $plan->id;
                            $currency = $plan->currency ?? 'USD';
                            $price = number_format($plan->price, 2);
                            // Format duration like SuperHero: "1 Months" instead of "/1 Month"
                            $durationText = $plan->duration_text ?? ($plan->cycleDuration . ' ' . ($plan->cycleDuration > 1 ? $plan->cycleUnit . 's' : $plan->cycleUnit));
                            // Check if user has active membership for this plan
                            $hasActivePlan = isset($userActivePlans[$plan->id]);
                            $planStatus = $userActivePlans[$plan->id] ?? null;
                        @endphp
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card plan-card">
                                <div class="card-body">
                                    <h4 class="card-title">{{ $plan->name }}</h4>
                                    <p class="card-text text-overflow">{{ $durationText }}</p>
                                    
                                    @if($showPrograms)
                                        <p class="card-text">
                                            <label>Classes included:</label>
                                            @php
                                                // Get programs/classes for this plan
                                                // For now, show plan type as badge, but structure is ready for program badges
                                                $programs = []; // TODO: Load actual programs when relationship is available
                                            @endphp
                                            @if(count($programs) > 0)
                                                @foreach($programs->take(9) as $program)
                                                    <span class="badge" style="color: {{ $program->textColor ?? '#000000' }}; background-color: {{ $program->color ?? '#f0f0f0' }};">
                                                        {{ $program->name }}
                                                    </span>
                                                @endforeach
                                                @if($programs->count() > 9)
                                                    <a class="modal-link" href="#" data-bs-toggle="modal" data-bs-target="#programsModal{{ $plan->id }}">...more</a>
                                                @endif
                                            @else
                                                {{-- Fallback: Show plan type badge --}}
                                                <span class="badge bg-secondary">{{ $plan->type_label ?? 'Standard Plan' }}</span>
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div class="card-footer">
                                    <h4 class="text-center mb-4">
                                        <span class="currency">{{ $currency }}</span> {{ $price }}
                                    </h4>
                                    @if($canSellOnline)
                                        @if($hasActivePlan)
                                            @if($planStatus == \App\Models\OrgUserPlan::STATUS_ACTIVE)
                                                <button class="btn btn-md btn-block btn-success" disabled>
                                                    <i class="fas fa-check-circle me-1"></i>Active
                                                </button>
                                            @elseif($planStatus == \App\Models\OrgUserPlan::STATUS_UPCOMING)
                                                <button class="btn btn-md btn-block btn-info" disabled>
                                                    <i class="fas fa-clock me-1"></i>Upcoming
                                                </button>
                                            @else
                                                <button class="btn btn-md btn-block btn-warning" disabled>
                                                    <i class="fas fa-hourglass-half me-1"></i>Pending
                                                </button>
                                            @endif
                                        @else
                                            <a class="btn btn-md btn-block btn-dark" href="/org-plan/index?plan={{ $planUuid }}">
                                                {{ $buyButtonText ?? 'Buy' }}
                                            </a>
                                        @endif
                                    @else
                                        <button class="btn btn-md btn-block btn-dark" disabled>
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
        
        /* Package Cards - Matching SuperHero CrossFit Design */
        .plan-card {
            height: 350px;
            display: flex;
            flex-direction: column;
            border-radius: 0;
            border: 1px solid rgba(0,0,0,0.125);
            box-shadow: none !important;
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
        
        .plan-card .card-text label {
            font-weight: 500;
            color: #212529;
            margin-right: 0.5rem;
        }
        
        .plan-card .card-text .badge {
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            display: inline-block;
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
        
        .plan-card .card-footer h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 1rem;
        }
        
        .plan-card .card-footer .currency {
            font-weight: 400;
            margin-right: 0.25rem;
        }
        
        .package-card-grid,
        .package-card-list {
            border-radius: 10px;
            box-shadow: none !important;
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
        
        /* Buy Button - Matching SuperHero CrossFit Dark Button */
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
        }
        
        .package-btn {
            font-size: 0.9rem;
            padding: 0.625rem 1.25rem;
            background: var(--fitness-button-bg, #4285F4) !important;
            border: 2px solid var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-text, #ffffff) !important;
            box-shadow: none !important;
        }
        
        .package-btn:hover:not(:disabled) {
            background: var(--fitness-primary-light) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-bg, #4285F4) !important;
            box-shadow: none !important;
        }
        
        .package-btn:disabled {
            background: var(--fitness-button-bg, #4285F4) !important;
            border-color: var(--fitness-button-bg, #4285F4) !important;
            color: var(--fitness-button-text, #ffffff) !important;
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
                                    @php
                                        // Check if user has active membership for this plan
                                        $hasActivePlan = isset($userActivePlans[$plan->id]);
                                        $planStatus = $userActivePlans[$plan->id] ?? null;
                                    @endphp
                                    @if($hasActivePlan)
                                        @if($planStatus == \App\Models\OrgUserPlan::STATUS_ACTIVE)
                                            <button class="btn btn-success d-block px-2 py-3" disabled>
                                                <i class="fas fa-check-circle me-1"></i>Active
                                            </button>
                                        @elseif($planStatus == \App\Models\OrgUserPlan::STATUS_UPCOMING)
                                            <button class="btn btn-info d-block px-2 py-3" disabled>
                                                <i class="fas fa-clock me-1"></i>Upcoming
                                            </button>
                                        @else
                                            <button class="btn btn-warning d-block px-2 py-3" disabled>
                                                <i class="fas fa-hourglass-half me-1"></i>Pending
                                            </button>
                                        @endif
                                    @else
                                        <a href="/org-plan/index?plan={{ $plan->uuid ?? $plan->id }}" class="btn btn-primary d-block px-2 py-3">{{ $buyButtonText ?? 'Get Started' }}</a>
                                    @endif
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
                            @php
                                // Check if user has active membership for this plan
                                $hasActivePlan = isset($userActivePlans[$plan->id]);
                                $planStatus = $userActivePlans[$plan->id] ?? null;
                            @endphp
                            @if($canSellOnline)
                                @if($hasActivePlan)
                                    @if($planStatus == \App\Models\OrgUserPlan::STATUS_ACTIVE)
                                        <button class="w-full bg-green-500 text-white py-3 px-6 rounded-lg cursor-not-allowed" disabled>
                                            <i class="fas fa-check-circle me-1"></i>Active
                                        </button>
                                    @elseif($planStatus == \App\Models\OrgUserPlan::STATUS_UPCOMING)
                                        <button class="w-full bg-blue-500 text-white py-3 px-6 rounded-lg cursor-not-allowed" disabled>
                                            <i class="fas fa-clock me-1"></i>Upcoming
                                        </button>
                                    @else
                                        <button class="w-full bg-yellow-500 text-white py-3 px-6 rounded-lg cursor-not-allowed" disabled>
                                            <i class="fas fa-hourglass-half me-1"></i>Pending
                                        </button>
                                    @endif
                                @else
                                    <a href="/org-plan/index?plan={{ $planUuid }}" 
                                       class="w-full {{ $isPopular ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' }} py-3 px-6 rounded-lg transition-colors inline-block text-center">
                                        {{ $buyButtonText ?? 'Choose Plan' }}
                                    </a>
                                @endif
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
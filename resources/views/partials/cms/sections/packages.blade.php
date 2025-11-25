{{-- Packages Section Partial --}}
@if($isFitness)
    <div class="container my-5">
        @if($section->title)
        <div class="text-center mb-5">
            <h2 class="section-heading">{{ $section->title }}</h2>
            @if($section->subtitle)
            <p class="text-muted">{{ $section->subtitle }}</p>
            @endif
        </div>
        @endif

        @if($section->content)
        <div class="text-center mb-5">{!! $section->content !!}</div>
        @endif

        @if(isset($plans) && $plans->count() > 0)
            @php
                // Get layout setting from section data (default to grid)
                $layoutMode = $layout ?? 'grid';
                
                // Calculate Bootstrap 5 column class based on columns setting (only for grid layout)
                if ($layoutMode === 'grid') {
                    $fitnessBootstrapCols = match((int)($columns ?? 3)) {
                        2 => 'col-lg-6 col-md-6',
                        4 => 'col-lg-3 col-md-6',
                        default => 'col-lg-4 col-md-6' // 3 columns default
                    };
                }
                
                $colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#ffeaa7', '#dda0dd'];
                $icons = ['dumbbell', 'fire', 'crown', 'medal', 'star', 'trophy'];
            @endphp
            @if($layoutMode === 'list')
                {{-- List Layout --}}
                <div class="row g-4">
                    @foreach($plans as $index => $plan)
                        @php
                            $color = $colors[$index % count($colors)];
                            $isPopular = $index === 1;
                        @endphp
                        <div class="col-12">
                            <div class="card h-100 shadow-lg border-0" style="border-left: 4px solid {{ $color }};">
                                <div class="row g-0">
                                    <div class="col-md-4 d-flex align-items-center justify-content-center p-4" style="background: linear-gradient(135deg, {{ $color }}20, {{ $color }}10);">
                                        <div class="text-center">
                                            <div class="price mb-2">
                                                <span class="display-4">${{ number_format($plan->price, 2) }}</span>
                                            </div>
                                            <span class="text-muted">/{{ $plan->duration_text }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h4 class="card-title">{{ $plan->name }}</h4>
                                                @if($showPrograms)
                                                    <span class="badge bg-primary">{{ $plan->type_label ?? 'Standard Plan' }}</span>
                                                @endif
                                            </div>
                                            @if($showDescription && $plan->description)
                                                <p class="card-text mb-3">{{ Str::limit($plan->description, 150) }}</p>
                                            @endif
                                            @php
                                                $canSellOnline = true;
                                                $planUuid = $plan->uuid ?? $plan->id;
                                            @endphp
                                            @if($canSellOnline)
                                                <a href="/org-plan/index?plan={{ $planUuid }}" class="btn btn-outline-primary">
                                                    {{ $buyButtonText ?? 'Buy' }} <i class="fas fa-arrow-right ms-1"></i>
                                                </a>
                                            @else
                                                <button class="btn btn-outline-secondary" disabled>
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
                <div class="row g-4">
                    @foreach($plans as $index => $plan)
                        @php
                            $color = $colors[$index % count($colors)];
                            $icon = $icons[$index % count($icons)];
                            $isPopular = $index === 1; // Make second plan popular
                        @endphp
                        <div class="{{ $fitnessBootstrapCols }}">
                            <div class="card h-100 shadow-lg border-0" style="border-top: 4px solid {{ $color }};">
                                <div class="card-body text-center p-5">
                                <h4 class="card-title mb-3">{{ $plan->name }}</h4>
                                <div class="price mb-4">
                                    <span class="display-4">${{ number_format($plan->price, 2) }}</span>
                                    <span class="text-muted">/{{ $plan->duration_text }}</span>
                                </div>
                                @if($showDescription && $plan->description)
                                    <p class="card-text mb-4">{{ Str::limit($plan->description, 120) }}</p>
                                @endif
                                @if($showPrograms)
                                    <div class="mb-3">
                                        <span class="badge bg-primary">{{ $plan->type_label ?? 'Standard Plan' }}</span>
                                    </div>
                                @endif
                                @php
                                    $canSellOnline = true;
                                    $planUuid = $plan->uuid ?? $plan->id;
                                @endphp
                                @if($canSellOnline)
                                    <a href="/org-plan/index?plan={{ $planUuid }}" class="btn {{ $isPopular ? 'btn-lg' : 'btn-outline-primary btn-lg' }} w-100" {{ $isPopular ? 'style=background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%); border: none; color: white;' : '' }}>
                                        {{ $buyButtonText ?? 'Buy' }} <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                @else
                                    <button class="btn btn-outline-secondary btn-lg w-100" disabled>
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
            <div class="text-center py-5">
                <div class="card card-fitness">
                    <div class="card-body p-5">
                        <i class="fas fa-dumbbell fs-1 text-muted mb-3"></i>
                        <h4 class="fw-bold text-muted">No packages available at this time.</h4>
                        <p class="text-muted">Check back soon for our fitness packages!</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
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
                    <div class="bg-white rounded-lg shadow-lg p-8 border-2 {{ $isPopular ? 'border-indigo-500 transform scale-105' : 'border-gray-200 hover:border-indigo-500' }} transition-colors {{ $isPopular ? 'relative' : '' }}">
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
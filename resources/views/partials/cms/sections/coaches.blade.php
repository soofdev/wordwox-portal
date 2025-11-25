{{-- Coaches Section Partial --}}
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

        @if(isset($coaches) && $coaches->count() > 0)
            @php
                // Get layout setting from section data (default to grid)
                $layoutMode = $layout ?? 'grid';
                
                // Calculate Bootstrap column classes (only for grid layout)
                if ($layoutMode === 'grid') {
                    $gridCols = match((int)($columns ?? 3)) {
                        2 => 'row-cols-1 row-cols-md-2',
                        4 => 'row-cols-1 row-cols-md-2 row-cols-lg-4',
                        default => 'row-cols-1 row-cols-md-2 row-cols-lg-3'
                    };
                }
            @endphp
            @if($layoutMode === 'list')
                {{-- List Layout --}}
                <div class="row g-4">
                    @foreach($coaches as $coach)
                        <div class="col-12">
                            <div class="card border-0 shadow-lg coach-card coach-card-horizontal">
                                <div class="row g-0">
                                    @if($showPhoto)
                                        <div class="col-md-4">
                                            @if($coach->photoFilePath)
                                                <div class="coach-image-horizontal" style="background-image: url({{ asset('storage/' . $coach->photoFilePath) }});"></div>
                                            @elseif($coach->portraitFilePath)
                                                <div class="coach-image-horizontal" style="background-image: url({{ asset('storage/' . $coach->portraitFilePath) }});"></div>
                                            @else
                                                <div class="coach-image-horizontal d-flex align-items-center justify-content-center bg-light">
                                                    <i class="fas fa-user-tie fa-3x text-muted"></i>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="{{ $showPhoto ? 'col-md-8' : 'col-12' }}">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h5 class="card-title mb-0 fw-bold">{{ $coach->fullName }}</h5>
                                                <div class="coach-certifications">
                                                    <span class="badge bg-primary me-1">Coach</span>
                                                    @if($coach->isStaff)
                                                        <span class="badge bg-secondary">Staff</span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if($coach->email)
                                                <p class="text-muted mb-2">{{ $coach->email }}</p>
                                            @endif
                                            @if($showBio && $coach->address)
                                                <p class="card-text small mb-3">{{ Str::limit($coach->address, 200) }}</p>
                                            @endif
                                            <div class="social-links">
                                                <a href="#" class="social-link me-2"><i class="fab fa-facebook-f"></i></a>
                                                <a href="#" class="social-link me-2"><i class="fab fa-instagram"></i></a>
                                                <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Grid Layout --}}
                <div class="row g-4 {{ $gridCols }}">
                    @foreach($coaches as $coach)
                        <div class="col">
                            <div class="card border-0 shadow-lg h-100 coach-card">
                            @if($showPhoto)
                                <div class="coach-img-container position-relative overflow-hidden">
                                    @if($coach->photoFilePath)
                                        <img src="{{ asset('storage/' . $coach->photoFilePath) }}" class="card-img-top coach-img" alt="Coach {{ $coach->fullName }}" height="300">
                                    @elseif($coach->portraitFilePath)
                                        <img src="{{ asset('storage/' . $coach->portraitFilePath) }}" class="card-img-top coach-img" alt="Coach {{ $coach->fullName }}" height="300">
                                    @else
                                        <div class="card-img-top coach-img d-flex align-items-center justify-content-center bg-light" style="height: 300px;">
                                            <i class="fas fa-user-tie fa-4x text-muted"></i>
                                        </div>
                                    @endif
                                    <div class="coach-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                                        <div class="social-links">
                                            <a href="#" class="social-link me-2"><i class="fab fa-facebook-f"></i></a>
                                            <a href="#" class="social-link me-2"><i class="fab fa-instagram"></i></a>
                                            <a href="#" class="social-link"><i class="fab fa-linkedin"></i></a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="card-body text-center p-4">
                                <h5 class="card-title mb-2 fw-bold">{{ $coach->fullName }}</h5>
                                @if($coach->email)
                                    <p class="text-muted mb-3">{{ $coach->email }}</p>
                                @endif
                                @if($showBio && $coach->address)
                                    <p class="card-text small">{{ Str::limit($coach->address, 150) }}</p>
                                @endif
                                <div class="coach-certifications">
                                    <span class="badge bg-primary me-1">Coach</span>
                                    @if($coach->isStaff)
                                        <span class="badge bg-secondary">Staff</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        @else
            <div class="text-center py-12 bg-light rounded">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <p class="text-muted">No coaches available at this time.</p>
            </div>
        @endif
    </div>

    <style>
        .coach-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .coach-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1)!important;
        }
        
        .coach-img-container {
            height: 300px;
        }
        
        .coach-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .coach-overlay {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.9) 0%, rgba(78, 205, 196, 0.9) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .coach-card:hover .coach-overlay {
            opacity: 1;
        }
        
        .coach-card:hover .coach-img {
            transform: scale(1.1);
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
                <div class="row">
                    @foreach($coaches as $coach)
                        <div class="col-lg-3 col-md-6 d-flex mb-sm-4 ftco-animate">
                            <div class="staff">
                                @if($showPhoto)
                                    @if($coach->photoFilePath)
                                        <div class="img mb-4" style="background-image: url({{ asset('storage/' . $coach->photoFilePath) }});"></div>
                                    @elseif($coach->portraitFilePath)
                                        <div class="img mb-4" style="background-image: url({{ asset('storage/' . $coach->portraitFilePath) }});"></div>
                                    @else
                                        <div class="img mb-4 d-flex align-items-center justify-content-center bg-light">
                                            <i class="fas fa-user-tie fa-3x text-muted"></i>
                                        </div>
                                    @endif
                                @endif
                                <div class="info text-center">
                                    <h3><a href="#">{{ $coach->fullName }}</a></h3>
                                    @if($coach->email)
                                        <span class="position">{{ $coach->email }}</span>
                                    @endif
                                    @if($showBio)
                                        <div class="text">
                                            @if($coach->address)
                                                <p>{{ Str::limit($coach->address, 150) }}</p>
                                            @else
                                                <p>Dedicated coach ready to help you achieve your fitness goals.</p>
                                            @endif
                                            <ul class="ftco-social">
                                                <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
                                                <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
                                                <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
                                            </ul>
                                        </div>
                                    @endif
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
                @foreach($coaches as $coach)
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                        @if($showPhoto)
                            @if($coach->photoFilePath)
                                <img src="{{ asset('storage/' . $coach->photoFilePath) }}" alt="Coach {{ $coach->fullName }}" class="w-full h-64 object-cover" height="256">
                            @elseif($coach->portraitFilePath)
                                <img src="{{ asset('storage/' . $coach->portraitFilePath) }}" alt="Coach {{ $coach->fullName }}" class="w-full h-64 object-cover" height="256">
                            @else
                                <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-user-tie text-4xl text-gray-400"></i>
                                </div>
                            @endif
                        @endif
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-2">{{ $coach->fullName }}</h3>
                            @if($coach->email)
                                <p class="text-indigo-600 mb-3">{{ $coach->email }}</p>
                            @endif
                            @if($showBio && $coach->address)
                                <p class="text-gray-600 mb-4">{{ Str::limit($coach->address, 150) }}</p>
                            @endif
                            <div class="flex space-x-3">
                                <a href="#" class="text-gray-400 hover:text-indigo-600">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="text-gray-400 hover:text-indigo-600">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="text-gray-400 hover:text-indigo-600">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            </div>
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
<div>
    <div class="org-user-view">
        <div class="container" style="max-width: 1200px;">
            @if($coach)
                {{-- Breadcrumb Navigation - Matching SuperHero CrossFit Design --}}
                <div class="py-3">
                    <nav aria-label="breadcrumb" class="mb-0">
                        <ol class="breadcrumb mb-0" style="background-color: transparent; padding: 0;">
                            <li class="breadcrumb-item"><a href="/" class="text-decoration-none" style="color: #6c757d;">Home</a></li>
                            <li class="breadcrumb-item"><a href="/coaches" class="text-decoration-none" style="color: #6c757d;">Our Coaches</a></li>
                            <li class="breadcrumb-item active" aria-current="page" style="color: #212529;">{{ $coach->fullName }}</li>
                        </ol>
                    </nav>
                </div>
                
                {{-- Coach Profile Content - Matching SuperHero CrossFit Design --}}
                <div class="row g-4">
                    <!-- Coach Photo -->
                    <div class="col-md-4">
                        <div class="profile-picture">
                            @if($coach->profileImageUrl)
                                <img src="{{ $coach->profileImageUrl }}" 
                                     alt="{{ $coach->fullName }}"
                                     class="img-fluid w-100">
                            @elseif($coach->portraitImageUrl)
                                <img src="{{ $coach->portraitImageUrl }}" 
                                     alt="{{ $coach->fullName }}"
                                     class="img-fluid w-100">
                            @else
                                <div class="d-flex align-items-center justify-content-center bg-light" 
                                     style="width: 100%; height: 400px;">
                                    <i class="fas fa-user-tie fa-5x text-muted"></i>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Coach Details -->
                    <div class="col-md-8">
                        <div class="coach-details">
                            <h2 class="coach-name mb-4">{{ $coach->fullName }}</h2>
                            
                            <div class="coach-section mb-4">
                                <h6 class="coach-label">Bio:</h6>
                                <p class="coach-content">{{ $coach->bio ?? 'No bio available.' }}</p>
                            </div>
                            
                            <div class="coach-section mb-4">
                                <h6 class="coach-label">Favorite Quote:</h6>
                                <p class="coach-content">{{ $coach->favoriteQuote ?? 'No favorite quote available.' }}</p>
                            </div>
                            
                            <div class="coach-section mb-4">
                                <h6 class="coach-label">Certificates:</h6>
                                <div class="coach-content">
                                    @if($coach->certificates)
                                        @php
                                            // Handle certificates - could be JSON string, array, or plain string
                                            $certificates = $coach->certificates;
                                            if (is_string($certificates)) {
                                                $decoded = json_decode($certificates, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    $certificates = $decoded;
                                                } else {
                                                    $certificates = [$certificates];
                                                }
                                            }
                                            if (!is_array($certificates)) {
                                                $certificates = [$certificates];
                                            }
                                        @endphp
                                        @if(count($certificates) > 0)
                                            <ul class="list-unstyled mb-0">
                                                @foreach($certificates as $certificate)
                                                    <li>{{ $certificate }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            {{ $coach->certificates }}
                                        @endif
                                    @else
                                        No certificates available.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-5">
                            <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                            <h3 class="h4 fw-bold text-muted mb-2">Coach Not Found</h3>
                            <p class="text-muted mb-4">The coach profile you're looking for doesn't exist or is no longer available.</p>
                            <a href="{{ route('home') }}" class="btn btn-primary package-btn">
                                <i class="fas fa-home me-2"></i>Return to Home
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        /* Coach Profile View - Matching SuperHero CrossFit Design */
        .org-user-view {
            padding: 20px 0 40px 0;
            background-color: #ffffff;
            color: #212529;
        }
        
        /* Breadcrumb Styling */
        .org-user-view .breadcrumb {
            background-color: #f5f5f5;
            padding: 10px 15px;
            margin-bottom: 0;
        }
        
        .org-user-view .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
            color: #6c757d;
            padding: 0 0.5rem;
        }
        
        .org-user-view .breadcrumb-item a {
            color: #6c757d;
            text-decoration: none;
        }
        
        .org-user-view .breadcrumb-item a:hover {
            color: #212529;
            text-decoration: underline;
        }
        
        .org-user-view .breadcrumb-item.active {
            color: #212529;
        }
        
        /* Profile Picture */
        .org-user-view .profile-picture {
            margin-bottom: 0;
        }
        
        .org-user-view .profile-picture img {
            width: 100%;
            height: auto;
            border-radius: 0;
            box-shadow: none;
            display: block;
        }
        
        /* Coach Details */
        .org-user-view .coach-details {
            padding: 0;
        }
        
        .org-user-view .coach-name {
            font-size: 2rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .org-user-view .coach-section {
            margin-bottom: 1.5rem;
        }
        
        .org-user-view .coach-label {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
            text-transform: none;
        }
        
        .org-user-view .coach-content {
            font-size: 1rem;
            color: #212529;
            line-height: 1.6;
            margin-bottom: 0;
        }
        
        .org-user-view .coach-content ul {
            padding-left: 0;
            list-style: none;
            margin-bottom: 0;
        }
        
        .org-user-view .coach-content li {
            padding: 0.25rem 0;
            color: #212529;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .org-user-view {
                padding: 15px 0 30px 0;
            }
            
            .org-user-view .coach-name {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }
            
            .org-user-view .coach-section {
                margin-bottom: 1.25rem;
            }
            
            .org-user-view .breadcrumb {
                padding: 8px 12px;
                font-size: 0.875rem;
            }
        }
    </style>
</div>

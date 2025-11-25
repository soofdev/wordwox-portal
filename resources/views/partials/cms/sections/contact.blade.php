{{-- Contact Form Section Partial --}}
@if($isFitness)
    <div class="container my-5">
        <div class="row">
            {{-- Contact Information --}}
            @if($showContactInfo && ($orgContact['email'] || $orgContact['phone'] || $orgContact['address']))
                <div class="col-lg-4 mb-5">
                    <div class="card h-100 border-0 shadow-sm bg-primary text-white">
                        <div class="card-body p-4">
                            <h3 class="h5 mb-4"><i class="fas fa-map-marker-alt me-2"></i>Contact Information</h3>
                            @if($orgContact['address'])
                                <div class="mb-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <span>{{ $orgContact['address'] }}</span>
                                </div>
                            @endif
                            @if($orgContact['phone'])
                                <div class="mb-3">
                                    <i class="fas fa-phone me-2"></i>
                                    <a href="tel:{{ $orgContact['phone'] }}" class="text-white text-decoration-none">{{ $orgContact['phone'] }}</a>
                                </div>
                            @endif
                            @if($orgContact['email'])
                                <div class="mb-3">
                                    <i class="fas fa-envelope me-2"></i>
                                    <a href="mailto:{{ $orgContact['email'] }}" class="text-white text-decoration-none">{{ $orgContact['email'] }}</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
            @else
                <div class="col-lg-8 mx-auto">
            @endif
                @if($section->title)
                <h2 class="section-heading text-center mb-4">{{ $section->title }}</h2>
                @endif
                @if($section->subtitle)
                <p class="text-center mb-5 text-muted">{{ $section->subtitle }}</p>
                @endif
                @if($section->content)
                <div class="text-center mb-5">{!! $section->content !!}</div>
                @endif

                <!-- Dynamic Contact Form -->
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        @livewire('contact-form', ['template' => 'fitness'])
                    </div>
                </div>
            </div>
        </div>
    </div>
@elseif($isMeditative)
    <section class="ftco-section contact-section ftco-degree-bg">
        <div class="container">
            <div class="row d-flex mb-5 contact-info">
                <div class="col-md-12 mb-4">
                    @if($section->title)
                    <h2 class="h4">{{ $section->title }}</h2>
                    @endif
                    @if($section->subtitle)
                    <p>{{ $section->subtitle }}</p>
                    @endif
                    @if($section->content)
                    <div>{!! $section->content !!}</div>
                    @endif
                </div>
            </div>
            <div class="row block-9">
                <div class="col-md-6 pr-md-5">
                    {{-- Dynamic Contact Form --}}
                    <div class="bg-light p-5 contact-form">
                        @livewire('contact-form', ['template' => 'meditative'])
                    </div>
                </div>
                <div class="col-md-6">
                    {{-- Contact Information --}}
                    @if($showContactInfo && ($orgContact['email'] || $orgContact['phone'] || $orgContact['address']))
                        <div class="contact-info bg-primary p-5 h-100">
                            <h3 class="h4 text-white mb-4">Contact Information</h3>
                            @if($orgContact['address'])
                                <p class="text-white-50 mb-3">
                                    <span class="text-white font-weight-bold">Address:</span><br>
                                    {{ $orgContact['address'] }}
                                </p>
                            @endif
                            @if($orgContact['phone'])
                                <p class="text-white-50 mb-3">
                                    <span class="text-white font-weight-bold">Phone:</span><br>
                                    <a href="tel:{{ $orgContact['phone'] }}" class="text-white">{{ $orgContact['phone'] }}</a>
                                </p>
                            @endif
                            @if($orgContact['email'])
                                <p class="text-white-50 mb-3">
                                    <span class="text-white font-weight-bold">Email:</span><br>
                                    <a href="mailto:{{ $orgContact['email'] }}" class="text-white">{{ $orgContact['email'] }}</a>
                                </p>
                            @endif
                        </div>
                    @else
                        <div id="map"></div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@else
    {{-- Default Contact Form for Modern Template --}}
    <div class="max-w-6xl mx-auto">
        @if($section->title)
        <h2 class="text-3xl font-bold text-center mb-8">{{ $section->title }}</h2>
        @endif
        @if($section->subtitle)
        <p class="text-xl text-center mb-8 text-gray-600">{{ $section->subtitle }}</p>
        @endif
        @if($section->content)
        <div class="text-center mb-8">{!! $section->content !!}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Contact Information --}}
            @if($showContactInfo && ($orgContact['email'] || $orgContact['phone'] || $orgContact['address']))
                <div class="lg:col-span-1">
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-8 text-white h-full">
                        <h3 class="text-2xl font-bold mb-6">Contact Information</h3>
                        @if($orgContact['address'])
                            <div class="mb-6">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-map-marker-alt text-xl mr-3"></i>
                                    <span class="font-semibold">Address</span>
                                </div>
                                <p class="text-indigo-100 ml-8">{{ $orgContact['address'] }}</p>
                            </div>
                        @endif
                        @if($orgContact['phone'])
                            <div class="mb-6">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-phone text-xl mr-3"></i>
                                    <span class="font-semibold">Phone</span>
                                </div>
                                <a href="tel:{{ $orgContact['phone'] }}" class="text-indigo-100 hover:text-white ml-8">{{ $orgContact['phone'] }}</a>
                            </div>
                        @endif
                        @if($orgContact['email'])
                            <div class="mb-6">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-envelope text-xl mr-3"></i>
                                    <span class="font-semibold">Email</span>
                                </div>
                                <a href="mailto:{{ $orgContact['email'] }}" class="text-indigo-100 hover:text-white ml-8">{{ $orgContact['email'] }}</a>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="lg:col-span-2">
            @else
                <div class="lg:col-span-3">
            @endif
                {{-- Dynamic Contact Form --}}
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    @livewire('contact-form', ['template' => 'modern'])
                </div>
            </div>
        </div>
    </div>
@endif
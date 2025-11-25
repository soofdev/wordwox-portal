{{-- Schedule Section Partial --}}
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

        {{-- Dynamic Schedule Navigation --}}
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-pills justify-content-center mb-4" id="schedule-tabs" role="tablist">
                    @foreach($showDays as $index => $day)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="{{ $day }}-tab" data-bs-toggle="pill" data-bs-target="#{{ $day }}" type="button" role="tab">
                                {{ ucfirst($day) }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Dynamic Schedule Content --}}
        <div class="tab-content" id="schedule-content">
            @foreach($showDays as $index => $day)
                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $day }}" role="tabpanel">
                    @if(isset($scheduleByDay[$day]) && $scheduleByDay[$day]->count() > 0)
                        <div class="row g-3">
                            @foreach($scheduleByDay[$day] as $event)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card schedule-card border-0 shadow-sm h-100">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h5 class="card-title mb-0">{{ $event->name ?: ($event->program ? $event->program->name : 'Class') }}</h5>
                                                <span class="badge bg-primary">Class</span>
                                            </div>
                                            <p class="text-muted mb-2"><i class="fas fa-clock me-2"></i>{{ $event->startDateTime->format('g:i A') }} - {{ $event->endDateTime->format('g:i A') }}</p>
                                            @if($showInstructor && $event->instructor)
                                                <p class="text-muted mb-2"><i class="fas fa-user me-2"></i>{{ $event->instructor->fullName }}</p>
                                            @endif
                                            @if($showCapacity && $event->capacity)
                                                @php
                                                    $bookedCount = \App\Models\EventSubscriber::where('event_id', $event->id)
                                                        ->where('booking_status', \App\Models\EventSubscriber::STATUS_BOOKED)
                                                        ->count();
                                                    $availableSpots = $event->capacity - $bookedCount;
                                                @endphp
                                                <p class="text-muted mb-3"><i class="fas fa-users me-2"></i>{{ $bookedCount }}/{{ $event->capacity }} booked ({{ $availableSpots }} spots left)</p>
                                            @endif
                                            @if($event->note)
                                                <p class="card-text small mb-3">{{ Str::limit($event->note, 120) }}</p>
                                            @endif
                                            @if($showBookButton)
                                                @if($availableSpots > 0)
                                                    <button class="btn btn-outline-primary btn-sm w-100">{{ $bookButtonText }}</button>
                                                @else
                                                    <button class="btn btn-outline-secondary btn-sm w-100" disabled>Fully Booked</button>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No classes scheduled for {{ ucfirst($day) }}.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .schedule-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1)!important;
        }
        
        .nav-pills .nav-link {
            border-radius: 25px;
            padding: 10px 20px;
            margin: 0 5px;
            transition: all 0.3s ease;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            border: none;
        }
        
        .nav-pills .nav-link:not(.active) {
            background: #f8f9fa;
            color: #666;
        }
        
        .nav-pills .nav-link:not(.active):hover {
            background: #e9ecef;
            color: #333;
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
            <div class="row">
                <div class="col-md-12">
                    @if($scheduleByDay && collect($scheduleByDay)->flatten()->count() > 0)
                        {{-- Dynamic Schedule Cards --}}
                        <div class="row">
                            @foreach($showDays as $day)
                                <div class="col-lg-6 mb-4">
                                    <div class="card border-primary">
                                        <div class="card-header bg-primary text-white text-center">
                                            <h4 class="mb-0">{{ ucfirst($day) }}</h4>
                                        </div>
                                        <div class="card-body">
                                            @if(isset($scheduleByDay[$day]) && $scheduleByDay[$day]->count() > 0)
                                                @foreach($scheduleByDay[$day] as $event)
                                                    <div class="mb-3 p-3 border-left border-primary">
                                                        <h5 class="text-primary mb-1">{{ $event->name }}</h5>
                                                        <p class="text-muted mb-1">
                                                            <i class="icon-clock-o mr-2"></i>
                                                            {{ $event->start_datetime->format('g:i A') }} - {{ $event->end_datetime->format('g:i A') }}
                                                        </p>
                                                        @if($showInstructor && $event->instructor)
                                                            <p class="text-muted mb-1">
                                                                <i class="icon-user mr-2"></i>
                                                                {{ $event->instructor->fullName }}
                                                            </p>
                                                        @endif
                                                        @if($event->orgLocation)
                                                            <span class="address">{{ $event->orgLocation->name ?? 'Main Studio' }}</span>
                                                        @endif
                                                        @if($showCapacity && $event->capacity)
                                                            @php
                                                                $bookedCount = \App\Models\EventSubscriber::where('event_id', $event->id)
                                                                    ->where('booking_status', \App\Models\EventSubscriber::STATUS_BOOKED)
                                                                    ->count();
                                                                $availableSpots = $event->capacity - $bookedCount;
                                                            @endphp
                                                            <small class="text-muted d-block">{{ $bookedCount }}/{{ $event->capacity }} spots filled</small>
                                                        @endif
                                                    </div>
                                                    @if(!$loop->last)<hr>@endif
                                                @endforeach
                                            @else
                                                <div class="text-center py-4">
                                                    <span class="off-day text-muted">No classes scheduled</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @if($loop->iteration % 2 == 0)
                                    </div><div class="row">
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="icon-calendar fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No classes scheduled for this week. Please check back later.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@else
    {{-- Default Schedule for Modern Template --}}
    <div class="max-w-7xl mx-auto">
        @if($section->title)
        <div class="text-center mb-12">
            <h2 class="text-4xl font-bold mb-4">{{ $section->title }}</h2>
            @if($section->subtitle)
            <p class="text-xl text-gray-600">{{ $section->subtitle }}</p>
            @endif
        </div>
        @endif

        @if($section->content)
        <div class="text-center mb-8">{!! $section->content !!}</div>
        @endif

        {{-- Dynamic Weekly Schedule Tabs --}}
        <div class="mb-8">
            <div class="flex flex-wrap justify-center space-x-4 mb-6">
                @foreach($showDays as $index => $day)
                    <button class="schedule-tab {{ $index === 0 ? 'active bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }} px-4 py-2 rounded-lg" data-day="{{ $day }}">
                        {{ ucfirst($day) }}
                    </button>
                @endforeach
            </div>

            {{-- Dynamic Schedule Content --}}
            <div id="schedule-content">
                @foreach($showDays as $index => $day)
                    <div class="schedule-day {{ $index === 0 ? 'active' : 'hidden' }}" id="{{ $day }}-schedule">
                        @if(isset($scheduleByDay[$day]) && $scheduleByDay[$day]->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach($scheduleByDay[$day] as $event)
                                    @php
                                        $bookedCount = \App\Models\EventSubscriber::where('event_id', $event->id)
                                            ->where('booking_status', \App\Models\EventSubscriber::STATUS_BOOKED)
                                            ->count();
                                        $availableSpots = $event->capacity ? $event->capacity - $bookedCount : null;
                                    @endphp
                                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                                        <div class="flex justify-between items-start mb-3">
                                            <h3 class="text-xl font-semibold">{{ $event->name ?: ($event->program ? $event->program->name : 'Class') }}</h3>
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">Class</span>
                                        </div>
                                        <p class="text-gray-600 mb-2"><i class="fas fa-clock mr-2"></i>{{ $event->startDateTime->format('g:i A') }} - {{ $event->endDateTime->format('g:i A') }}</p>
                                        @if($showInstructor && $event->instructor)
                                            <p class="text-gray-600 mb-2"><i class="fas fa-user mr-2"></i>{{ $event->instructor->fullName }}</p>
                                        @endif
                                        @if($showCapacity && $event->capacity)
                                            <p class="text-gray-600 mb-4"><i class="fas fa-users mr-2"></i>{{ $bookedCount }}/{{ $event->capacity }} booked</p>
                                        @endif
                                        @if($event->note)
                                            <p class="text-gray-500 mb-4 text-sm">{{ Str::limit($event->note, 100) }}</p>
                                        @endif
                                        @if($showBookButton)
                                            @if($availableSpots === null || $availableSpots > 0)
                                                <button class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700">{{ $bookButtonText }}</button>
                                            @else
                                                <button class="w-full bg-gray-400 text-white py-2 rounded-lg cursor-not-allowed" disabled>Fully Booked</button>
                                            @endif
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <i class="fas fa-calendar-alt text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500">No classes scheduled for {{ ucfirst($day) }}.</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
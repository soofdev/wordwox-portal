{{-- Section Wrapper Partial --}}
@php
    // Get section customization settings
    $sectionSettings = is_string($section->settings_json) ? 
        json_decode($section->settings_json, true) : 
        ($section->settings_json ?? []);
    
    // Extract layout, spacing, background, typography settings
    $layout = $sectionSettings['layout'] ?? [];
    $spacing = $sectionSettings['spacing'] ?? [];
    $background = $sectionSettings['background'] ?? [];
    $typography = $sectionSettings['typography'] ?? [];
    
    // Build CSS classes
    $sectionClasses = ['cms-section', 'cms-section-' . $section->type];
    $containerClasses = [];
    $sectionStyles = [];
    
    // Layout classes
    if ($isMeditative) {
        // Meditative template uses Bootstrap 4
        switch ($layout['width'] ?? 'container') {
            case 'full':
                $containerClasses[] = 'container-fluid';
                break;
            case 'narrow':
                $containerClasses[] = 'container';
                break;
            default: // container
                $containerClasses[] = 'container';
                break;
        }
    } elseif ($isFitness) {
        // Fitness template uses Bootstrap 5
        switch ($layout['width'] ?? 'container') {
            case 'full':
                $containerClasses[] = 'container-fluid';
                break;
            case 'narrow':
                $containerClasses[] = 'container-sm';
                break;
            default: // container
                $containerClasses[] = 'container';
                break;
        }
    } else {
        // Other templates use Tailwind CSS
        switch ($layout['width'] ?? 'container') {
            case 'full':
                $containerClasses[] = 'w-full';
                break;
            case 'narrow':
                $containerClasses[] = 'max-w-4xl mx-auto px-6';
                break;
            default: // container
                $containerClasses[] = 'container mx-auto px-6';
                break;
        }
    }
    
    // Spacing classes
    $paddingMap = [
        'none' => '0',
        'xs' => '0.5rem',
        'sm' => '1rem', 
        'md' => '2rem',
        'lg' => '3rem',
        'xl' => '4rem',
        '2xl' => '6rem'
    ];
    
    // Only apply padding if explicitly set in section settings (not by default)
    if (isset($spacing['padding_top']) && $spacing['padding_top'] !== 'none') {
        $topPadding = $spacing['padding_top'];
        $sectionStyles[] = 'padding-top: ' . ($paddingMap[$topPadding] ?? $paddingMap['md']);
    }
    if (isset($spacing['padding_bottom']) && $spacing['padding_bottom'] !== 'none') {
        $bottomPadding = $spacing['padding_bottom'];
        $sectionStyles[] = 'padding-bottom: ' . ($paddingMap[$bottomPadding] ?? $paddingMap['md']);
    }
    
    // Background styles
    switch ($background['type'] ?? 'color') {
        case 'gradient':
            $gradientClass = $background['gradient'] ?? 'bg-gradient-to-r from-blue-500 to-purple-600';
            $sectionClasses[] = $gradientClass;
            break;
        case 'image':
            $sectionClasses[] = 'bg-cover bg-center';
            if (isset($background['image'])) {
                $sectionStyles[] = "background-image: url('{$background['image']}')";
            }
            break;
        case 'none':
            // No background
            break;
        default: // color
            $sectionStyles[] = 'background-color: ' . ($background['color'] ?? '#ffffff');
            break;
    }
    
    // Typography
    if ($isMeditative) {
        // Bootstrap 4 text alignment classes
        $alignmentMap = [
            'left' => 'text-left',
            'center' => 'text-center', 
            'right' => 'text-right',
            'justify' => 'text-justify'
        ];
    } elseif ($isFitness) {
        // Bootstrap 5 text alignment classes
        $alignmentMap = [
            'left' => 'text-start',
            'center' => 'text-center', 
            'right' => 'text-end',
            'justify' => 'text-justify'
        ];
    } else {
        // Tailwind CSS text alignment classes
        $alignmentMap = [
            'left' => 'text-left',
            'center' => 'text-center', 
            'right' => 'text-right',
            'justify' => 'text-justify'
        ];
    }
    
    $containerClasses[] = $alignmentMap[$typography['text_align'] ?? 'left'];
    
    // Set text color for all sections
    if (isset($typography['text_color'])) {
        $sectionStyles[] = 'color: ' . $typography['text_color'];
    } else {
        // Set default text color if not specified - determine based on background
        $bgColor = $background['color'] ?? '#ffffff';
        // If background is light/white, use dark text; if dark, use light text
        $isLightBg = $bgColor === '#ffffff' || $bgColor === '#fff' || stripos($bgColor, '#f') === 0;
        $defaultTextColor = $isLightBg ? '#2c3e50' : '#ffffff';
        $sectionStyles[] = 'color: ' . $defaultTextColor;
    }
    
    // Add template-specific classes
    $templateClasses = [];
    if ($isMeditative) {
        $templateClasses[] = 'ftco-section';
        if ($section->type !== 'hero') {
            $templateClasses[] = 'ftco-animate';
        }
    } elseif ($isFitness) {
        // Don't add section-padding class - each section type handles its own padding
        if ($section->type !== 'hero') {
            $templateClasses[] = 'fitness-section';
        }
    }
    $finalSectionClasses = array_merge($sectionClasses, $templateClasses);
    
    $sectionClassString = implode(' ', $finalSectionClasses);
    $containerClassString = implode(' ', $containerClasses);
    $sectionStyleString = implode('; ', $sectionStyles);
@endphp

<div class="{{ $sectionClassString }}" 
     id="section-{{ $section->id }}" 
     style="{{ $sectionStyleString }}">
    
    @if($section->type === 'hero' && ($isMeditative || $isFitness))
        {{-- Hero sections don't need the container wrapper --}}
        @include('partials.cms.sections.hero', compact('section', 'isMeditative', 'isFitness'))
    @else
        {{-- All other sections use the container wrapper --}}
        <div class="{{ $containerClassString }}">
            @switch($section->type)
                @case('hero')
                    @include('partials.cms.sections.hero', compact('section', 'isMeditative', 'isFitness'))
                    @break
                    
                @case('contact')
                    @php
                        // Get contact block data
                        $contactData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($contactData)) {
                            $contactData = [];
                        }
                        
                        // Get organization ID from page or default
                        $orgId = $page->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
                        
                        // Get organization contact information
                        $organization = \App\Models\Org::find($orgId);
                        $orgContact = [
                            'name' => $organization?->name ?? config('app.name'),
                            'email' => $organization?->email ?? $contactData['email'] ?? '',
                            'phone' => $organization?->phoneNumber ?? $contactData['phone'] ?? '',
                            'address' => $organization?->address ?? $contactData['location'] ?? '',
                        ];
                        
                        // Form configuration
                        $formFields = $contactData['fields'] ?? [];
                        $submitText = $contactData['submit_text'] ?? 'Send Message';
                        $successMessage = $contactData['success_message'] ?? 'Thank you for your message! We\'ll get back to you soon.';
                        $showContactInfo = $contactData['show_contact_info'] ?? true;
                        $mapUrl = $contactData['map_url'] ?? '';
                    @endphp
                    @include('partials.cms.sections.contact', compact('section', 'page', 'isMeditative', 'isFitness', 'currentTemplate', 'orgContact', 'formFields', 'submitText', 'successMessage', 'showContactInfo', 'mapUrl'))
                    @break
                    
                @case('packages')
                    @php
                        // Get packages block data
                        $packagesData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($packagesData)) {
                            $packagesData = [];
                        }
                        
                        // Get organization ID from page or default
                        $orgId = $page->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
                        
                        // Fetch active organization plans
                        $plans = \App\Models\OrgPlan::where('org_id', $orgId)
                            ->where('isActive', true)
                            ->where('is_upcharge_plan', false) // Only main plans
                            ->orderBy('name', 'asc')
                            ->get();
                        
                        // Check active memberships for authenticated users
                        $userActivePlans = [];
                        if (Auth::check() && Auth::user()->orgUser_id) {
                            $orgUser = \App\Models\OrgUser::find(Auth::user()->orgUser_id);
                            if ($orgUser) {
                                // Get all active/upcoming/pending memberships in one query
                                $activeMemberships = \App\Models\OrgUserPlan::where('orgUser_id', $orgUser->id)
                                    ->whereIn('status', [
                                        \App\Models\OrgUserPlan::STATUS_ACTIVE,
                                        \App\Models\OrgUserPlan::STATUS_UPCOMING,
                                        \App\Models\OrgUserPlan::STATUS_PENDING,
                                    ])
                                    ->where('isCanceled', false)
                                    ->where('isDeleted', false)
                                    ->get();
                                
                                // Map plan IDs to their status
                                foreach ($activeMemberships as $membership) {
                                    $userActivePlans[$membership->orgPlan_id] = $membership->status;
                                }
                            }
                        }
                        
                        $layout = $packagesData['layout'] ?? 'grid';
                        $columns = $packagesData['columns'] ?? 3;
                        $showDescription = $packagesData['show_description'] ?? true;
                        $showPrograms = $packagesData['show_programs'] ?? true;
                        $buyButtonText = $packagesData['buy_button_text'] ?? 'Buy';
                        $purchaseAtGymText = $packagesData['purchase_at_gym_text'] ?? 'Purchase at the Gym';
                    @endphp
                    @include('partials.cms.sections.packages', compact('section', 'page', 'isMeditative', 'isFitness', 'plans', 'layout', 'columns', 'showDescription', 'showPrograms', 'buyButtonText', 'purchaseAtGymText', 'userActivePlans'))
                    @break
                    
                @case('coaches')
                    @php
                        // Get coaches block data
                        $coachesData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($coachesData)) {
                            $coachesData = [];
                        }
                        
                        // Get organization ID from page or default
                        $orgId = $page->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
                        
                        // Fetch coaches (staff members on roster) from organization
                        $coaches = \App\Models\OrgUser::where('org_id', $orgId)
                            ->where('isOnRoster', true)
                            ->where('isActive', true)
                            ->orderBy('fullName', 'asc')
                            ->get();
                        
                        // Configuration options
                        $layout = $coachesData['layout'] ?? 'grid';
                        $columns = $coachesData['columns'] ?? 3;
                        $showPhoto = $coachesData['show_photo'] ?? true;
                        $showBio = $coachesData['show_bio'] ?? true;
                        $viewProfileText = $coachesData['view_profile_text'] ?? 'View Profile';
                    @endphp
                    @include('partials.cms.sections.coaches', compact('section', 'page', 'isMeditative', 'isFitness', 'currentTemplate', 'coaches', 'layout', 'columns', 'showPhoto', 'showBio', 'viewProfileText'))
                    @break
                    
                @case('schedule')
                    @php
                        // Get schedule block data
                        $scheduleData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($scheduleData)) {
                            $scheduleData = [];
                        }
                        
                        // Get organization ID from page or default
                        $orgId = $page->org_id ?? env('CMS_DEFAULT_ORG_ID', 8);
                        
                        // Get recurring schedules from the schedule table
                        $schedules = \DB::table('schedule')
                            ->where('org_id', $orgId)
                            ->where('isDeleted', false)
                            ->where('status', 1) // Active schedules
                            ->get();
                        
                        // Generate events for the next 7 days based on recurring schedules
                        $weeklyEvents = collect();
                        $startDate = now()->startOfDay();
                        
                        for ($i = 0; $i < 7; $i++) {
                            $date = $startDate->copy()->addDays($i);
                            $dayOfWeek = strtolower($date->format('D')); // mon, tue, wed, etc.
                            
                            foreach ($schedules as $schedule) {
                                // Check if this schedule runs on this day
                                if (!$schedule->$dayOfWeek) {
                                    continue;
                                }
                                
                                // Create event object from schedule
                                $event = new \stdClass();
                                $event->id = $schedule->id . '_' . $date->format('Y-m-d');
                                $event->schedule_id = $schedule->id;
                                $event->name = $schedule->name;
                                $event->note = $schedule->note;
                                
                                // Parse time and create datetime
                                if ($schedule->localStartTime && $schedule->localEndTime) {
                                    $event->startDateTime = \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->localStartTime);
                                    $event->endDateTime = \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->localEndTime);
                                } else {
                                    continue; // Skip if no time set
                                }
                                
                                // Get capacity for this day
                                $capacityField = 'capacity' . ucfirst($dayOfWeek);
                                $event->capacity = $schedule->$capacityField ?? null;
                                
                                // Load program
                                if ($schedule->program_id) {
                                    $program = \App\Models\Program::find($schedule->program_id);
                                    $event->program = $program;
                                }
                                
                                // Load location
                                if ($schedule->orgLocation_id) {
                                    $event->orgLocation = \App\Models\OrgLocation::find($schedule->orgLocation_id);
                                }
                                
                                // Load instructor from scheduleAssignment
                                // Note: scheduleAssignment table doesn't have a role column
                                // Always set instructor property, even if null
                                $instructor = \DB::table('scheduleAssignment')
                                    ->join('orgUser', 'scheduleAssignment.orgUser_id', '=', 'orgUser.id')
                                    ->where('scheduleAssignment.schedule_id', $schedule->id)
                                    ->where('scheduleAssignment.isDeleted', false)
                                    ->select('orgUser.*')
                                    ->first();
                                
                                if ($instructor) {
                                    $event->instructor = (object)[
                                        'id' => $instructor->id,
                                        'fullName' => trim(($instructor->fname ?? '') . ' ' . ($instructor->lname ?? '')),
                                        'email' => $instructor->email ?? '',
                                    ];
                                } else {
                                    $event->instructor = null;
                                }
                                
                                $weeklyEvents->push($event);
                            }
                        }
                        
                        // Sort by start time
                        $weeklyEvents = $weeklyEvents->sortBy('startDateTime')->values();
                        
                        // Group events by day of the week
                        $scheduleByDay = [];
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        
                        foreach ($days as $day) {
                            $scheduleByDay[$day] = $weeklyEvents->filter(function($event) use ($day) {
                                return strtolower($event->startDateTime->format('l')) === $day;
                            })->values();
                        }
                        
                        // Configuration options
                        $layout = $scheduleData['layout'] ?? 'grid'; // grid or list
                        $showDays = $scheduleData['show_days'] ?? $days;
                        $showInstructor = $scheduleData['show_instructor'] ?? true;
                        $showCapacity = $scheduleData['show_capacity'] ?? true;
                        $showBookButton = $scheduleData['show_book_button'] ?? true;
                        $bookButtonText = $scheduleData['book_button_text'] ?? 'Book Class';
                    @endphp
                    @include('partials.cms.sections.schedule', compact('section', 'page', 'isMeditative', 'isFitness', 'scheduleByDay', 'showDays', 'showInstructor', 'showCapacity', 'showBookButton', 'bookButtonText', 'layout'))
                    @break
                    
                @case('content')
                    @include('partials.cms.sections.content', compact('section', 'isMeditative', 'isFitness'))
                    @break
                    
                @case('heading')
                    @php
                        $headingSettings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
                        $headingFontSize = $headingSettings['content_font_size'] ?? '';
                        $headingStyle = '';
                        if (!empty($headingFontSize)) {
                            $numericValue = is_numeric($headingFontSize) ? (float) $headingFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $headingFontSize, $matches) ? (float) $matches[1] : null);
                            if ($numericValue && $numericValue >= 1) {
                                $headingStyle = 'font-size: ' . $numericValue . 'px; ';
                            }
                        }
                        // Always add black color to override parent section's color
                        $headingStyleWithColor = $headingStyle . 'color: #000000 !important;';
                    @endphp
                    @if($isFitness)
                        <div class="py-4 py-md-6">
                            <h2 class="section-heading text-center" style="{{ $headingStyleWithColor }}">{{ $section->content }}</h2>
                        </div>
                    @else
                        <div class="py-6 py-md-8">
                            <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 text-center" style="{{ $headingStyleWithColor }}">{{ $section->content }}</h2>
                    </div>
                    @endif
                    @break

                @case('paragraph')
                    @php
                        $paragraphSettings = is_string($section->settings) ? json_decode($section->settings, true) : ($section->settings ?? []);
                        $paragraphFontSize = $paragraphSettings['content_font_size'] ?? '';
                        $paragraphStyle = '';
                        if (!empty($paragraphFontSize)) {
                            $numericValue = is_numeric($paragraphFontSize) ? (float) $paragraphFontSize : (preg_match('/^([0-9]+(?:\.[0-9]+)?)/', $paragraphFontSize, $matches) ? (float) $matches[1] : null);
                            if ($numericValue && $numericValue >= 1) {
                                $paragraphStyle = 'font-size: ' . $numericValue . 'px;';
                            }
                        }
                    @endphp
                    @if($isFitness)
                        <div class="content-section" style="{{ $paragraphStyle }}">
                            {!! $section->content !!}
                        </div>
                    @else
                        <div class="max-w-none text-base md:text-lg leading-relaxed text-gray-700 ck-content" style="{{ $paragraphStyle }}">
                        {!! $section->content !!}
                    </div>
                    @endif
                    @break

                @case('quote')
                    @if($isFitness)
                        <blockquote class="blockquote-fitness">
                            <p class="quote-text-fitness">"{{ $section->content }}"</p>
                            @if($section->title)
                                <cite class="quote-cite-fitness">— {{ $section->title }}</cite>
                            @endif
                        </blockquote>
                    @else
                        <blockquote class="text-xl md:text-2xl lg:text-3xl italic text-gray-700 mb-4 mb-md-6">
                        "{{ $section->content }}"
                    </blockquote>
                    @if($section->title)
                            <cite class="text-base md:text-lg text-gray-600">— {{ $section->title }}</cite>
                        @endif
                    @endif
                    @break

                @case('list')
                    @php
                        $items = explode("\n", $section->content);
                        $items = array_filter(array_map('trim', $items));
                    @endphp
                    @if($isFitness)
                        <ul class="list-fitness">
                            @foreach($items as $item)
                                @php
                                    $item = preg_replace('/^[•\-\*]\s*/', '', $item);
                                @endphp
                                <li class="list-item-fitness">{{ $item }}</li>
                            @endforeach
                        </ul>
                    @else
                        <ul class="space-y-2 md:space-y-3 text-base md:text-lg">
                        @foreach($items as $item)
                            @php
                                $item = preg_replace('/^[•\-\*]\s*/', '', $item);
                            @endphp
                            <li class="flex items-start">
                                <span class="text-blue-600 mr-3 mt-1">•</span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @endif
                    @break

                @case('button')
                    @if($isFitness)
                        <div class="text-center my-3 my-md-4">
                            <a href="{{ $section->title ?: '#' }}" 
                               class="btn-fitness">
                                {{ $section->content ?: 'Click me' }}
                            </a>
                        </div>
                    @else
                        <div class="text-center my-4 my-md-6">
                    <a href="{{ $section->title ?: '#' }}" 
                               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 md:px-8 md:py-4 rounded-lg transition-colors text-sm md:text-base">
                        {{ $section->content ?: 'Click me' }}
                    </a>
                        </div>
                    @endif
                    @break

                @case('spacer')
                    @php
                        $height = (int)($section->content ?: 50);
                    @endphp
                    <div class="spacer-section" style="height: {{ $height }}px;"></div>
                    @break

                @case('code')
                    <pre class="bg-gray-900 text-green-400 p-6 rounded-lg overflow-x-auto"><code>{{ $section->content }}</code></pre>
                    @break

                @case('cta')
                    @php
                        $data = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        $buttons = $data['buttons'] ?? [];
                    @endphp
                    @if($isFitness)
                        <div class="cta-section-fitness text-center py-4 py-md-5">
                            @if($section->title)
                                <h2 class="section-heading mb-3 mb-md-4">{{ $section->title }}</h2>
                            @endif
                            @if($section->content)
                                <p class="cta-description-fitness mb-4 mb-md-5">{{ $section->content }}</p>
                            @endif
                            @if(!empty($buttons))
                                <div class="cta-buttons-fitness d-flex flex-wrap justify-content-center gap-3">
                                    @foreach($buttons as $button)
                                        @php
                                            $buttonClass = 'btn-fitness ';
                                            switch($button['style'] ?? 'primary') {
                                                case 'primary':
                                                    $buttonClass .= '';
                                                    break;
                                                case 'secondary':
                                                    $buttonClass .= 'btn-secondary-fitness';
                                                    break;
                                                case 'outline':
                                                    $buttonClass .= 'btn-outline-fitness';
                                                    break;
                                            }
                                        @endphp
                                        <a href="{{ $button['url'] ?? '#' }}" class="{{ $buttonClass }}">
                                            {{ $button['text'] ?? 'Button' }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-6 py-md-8">
                    @if($section->title)
                                <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold mb-3 mb-md-4">{{ $section->title }}</h2>
                    @endif
                    @if($section->content)
                                <p class="text-lg md:text-xl mb-6 mb-md-8 opacity-90">{{ $section->content }}</p>
                    @endif
                    @if(!empty($buttons))
                                <div class="d-flex flex-wrap justify-content-center gap-3 gap-md-4">
                            @foreach($buttons as $button)
                                @php
                                            $buttonClass = 'inline-block px-6 py-3 md:px-8 md:py-4 rounded-lg font-semibold transition-colors text-sm md:text-base ';
                                    switch($button['style'] ?? 'primary') {
                                        case 'primary':
                                            $buttonClass .= 'bg-white text-blue-600 hover:bg-gray-100';
                                            break;
                                        case 'secondary':
                                            $buttonClass .= 'bg-gray-600 text-white hover:bg-gray-700';
                                            break;
                                        case 'outline':
                                            $buttonClass .= 'border-2 border-white text-white hover:bg-white hover:text-blue-600';
                                            break;
                                    }
                                @endphp
                                <a href="{{ $button['url'] ?? '#' }}" class="{{ $buttonClass }}">
                                    {{ $button['text'] ?? 'Button' }}
                                </a>
                            @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                    @break

                @case('banner')
                    @php
                        // Get banner data from block
                        $bannerData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($bannerData)) {
                            $bannerData = [];
                        }
                        $bannerImages = $bannerData['images'] ?? [];
                        // Backward compatibility: if old format with image_url exists, convert it
                        if (empty($bannerImages) && !empty($bannerData['image_url'])) {
                            $bannerImages = [[
                                'url' => $bannerData['image_url'],
                                'alt_text' => $bannerData['alt_text'] ?? 'Banner image',
                                'link_url' => $bannerData['link_url'] ?? ''
                            ]];
                        }
                        $height = $bannerData['height'] ?? '300';
                        // Convert old height values (small, medium, etc.) to pixels
                        if (!is_numeric($height)) {
                            $heightMap = ['small' => '200', 'medium' => '300', 'large' => '400', 'xl' => '500'];
                            $height = $heightMap[$height] ?? '300';
                        }
                        $heightPixels = max(1, (int)$height); // Ensure minimum 1px
                        
                        // Generate unique carousel ID
                        $carouselId = 'banner-carousel-' . $section->id . '-' . uniqid();
                    @endphp
                    <div class="banner-section">
                        @if(!empty($bannerImages))
                            @if(count($bannerImages) === 1)
                                {{-- Single image --}}
                                @php $image = $bannerImages[0]; @endphp
                                @if(!empty($image['link_url']))
                                    <a href="{{ $image['link_url'] }}" class="block overflow-hidden">
                                        <img src="{{ $image['url'] }}" 
                                             alt="{{ $image['alt_text'] ?? 'Banner image' }}" 
                                             style="height: {{ $heightPixels }}px;"
                                             class="w-full object-cover hover:scale-105 transition-transform duration-300">
                                </a>
                            @else
                                <div class="overflow-hidden">
                                        <img src="{{ $image['url'] }}" 
                                             alt="{{ $image['alt_text'] ?? 'Banner image' }}" 
                                             style="height: {{ $heightPixels }}px;"
                                             class="w-full object-cover">
                                    </div>
                                @endif
                            @else
                                {{-- Multiple images - display as carousel slider --}}
                                <div id="{{ $carouselId }}" class="carousel slide carousel-fade" data-bs-ride="carousel" style="height: {{ $heightPixels }}px;">
                                    {{-- Carousel indicators --}}
                                    <ol class="carousel-indicators">
                                        @foreach($bannerImages as $idx => $image)
                                            <li data-bs-target="#{{ $carouselId }}" 
                                                data-bs-slide-to="{{ $idx }}" 
                                                class="{{ $idx === 0 ? 'active' : '' }}"
                                                aria-current="{{ $idx === 0 ? 'true' : 'false' }}"
                                                aria-label="Slide {{ $idx + 1 }}"></li>
                                        @endforeach
                                    </ol>
                                    
                                    {{-- Carousel inner --}}
                                    <div class="carousel-inner" style="height: {{ $heightPixels }}px;">
                                        @foreach($bannerImages as $idx => $image)
                                            <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}" style="height: {{ $heightPixels }}px;">
                                                @if(!empty($image['link_url']))
                                                    <a href="{{ $image['link_url'] }}" style="height: {{ $heightPixels }}px; display: block;">
                                                        <img src="{{ $image['url'] }}" 
                                                             alt="{{ $image['alt_text'] ?? 'Banner image' }}" 
                                                             style="height: {{ $heightPixels }}px; width: 100%; object-fit: cover;"
                                                             class="d-block">
                                                    </a>
                                                @else
                                                    <img src="{{ $image['url'] }}" 
                                                         alt="{{ $image['alt_text'] ?? 'Banner image' }}" 
                                                         style="height: {{ $heightPixels }}px; width: 100%; object-fit: cover;"
                                                         class="d-block">
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    
                                    {{-- Carousel controls --}}
                                    <button class="carousel-control-prev" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                            @endif
                        @else
                            <!-- Placeholder when no image is set -->
                            <div class="bg-gray-200 flex items-center justify-center" style="height: {{ $heightPixels }}px;">
                                <div class="text-center text-gray-500">
                                    <svg class="mx-auto h-16 w-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium">Banner Image</p>
                                    <p class="text-sm">Configure in CMS editor</p>
                                </div>
                            </div>
                        @endif
                    </div>
                    @break

                @case('image')
                    @php
                        // Get image data from block
                        $imageBlockData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($imageBlockData)) {
                            $imageBlockData = [];
                        }
                        $imageUrl = $imageBlockData['image_url'] ?? $section->content ?? '';
                        $altText = $imageBlockData['alt_text'] ?? 'Image';
                        $height = $imageBlockData['height'] ?? 'auto';
                        $width = $imageBlockData['width'] ?? 'full';
                        
                        // Convert height to CSS class
                        $heightClass = match($height) {
                            'small' => 'h-48 md:h-56',
                            'medium' => 'h-64 md:h-72',
                            'large' => 'h-80 md:h-96',
                            'xl' => 'h-96 md:h-[32rem]',
                            default => 'h-auto'
                        };
                        
                        // Convert width to CSS class
                        $widthClass = match($width) {
                            'small' => 'w-1/4',
                            'medium' => 'w-1/2',
                            'large' => 'w-3/4',
                            default => 'w-full'
                        };
                        
                        $containerClass = $width === 'full' ? 'w-full' : 'flex justify-center';
                    @endphp
                    @if($imageUrl)
                        <div class="{{ $containerClass }}">
                            <div class="{{ $widthClass }}">
                                <img src="{{ $imageUrl }}" 
                                     alt="{{ $altText }}" 
                                     height="400"
                                     class="w-full {{ $heightClass }} object-cover rounded-lg shadow-lg">
                                @if($section->title)
                                    <p class="text-center text-gray-600 mt-4 text-lg">{{ $section->title }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="bg-gray-200 rounded-lg p-12 mb-4 {{ $widthClass }} mx-auto">
                                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"></path>
                                </svg>
                                <p class="mt-4 text-gray-500">Image placeholder</p>
                            </div>
                            @if($section->title)
                                <p class="text-sm text-gray-600 italic">{{ $section->title }}</p>
                            @endif
                        </div>
                    @endif
                    @break

                @case('video')
                    @php
                        // Get video data
                        $videoData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($videoData)) {
                            $videoData = [];
                        }
                        $videoUrl = $videoData['video_url'] ?? $section->content ?? '';
                        $videoPath = $videoData['video_path'] ?? '';
                        $isUploaded = !empty($videoPath);
                        
                        // Check if it's a YouTube or Vimeo URL
                        $isYouTube = preg_match('/(youtube\.com|youtu\.be)/', $videoUrl);
                        $isVimeo = preg_match('/vimeo\.com/', $videoUrl);
                    @endphp
                    <div class="video-section py-12">
                        @if($section->title)
                            <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 text-center">{{ $section->title }}</h2>
                        @endif
                        
                        @if($videoUrl)
                            @if($isYouTube || $isVimeo)
                                <!-- YouTube/Vimeo Embed -->
                                <div class="aspect-video w-full max-w-4xl mx-auto">
                                    @if($isYouTube)
                                        @php
                                            // Extract YouTube video ID
                                            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $videoUrl, $matches);
                                            $youtubeId = $matches[1] ?? '';
                                        @endphp
                                        <iframe 
                                            class="w-full h-full rounded-lg"
                                            src="https://www.youtube.com/embed/{{ $youtubeId }}"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen
                                        ></iframe>
                                    @elseif($isVimeo)
                                        @php
                                            // Extract Vimeo video ID
                                            preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches);
                                            $vimeoId = $matches[1] ?? '';
                                        @endphp
                                        <iframe 
                                            class="w-full h-full rounded-lg"
                                            src="https://player.vimeo.com/video/{{ $vimeoId }}"
                                            frameborder="0"
                                            allow="autoplay; fullscreen; picture-in-picture"
                                            allowfullscreen
                                        ></iframe>
                                    @endif
                                </div>
                            @else
                                <!-- Direct Video File -->
                                <div class="w-full max-w-4xl mx-auto">
                                    <video 
                                        controls 
                                        class="w-full rounded-lg shadow-lg"
                                        preload="metadata"
                                    >
                                        <source src="{{ $isUploaded ? asset('storage/' . $videoPath) : $videoUrl }}" type="video/mp4">
                                        <source src="{{ $isUploaded ? asset('storage/' . $videoPath) : $videoUrl }}" type="video/webm">
                                        <source src="{{ $isUploaded ? asset('storage/' . $videoPath) : $videoUrl }}" type="video/ogg">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            @endif
                        @endif
                        
                        @if($section->subtitle)
                            <p class="text-gray-600 mt-4 text-center">{{ $section->subtitle }}</p>
                        @endif
                    </div>
                    @break

                @default
                    {{-- Default content section or fallback --}}
                    @include('partials.cms.fallback', compact('section', 'isMeditative', 'isFitness'))
            @endswitch
        </div>
    @endif
</div>
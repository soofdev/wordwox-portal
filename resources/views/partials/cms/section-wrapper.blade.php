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
    
    $topPadding = $spacing['padding_top'] ?? 'md';
    $bottomPadding = $spacing['padding_bottom'] ?? 'md';
    $sectionStyles[] = 'padding-top: ' . ($paddingMap[$topPadding] ?? $paddingMap['md']);
    $sectionStyles[] = 'padding-bottom: ' . ($paddingMap[$bottomPadding] ?? $paddingMap['md']);
    
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
    
    if (isset($typography['text_color'])) {
        $sectionStyles[] = 'color: ' . $typography['text_color'];
    }
    
    // Add template-specific classes
    $templateClasses = [];
    if ($isMeditative) {
        $templateClasses[] = 'ftco-section';
        if ($section->type !== 'hero') {
            $templateClasses[] = 'ftco-animate';
        }
    } elseif ($isFitness) {
        $templateClasses[] = 'section-padding';
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
                    @endphp
                    @include('partials.cms.sections.contact', compact('section', 'page', 'isMeditative', 'isFitness', 'currentTemplate', 'orgContact', 'formFields', 'submitText', 'successMessage', 'showContactInfo'))
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
                        
                        $layout = $packagesData['layout'] ?? 'grid';
                        $columns = $packagesData['columns'] ?? 3;
                        $showDescription = $packagesData['show_description'] ?? true;
                        $showPrograms = $packagesData['show_programs'] ?? true;
                        $buyButtonText = $packagesData['buy_button_text'] ?? 'Buy';
                        $purchaseAtGymText = $packagesData['purchase_at_gym_text'] ?? 'Purchase at the Gym';
                    @endphp
                    @include('partials.cms.sections.packages', compact('section', 'page', 'isMeditative', 'isFitness', 'plans', 'layout', 'columns', 'showDescription', 'showPrograms', 'buyButtonText', 'purchaseAtGymText'))
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
                        
                        // Get current week date range
                        $startOfWeek = now()->startOfWeek(); // Monday
                        $endOfWeek = now()->endOfWeek(); // Sunday
                        
                        // Fetch upcoming events (classes) from organization for the current week
                        $weeklyEvents = \App\Models\Event::where('org_id', $orgId)
                            ->where('isDeleted', false)
                            ->where('isCanceled', false)
                            ->whereBetween('startDateTime', [$startOfWeek, $endOfWeek])
                            ->with(['program', 'orgLocation'])
                            ->orderBy('startDateTime', 'asc')
                            ->get();
                        
                        // Group events by day of the week
                        $scheduleByDay = [];
                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        
                        foreach ($days as $day) {
                            $scheduleByDay[$day] = $weeklyEvents->filter(function($event) use ($day) {
                                return strtolower($event->startDateTime->format('l')) === $day;
                            })->values();
                        }
                        
                        // Configuration options
                        $showDays = $scheduleData['show_days'] ?? $days;
                        $showInstructor = $scheduleData['show_instructor'] ?? true;
                        $showCapacity = $scheduleData['show_capacity'] ?? true;
                        $showBookButton = $scheduleData['show_book_button'] ?? true;
                        $bookButtonText = $scheduleData['book_button_text'] ?? 'Book Class';
                    @endphp
                    @include('partials.cms.sections.schedule', compact('section', 'page', 'isMeditative', 'isFitness', 'scheduleByDay', 'showDays', 'showInstructor', 'showCapacity', 'showBookButton', 'bookButtonText'))
                    @break
                    
                @case('content')
                    @include('partials.cms.sections.content', compact('section', 'isMeditative', 'isFitness'))
                    @break
                    
                @case('heading')
                    <div class="heading-section py-8">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900">{{ $section->content }}</h2>
                    </div>
                    @break

                @case('paragraph')
                    <div class="max-w-none text-base leading-relaxed text-gray-700 ck-content">
                        {!! $section->content !!}
                    </div>
                    @break

                @case('quote')
                    <blockquote class="text-2xl md:text-3xl italic text-gray-700 mb-6">
                        "{{ $section->content }}"
                    </blockquote>
                    @if($section->title)
                        <cite class="text-lg text-gray-600">— {{ $section->title }}</cite>
                    @endif
                    @break

                @case('list')
                    @php
                        $items = explode("\n", $section->content);
                        $items = array_filter(array_map('trim', $items));
                    @endphp
                    <ul class="space-y-3 text-lg">
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
                    @break

                @case('button')
                    <a href="{{ $section->title ?: '#' }}" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-8 py-4 rounded-lg transition-colors">
                        {{ $section->content ?: 'Click me' }}
                    </a>
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
                    @if($section->title)
                        <h2 class="text-3xl md:text-4xl font-bold mb-4">{{ $section->title }}</h2>
                    @endif
                    @if($section->content)
                        <p class="text-xl mb-8 opacity-90">{{ $section->content }}</p>
                    @endif
                    @if(!empty($buttons))
                        <div class="flex flex-wrap justify-center gap-4">
                            @foreach($buttons as $button)
                                @php
                                    $buttonClass = 'inline-block px-8 py-4 rounded-lg font-semibold transition-colors ';
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
                    @break

                @case('banner')
                    @php
                        // Get banner data from block
                        $bannerData = is_string($section->data) ? json_decode($section->data, true) : ($section->data ?? []);
                        if (!is_array($bannerData)) {
                            $bannerData = [];
                        }
                        $bannerImageUrl = $bannerData['image_url'] ?? '';
                        $linkUrl = $bannerData['link_url'] ?? '';
                        $altText = $bannerData['alt_text'] ?? '';
                        $height = $bannerData['height'] ?? 'medium';
                        
                        $heightClass = match($height) {
                            'small' => 'h-48 md:h-56',
                            'medium' => 'h-64 md:h-72',
                            'large' => 'h-80 md:h-96',
                            'xl' => 'h-96 md:h-[32rem]',
                            default => 'h-64 md:h-72'
                        };
                        
                        $heightPixels = match($height) {
                            'small' => '200',
                            'medium' => '300', 
                            'large' => '400',
                            'xl' => '500',
                            default => '300'
                        };
                    @endphp
                    <div class="banner-section">
                        @if($bannerImageUrl)
                            @if($linkUrl)
                                <a href="{{ $linkUrl }}" class="block overflow-hidden">
                                    <img src="{{ $bannerImageUrl }}" 
                                         alt="{{ $altText ?: 'Banner image' }}" 
                                         height="{{ $heightPixels }}"
                                         class="w-full {{ $heightClass }} object-cover hover:scale-105 transition-transform duration-300">
                                </a>
                            @else
                                <div class="overflow-hidden">
                                    <img src="{{ $bannerImageUrl }}" 
                                         alt="{{ $altText ?: 'Banner image' }}" 
                                         height="{{ $heightPixels }}"
                                         class="w-full {{ $heightClass }} object-cover">
                                </div>
                            @endif
                        @else
                            <!-- Placeholder when no image is set -->
                            <div class="{{ $heightClass }} bg-gray-200 flex items-center justify-center">
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
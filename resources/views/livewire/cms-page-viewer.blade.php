<div>
    @if($page)
        {{-- Include SEO partial --}}
        @include('partials.cms.page-seo', compact('page'))

        @php
            $currentTemplate = $template ?? ($page->template ?? env('CMS_DEFAULT_THEME', 'fitness'));
            $isMeditative = $currentTemplate === 'meditative';
            $isFitness = $currentTemplate === 'fitness';
        @endphp
        
        <div class="cms-page" data-page-type="{{ $page->type }}" data-page-id="{{ $page->id }}" data-template="{{ $currentTemplate }}">
            
            {{-- Include Page Header partial (only show for non-home pages without hero sections) --}}
            @if($page->type !== 'home' && !$this->hasHeroSection())
                @include('partials.cms.page-header', compact('page', 'isMeditative', 'isFitness'))
            @endif

            {{-- Page Sections --}}
            @if($page->sections && $page->sections->count() > 0)
                <div class="cms-sections">
                    @foreach($page->sections->where('is_active', true) as $section)
                        @include('partials.cms.section-wrapper', compact('section', 'page', 'isMeditative', 'isFitness', 'currentTemplate'))
                    @endforeach
                </div>
            @else
                {{-- Fallback: Display page content as a section if no sections exist --}}
                @include('partials.cms.page-fallback', compact('page', 'isMeditative', 'isFitness'))
            @endif

            {{-- Add some basic styling --}}
            <style>
                .cms-page {
                    min-height: 50vh;
                }
                
                .container {
                    max-width: 1200px;
                }
                
                .prose {
                    color: #374151;
                    line-height: 1.75;
                }
                
                .prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
                    color: #111827;
                    font-weight: 600;
                }
                
                .prose p {
                    margin-bottom: 1.25em;
                }
                
                .prose ul, .prose ol {
                    margin: 1.25em 0;
                    padding-left: 1.625em;
                }
                
                .prose li {
                    margin: 0.5em 0;
                }
                
                .prose a {
                    color: #2563eb;
                    text-decoration: underline;
                }
                
                .prose a:hover {
                    color: #1d4ed8;
                }
            </style>
        </div>

    @else
        {{-- Page not found --}}
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="text-center">
                <div class="mb-8">
                    <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Page Not Found</h1>
                <p class="text-xl text-gray-600 mb-8">The requested page "{{ $slug }}" could not be found.</p>
                <div class="space-x-4">
                    <a href="/" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Go Home
                    </a>
                    <a href="/cms-admin/pages" class="inline-block border border-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-50 transition">
                        Manage Pages
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
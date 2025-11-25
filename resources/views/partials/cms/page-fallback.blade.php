{{-- Page Fallback Content Partial --}}
<div class="cms-sections">
    <div class="cms-section cms-section-content {{ $isMeditative ? 'ftco-section ftco-animate' : 'py-16' }}">
        <div class="{{ $isMeditative ? 'container' : 'container mx-auto px-6' }}">
            @if($page->title && !method_exists($this, 'hasHeroSection'))
                <div class="mb-8">
                    @if($isMeditative)
                        <div class="row justify-content-center mb-5 pb-3">
                            <div class="col-md-12 heading-section ftco-animate text-center">
                                <h1 class="mb-1">{{ $page->title }}</h1>
                                @if($page->description)
                                <p class="text-gray-600">{{ $page->description }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4 text-center">{{ $page->title }}</h1>
                        @if($page->description)
                            <p class="text-xl text-gray-600 max-w-3xl mx-auto text-center">{{ $page->description }}</p>
                        @endif
                    @endif
                </div>
            @endif
            
            @if($page->content)
                @if($isMeditative)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-gray-700">
                                {!! $page->content !!}
                            </div>
                        </div>
                    </div>
                @else
                    <div class="prose prose-lg max-w-none">
                        {!! $page->content !!}
                    </div>
                @endif
            @else
                <div class="text-center py-16">
                    <div class="{{ $isMeditative ? 'col-md-12' : 'max-w-2xl mx-auto' }}">
                        <svg class="mx-auto h-24 w-24 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Page Content Coming Soon</h2>
                        <p class="text-lg text-gray-600 mb-8">This page is currently being updated. Please check back soon.</p>
                        <a href="/cms-admin/pages/{{ $page->id }}/edit" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                            Edit This Page
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
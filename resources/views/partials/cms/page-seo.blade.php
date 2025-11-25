{{-- Page SEO Tags and Meta Information --}}
@if($page)
    {{-- Set page title and meta tags --}}
    @push('title')
        {{ $page->seo_title ?? $page->title }}
    @endpush

    @if($page->seo_description)
        @push('meta')
            <meta name="description" content="{{ $page->seo_description }}">
        @endpush
    @endif

    @if($page->seo_keywords)
        @push('meta')
            <meta name="keywords" content="{{ $page->seo_keywords }}">
        @endpush
    @endif

    {{-- Open Graph Meta Tags --}}
    @push('meta')
        <meta property="og:title" content="{{ $page->seo_title ?? $page->title }}">
        <meta property="og:description" content="{{ $page->seo_description ?? $page->description ?? '' }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        @if($page->featured_image)
        <meta property="og:image" content="{{ asset('storage/' . $page->featured_image) }}">
        @endif
        
        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $page->seo_title ?? $page->title }}">
        <meta name="twitter:description" content="{{ $page->seo_description ?? $page->description ?? '' }}">
        @if($page->featured_image)
        <meta name="twitter:image" content="{{ asset('storage/' . $page->featured_image) }}">
        @endif
        
        {{-- Structured Data (JSON-LD) --}}
        @php
            $jsonLd = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $page->title,
                'description' => $page->seo_description ?? $page->description ?? '',
                'url' => url()->current()
            ];
            if ($page->featured_image) {
                $jsonLd['image'] = asset('storage/' . $page->featured_image);
            }
        @endphp
        <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
        </script>
    @endpush
@endif
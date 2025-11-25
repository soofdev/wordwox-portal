{{-- Generic Content Section Partial --}}
@if($section->type === 'content')
    @if($isFitness)
        <div class="container my-5">
            @if($section->title)
            <div class="text-center mb-4">
                <h2 class="section-heading">{{ $section->title }}</h2>
                @if($section->subtitle)
                <p class="text-muted">{{ $section->subtitle }}</p>
                @endif
            </div>
            @endif
            
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    @if($section->content)
                    <div class="content-section">
                        {!! $section->content !!}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif($isMeditative)
        <section class="ftco-section">
            <div class="container">
                @if($section->title || $section->subtitle)
                <div class="row justify-content-center pb-5 mb-3">
                    <div class="col-md-7 heading-section text-center ftco-animate">
                        @if($section->title)
                        <h2>{{ $section->title }}</h2>
                        @endif
                        @if($section->subtitle)
                        <span class="subheading">{{ $section->subtitle }}</span>
                        @endif
                    </div>
                </div>
                @endif
                
                @if($section->content)
                <div class="row">
                    <div class="col-md-12">
                        <div class="content-section">
                            {!! $section->content !!}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </section>
    @else
        {{-- Default Content for Modern Template --}}
        <div class="max-w-4xl mx-auto">
            @if($section->title)
            <div class="text-center mb-12">
                <h2 class="text-4xl font-bold mb-4">{{ $section->title }}</h2>
                @if($section->subtitle)
                <p class="text-xl text-gray-600">{{ $section->subtitle }}</p>
                @endif
            </div>
            @endif
            
            @if($section->content)
            <div class="prose max-w-none">
                {!! $section->content !!}
            </div>
            @endif
        </div>
    @endif
@endif
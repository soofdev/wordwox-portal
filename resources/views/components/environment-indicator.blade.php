@php
$environment = app()->environment();
$envColors = [
'production' => 'bg-red-600 dark:bg-red-700',
'staging' => 'bg-yellow-500 dark:bg-yellow-600',
'development' => 'bg-teal-500 dark:bg-teal-600',
'local' => 'bg-teal-500 dark:bg-teal-600'
];

$color = $envColors[$environment] ?? $envColors['development'];
@endphp

@if(config('app.show_env_indicator', true))
<div class="fixed top-0 left-0 right-0 z-50 {{ $color }} h-1"></div>
@endif

@push('styles')
<style>
    /* Adjust body padding to account for environment strip */
    @if(config('app.show_env_indicator', true)) body {
        padding-top: 0.25rem;
        /* 4px / 16px = 0.25rem */
    }

    @endif

</style>
@endpush

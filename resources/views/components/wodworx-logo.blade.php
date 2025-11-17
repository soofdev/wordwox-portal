@props(['width' => 200, 'height' => 200])

<svg 
    width="{{ $width }}" 
    height="{{ $height }}" 
    viewBox="0 0 200 200" 
    xmlns="http://www.w3.org/2000/svg"
    {{ $attributes->merge(['class' => 'flex-shrink-0']) }}
>
    {{-- Black background square --}}
    <rect width="200" height="200" fill="currentColor" class="text-gray-900 dark:text-gray-900" />

    {{-- First rectangle --}}
    <rect x="25" y="50" width="40" height="100" fill="currentColor" class="text-gray-400 dark:text-gray-400" />
    <polygon points="25,50 65,150 25,150" fill="currentColor" class="text-white dark:text-white" />

    {{-- Second rectangle --}}
    <rect x="80" y="50" width="40" height="100" fill="currentColor" class="text-gray-400 dark:text-gray-400" />
    <polygon points="80,50 120,150 80,150" fill="currentColor" class="text-white dark:text-white" />

    {{-- Third rectangle --}}
    <rect x="135" y="50" width="40" height="100" fill="currentColor" class="text-gray-400 dark:text-gray-400" />
    <polygon points="135,50 175,150 135,150" fill="currentColor" class="text-white dark:text-white" />
</svg>

@props([
'href' => '#',
'class' => '',
])

<a href="{{ $href }}" {{ $attributes->merge([
        'class' => 'text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline transition-colors ' . $class
    ]) }}>
    {{ $slot }}
</a>

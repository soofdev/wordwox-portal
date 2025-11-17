@props([
'src' => null,
'name' => '',
'size' => 'md',
'alt' => null,
'clickable' => false,
'class' => ''
])

@php
$sizeClasses = [
'xs' => 'w-6 h-6',
'sm' => 'w-8 h-8',
'md' => 'w-10 h-10',
'lg' => 'w-12 h-12',
'xl' => 'w-16 h-16',
'2xl' => 'w-20 h-20',
'3xl' => 'w-24 h-24',
'4xl' => 'w-32 h-32',
][$size] ?? 'w-10 h-10';

$textSizeClasses = [
'xs' => 'text-xs',
'sm' => 'text-xs',
'md' => 'text-sm',
'lg' => 'text-base',
'xl' => 'text-lg',
'2xl' => 'text-xl',
'3xl' => 'text-2xl',
'4xl' => 'text-3xl',
][$size] ?? 'text-sm';

$initials = '';
if ($name) {
$names = explode(' ', trim($name));
foreach (array_slice($names, 0, 2) as $namePart) {
$initials .= strtoupper(substr($namePart, 0, 1));
}
}
$initials = $initials ?: 'U';

$altText = $alt ?? $name;
@endphp

@if($src)
@if($clickable)
<div class="cursor-pointer transition-all hover:opacity-80" onclick="openImageModal('{{ $src }}', '{{ $altText }}')">
    <img src="{{ $src }}" alt="{{ $altText }}" class="{{ $sizeClasses }} rounded-full object-cover border-2 border-gray-200 dark:border-gray-700 {{ $class }}" loading="lazy">
</div>
@else
<img src="{{ $src }}" alt="{{ $altText }}" class="{{ $sizeClasses }} rounded-full object-cover border-2 border-gray-200 dark:border-gray-700 {{ $class }}" loading="lazy">
@endif
@else
<div class="{{ $sizeClasses }} rounded-full bg-indigo-100 dark:bg-indigo-800 flex items-center justify-center border-2 border-gray-200 dark:border-gray-700 {{ $class }}">
    <span class="text-indigo-700 dark:text-indigo-300 font-medium {{ $textSizeClasses }}">
        {{ $initials }}
    </span>
</div>
@endif

@if($clickable)
@push('scripts')
<script>
    function openImageModal(src, alt) {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4';
        modal.onclick = () => modal.remove();

        // Create image container
        const container = document.createElement('div');
        container.className = 'max-w-4xl max-h-full bg-white rounded-lg overflow-hidden shadow-xl';
        container.onclick = (e) => e.stopPropagation();

        // Create image
        const img = document.createElement('img');
        img.src = src;
        img.alt = alt;
        img.className = 'w-full h-auto max-h-[80vh] object-contain';

        // Create close button
        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.className = 'absolute top-4 right-4 text-white text-2xl bg-black bg-opacity-50 rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-75';
        closeBtn.onclick = () => modal.remove();

        container.appendChild(img);
        modal.appendChild(container);
        modal.appendChild(closeBtn);
        document.body.appendChild(modal);
    }

</script>
@endpush
@endif

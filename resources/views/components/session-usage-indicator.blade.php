{{--
    Session Usage Indicator Component
    
    A reusable component that displays session usage with a circular progress indicator
    for limited sessions or an infinity symbol for unlimited sessions.
    
    Usage:
    <x-session-usage-indicator 
        :consumed="8"
        :remaining="12"
        :total="20"
        :percentage="40"
        size="md"
    />
    
    For unlimited sessions:
    <x-session-usage-indicator 
        :consumed="5"
        total="unlimited"
        size="md"
    />
    
    Props:
    - consumed (required): Number of sessions consumed/used
    - remaining (optional): Number of sessions remaining (not needed if unlimited)
    - total (required): Total sessions available or 'unlimited'
    - percentage (optional): Percentage used (0-100, not needed if unlimited)
    - size (optional): The size variant - 'sm' | 'md' | 'lg' (default: 'md')
    
    Progress Ring Colors:
    - Green: < 50% used
    - Yellow: 50-79% used
    - Red: ≥ 80% used
--}}

@props([
    'consumed' => 0,
    'remaining' => null,
    'total' => 0,
    'percentage' => 0,
    'size' => 'md', // sm, md, lg
])

@php
// Size configurations
$sizes = [
    'sm' => [
        'container' => 'gap-2 py-1',
        'ring' => 'w-12 h-12',
        'ringRadius' => 20,
        'ringStroke' => 4,
        'ringCenter' => 24,
        'infinity' => 'text-xl',
        'numbers' => 'text-base',
        'labels' => 'text-[10px]',
        'detailsGap' => 'gap-2'
    ],
    'md' => [
        'container' => 'gap-4 py-2',
        'ring' => 'w-16 h-16',
        'ringRadius' => 28,
        'ringStroke' => 6,
        'ringCenter' => 32,
        'infinity' => 'text-2xl',
        'numbers' => 'text-lg',
        'labels' => 'text-xs',
        'detailsGap' => 'gap-3'
    ],
    'lg' => [
        'container' => 'gap-5 py-3',
        'ring' => 'w-20 h-20',
        'ringRadius' => 36,
        'ringStroke' => 8,
        'ringCenter' => 40,
        'infinity' => 'text-3xl',
        'numbers' => 'text-xl',
        'labels' => 'text-sm',
        'detailsGap' => 'gap-4'
    ]
];

$config = $sizes[$size] ?? $sizes['md'];

// Determine if unlimited
$isUnlimited = $total === 'unlimited' || $total === 'Unlimited' || $total === 0;

// Calculate progress ring color based on percentage
if (!$isUnlimited) {
    $strokeColor = $percentage >= 80 ? 'text-red-500' : ($percentage >= 50 ? 'text-yellow-500' : 'text-green-500');
    $circumference = 2 * pi() * $config['ringRadius'];
    $progressOffset = $circumference - ($percentage / 100 * $circumference);
}
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center ' . $config['container']]) }}>
    @if($isUnlimited)
    <!-- Unlimited Sessions -->
    <div class="flex flex-col items-center">
        <div class="{{ $config['infinity'] }} font-black text-green-600 dark:text-green-400">
            ∞
        </div>
        <div class="{{ $config['labels'] }} font-medium text-green-600 dark:text-green-400 uppercase tracking-wide">
            {{ __('subscriptions.Unlimited') }}
        </div>
        <div class="{{ $config['labels'] }} text-zinc-500 dark:text-zinc-400 mt-1">
            {{ $consumed }} {{ __('subscriptions.used') }}
        </div>
    </div>
    @else
    <!-- Circular Progress Ring -->
    <div class="flex items-center {{ $config['detailsGap'] }}">
        <!-- SVG Circular Progress -->
        <div class="relative {{ $config['ring'] }}">
            <svg class="transform -rotate-90 {{ $config['ring'] }}">
                <!-- Background circle -->
                <circle 
                    cx="{{ $config['ringCenter'] }}" 
                    cy="{{ $config['ringCenter'] }}" 
                    r="{{ $config['ringRadius'] }}" 
                    stroke="currentColor" 
                    stroke-width="{{ $config['ringStroke'] }}" 
                    fill="none" 
                    class="text-zinc-200 dark:text-zinc-700" 
                />
                <!-- Progress circle -->
                <circle 
                    cx="{{ $config['ringCenter'] }}" 
                    cy="{{ $config['ringCenter'] }}" 
                    r="{{ $config['ringRadius'] }}" 
                    stroke="currentColor" 
                    stroke-width="{{ $config['ringStroke'] }}" 
                    fill="none" 
                    class="{{ $strokeColor }}"
                    stroke-dasharray="{{ $circumference }}"
                    stroke-dashoffset="{{ $progressOffset }}"
                    stroke-linecap="round"
                />
            </svg>
            <!-- Percentage in center -->
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="{{ $config['labels'] }} font-bold text-zinc-900 dark:text-white">
                    {{ number_format($percentage, 0) }}%
                </span>
            </div>
        </div>
        
        <!-- Usage Details -->
        <div class="flex flex-col">
            <div class="flex items-baseline gap-2">
                <span class="{{ $config['numbers'] }} font-bold text-red-600 dark:text-red-400">{{ $consumed }}</span>
                <span class="text-zinc-400 font-medium">/</span>
                <span class="{{ $config['numbers'] }} font-bold text-green-600 dark:text-green-400">{{ $remaining }}</span>
            </div>
            <div class="{{ $config['labels'] }} text-zinc-500 dark:text-zinc-400">
                {{ __('subscriptions.used') }} / {{ __('subscriptions.remaining') }}
            </div>
            <div class="{{ $config['labels'] }} font-medium text-zinc-600 dark:text-zinc-300 mt-1">
                {{ $total }} {{ __('subscriptions.total') }}
            </div>
        </div>
    </div>
    @endif
</div>


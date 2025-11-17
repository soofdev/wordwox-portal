{{--
    Date Range Timeline Component
    
    A reusable component that displays a start and end date in a visual timeline format
    with a color-coded end date indicator based on urgency status.
    
    Usage:
    <x-date-range-timeline 
        start-date="Sep 02, 2025"
        end-date="Nov 03, 2025"
        urgency-status="warning"
        size="md"
    />
    
    Props:
    - startDate (required): The start date string (formatted)
    - endDate (required): The end date string (formatted)
    - urgencyStatus (optional): The urgency level - 'healthy' | 'warning' | 'urgent' | 'expired' (default: 'healthy')
    - size (optional): The size variant - 'sm' | 'md' | 'lg' (default: 'md')
    
    Urgency Colors:
    - healthy: Blue dot (>30 days)
    - warning: Yellow dot (8-30 days)
    - urgent: Orange dot (≤7 days)
    - expired: Red dot (expired)
--}}

@props([
    'startDate',
    'endDate',
    'urgencyStatus' => 'healthy', // healthy, warning, urgent, expired
    'size' => 'md', // sm, md, lg
])

@php
// Size configurations
$sizes = [
    'sm' => [
        'icon' => 'w-4 h-4',
        'dot' => 'w-1.5 h-1.5',
        'label' => 'text-[10px]',
        'date' => 'text-xs',
        'arrow' => 'w-2.5 h-2.5',
        'line' => 'h-2',
        'gap' => 'gap-2',
        'spacing' => 'gap-1'
    ],
    'md' => [
        'icon' => 'w-5 h-5',
        'dot' => 'w-2 h-2',
        'label' => 'text-xs',
        'date' => 'text-sm',
        'arrow' => 'w-3 h-3',
        'line' => 'h-3',
        'gap' => 'gap-3',
        'spacing' => 'gap-1.5'
    ],
    'lg' => [
        'icon' => 'w-6 h-6',
        'dot' => 'w-2.5 h-2.5',
        'label' => 'text-sm',
        'date' => 'text-base',
        'arrow' => 'w-4 h-4',
        'line' => 'h-4',
        'gap' => 'gap-4',
        'spacing' => 'gap-2'
    ]
];

$config = $sizes[$size] ?? $sizes['md'];

// Urgency color mapping for end date dot
$urgencyColors = [
    'expired' => 'bg-red-500',
    'urgent' => 'bg-orange-500',
    'warning' => 'bg-yellow-500',
    'healthy' => 'bg-blue-500',
];

$endDotColor = $urgencyColors[$urgencyStatus] ?? $urgencyColors['healthy'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center ' . $config['gap'] . ' py-2']) }}>
    <!-- Calendar Icon -->
    <flux:icon name="calendar" class="{{ $config['icon'] }} text-zinc-400 flex-shrink-0" />
    
    <!-- Date Range with Visual Timeline -->
    <div class="flex flex-col {{ $config['spacing'] }}">
        <!-- Start Date -->
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5">
                <div class="{{ $config['dot'] }} rounded-full bg-green-500"></div>
                <div class="{{ $config['label'] }} text-zinc-500 dark:text-zinc-400 font-medium uppercase tracking-wide">
                    Start
                </div>
            </div>
            <div class="{{ $config['date'] }} font-semibold text-zinc-900 dark:text-white">
                {{ $startDate }}
            </div>
        </div>
        
        <!-- Visual Arrow/Connector -->
        <div class="flex items-center gap-2 ml-1">
            <div class="w-px {{ $config['line'] }} bg-zinc-300 dark:bg-zinc-600"></div>
            <flux:icon name="arrow-down" class="{{ $config['arrow'] }} text-zinc-400" />
        </div>
        
        <!-- End Date -->
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1.5">
                <div class="{{ $config['dot'] }} rounded-full {{ $endDotColor }}"></div>
                <div class="{{ $config['label'] }} text-zinc-500 dark:text-zinc-400 font-medium uppercase tracking-wide">
                    End
                </div>
            </div>
            <div class="{{ $config['date'] }} font-semibold text-zinc-900 dark:text-white">
                {{ $endDate }}
            </div>
        </div>
    </div>
</div>


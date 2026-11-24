@props([
    'mode' => 'SEA', // SEA, AIR, ROAD
    'size' => 'sm',  // 'xs', 'sm', 'md'
])

@php
    $normalizedMode = strtoupper((string) ($mode ?? 'SEA'));

    $config = match($normalizedMode) {
        'AIR' => [
            'label' => 'Air',
            'icon' => '✈️',
            'badgeClass' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30',
            'dotClass' => 'bg-sky-400',
        ],
        'ROAD' => [
            'label' => 'Road',
            'icon' => '🚚',
            'badgeClass' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
            'dotClass' => 'bg-emerald-400',
        ],
        default => [
            'label' => 'Sea',
            'icon' => '🚢',
            'badgeClass' => 'bg-blue-500/15 text-blue-700 dark:text-cyan-300 border-blue-500/30',
            'dotClass' => 'bg-blue-400 dark:bg-cyan-400',
        ],
    };

    $sizeClasses = match($size) {
        'xs' => 'px-2 py-0.5 text-[9px] gap-1',
        'md' => 'px-3 py-1 text-xs gap-1.5',
        default => 'px-2.5 py-0.5 text-[10px] gap-1.5',
    };
@endphp

<span {{ $attributes->merge(['class' => "rounded-full font-semibold border inline-flex items-center {$sizeClasses} {$config['badgeClass']} font-sans"]) }} title="Transport mode: {{ $config['label'] }}">
    <span class="text-[1.1em] leading-none" aria-hidden="true">{{ $config['icon'] }}</span>
    <span>{{ $config['label'] }}</span>
</span>

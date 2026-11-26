@props([
    'mode' => 'SEA', // SEA, AIR, ROAD
    'size' => 'sm',  // 'xs', 'sm', 'md'
])

@php
    $normalizedMode = strtoupper((string) ($mode ?? 'SEA'));

    $config = match($normalizedMode) {
        'AIR' => [
            'label' => 'Air',
            'badgeClass' => 'bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30',
            'dotClass' => 'bg-sky-400',
        ],
        'ROAD' => [
            'label' => 'Road',
            'badgeClass' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
            'dotClass' => 'bg-emerald-400',
        ],
        default => [
            'label' => 'Sea',
            'badgeClass' => 'bg-blue-500/15 text-blue-700 dark:text-cyan-300 border-blue-500/30',
            'dotClass' => 'bg-blue-400 dark:bg-cyan-400',
        ],
    };

    $sizeClasses = match($size) {
        'xs' => 'px-2 py-0.5 text-[9px] gap-1',
        'md' => 'px-3 py-1 text-xs gap-1.5',
        default => 'px-2.5 py-0.5 text-[10px] gap-1.5',
    };

    $iconSize = match($size) {
        'xs' => 'w-3 h-3',
        'md' => 'w-4 h-4',
        default => 'w-3.5 h-3.5',
    };
@endphp

<span {{ $attributes->merge(['class' => "rounded-full font-semibold border inline-flex items-center whitespace-nowrap {$sizeClasses} {$config['badgeClass']} font-sans"]) }} title="Transport mode: {{ $config['label'] }}">
    <x-mode-icon :mode="$normalizedMode" :class="$iconSize" />
    <span>{{ $config['label'] }}</span>
</span>

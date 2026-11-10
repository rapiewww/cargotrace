@props([
    'label',
    'value',
    'unit' => null,
    'trend' => null,
    'trendType' => 'neutral', // 'positive', 'negative', 'warning', 'neutral'
    'icon' => null,
    'pulse' => false,
    'color' => 'white', // 'white', 'teal', 'amber', 'emerald', 'rose'
])

@php
    $colorClasses = [
        'white' => 'theme-heading',
        'teal' => 'text-teal-500 dark:text-teal-400',
        'amber' => 'text-amber-500 dark:text-amber-400',
        'emerald' => 'text-emerald-500 dark:text-emerald-400',
        'rose' => 'text-rose-500 dark:text-rose-400',
    ][$color] ?? 'theme-heading';

    $trendBadge = match($trendType) {
        'positive' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border-emerald-500/30',
        'warning' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border-amber-500/30',
        'negative' => 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border-rose-500/30',
        default => 'bg-slate-500/10 text-slate-600 dark:text-slate-300 border-slate-400/20',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl theme-card border p-5 flex flex-col justify-between space-y-3 transition-all duration-200']) }}>
    <div class="flex items-center justify-between">
        <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold flex items-center gap-1.5">
            @if($pulse)
                <span class="w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse"></span>
            @endif
            {{ $label }}
        </span>
        @if($icon)
            <div class="theme-text-muted">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="flex items-baseline justify-between gap-2">
        <div class="flex items-baseline space-x-1.5">
            <span class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight {{ $colorClasses }}">{{ $value }}</span>
            @if($unit)
                <span class="text-xs theme-text-muted font-semibold">{{ $unit }}</span>
            @endif
        </div>
        @if($trend)
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono border {{ $trendBadge }}">
                {{ $trend }}
            </span>
        @endif
    </div>
</div>

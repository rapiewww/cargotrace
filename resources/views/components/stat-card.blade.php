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
        'white' => 'text-white',
        'teal' => 'text-teal-400',
        'amber' => 'text-amber-400',
        'emerald' => 'text-emerald-400',
        'rose' => 'text-rose-400',
    ][$color] ?? 'text-white';

    $trendBadge = match($trendType) {
        'positive' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
        'warning' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
        'negative' => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
        default => 'bg-white/5 text-slate-300 border-white/10',
    };
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-5 shadow-2xl flex flex-col justify-between space-y-3 hover:border-white/20 transition-all duration-200']) }}>
    <div class="flex items-center justify-between">
        <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold flex items-center gap-1.5">
            @if($pulse)
                <span class="w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse"></span>
            @endif
            {{ $label }}
        </span>
        @if($icon)
            <div class="text-slate-400">
                {{ $icon }}
            </div>
        @endif
    </div>

    <div class="flex items-baseline justify-between gap-2">
        <div class="flex items-baseline space-x-1.5">
            <span class="text-2xl sm:text-3xl font-extrabold font-mono tracking-tight {{ $colorClasses }}">{{ $value }}</span>
            @if($unit)
                <span class="text-xs text-slate-400 font-semibold">{{ $unit }}</span>
            @endif
        </div>
        @if($trend)
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border font-mono {{ $trendBadge }}">
                {{ $trend }}
            </span>
        @endif
    </div>
</div>

@props([
    'source' => 'SIMULATED', // 'LIVE', 'CACHED', 'SIMULATED'
    'type' => null, // e.g., 'OpenWeatherMap', 'FX Exchange', 'News Wire'
])

@php
    $style = match(strtoupper((string) $source)) {
        'LIVE' => ['class' => 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30', 'dot' => 'bg-emerald-500', 'label' => __('LIVE')],
        'CACHED' => ['class' => 'bg-teal-500/15 text-teal-700 dark:text-teal-300 border-teal-500/30', 'dot' => 'bg-teal-500', 'label' => __('CACHED')],
        default => ['class' => 'bg-amber-500/20 text-amber-900 dark:text-amber-200 border-dashed border-amber-500/70 shadow-sm', 'dot' => 'bg-amber-500', 'label' => __('SIMULATED')],
    };
@endphp

<span {{ $attributes->merge(['class' => "px-2 py-0.5 rounded-full text-[9px] font-mono font-bold tracking-wider uppercase border inline-flex items-center gap-1.5 {$style['class']}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }} {{ $style['label'] === 'LIVE' ? 'animate-pulse' : '' }}"></span>
    <span>{{ $style['label'] }}</span>
    @if($type)
        <span class="text-slate-400 font-sans font-normal">&bull; {{ $type }}</span>
    @endif
</span>


@props([
    'source' => 'SIMULATED', // 'LIVE', 'CACHED', 'SIMULATED'
    'type' => null, // e.g., 'OpenWeatherMap', 'FX Exchange', 'News Wire'
])

@php
    $style = match(strtoupper((string) $source)) {
        'LIVE' => ['class' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'dot' => 'bg-emerald-400', 'label' => 'LIVE'],
        'CACHED' => ['class' => 'bg-teal-500/15 text-teal-300 border-teal-500/30', 'dot' => 'bg-teal-400', 'label' => 'CACHED'],
        default => ['class' => 'bg-amber-500/15 text-amber-300 border-amber-500/30', 'dot' => 'bg-amber-400', 'label' => 'SIMULATED'],
    };
@endphp

<span {{ $attributes->merge(['class' => "px-2 py-0.5 rounded-full text-[9px] font-mono font-bold tracking-wider uppercase border inline-flex items-center gap-1.5 {$style['class']}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }} {{ $style['label'] === 'LIVE' ? 'animate-pulse' : '' }}"></span>
    <span>{{ $style['label'] }}</span>
    @if($type)
        <span class="text-slate-400 font-sans font-normal">&bull; {{ $type }}</span>
    @endif
</span>


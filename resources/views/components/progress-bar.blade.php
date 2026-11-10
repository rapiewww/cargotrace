@props([
    'percentage' => 50,
    'origin' => 'Origin',
    'destination' => 'Destination',
    'current' => null,
    'eta' => null,
    'delayed' => false,
])

<div class="space-y-2 w-full">
    <div class="flex items-center justify-between text-[11px] text-slate-400 font-mono">
        <span>From: <strong class="text-white">{{ $origin }}</strong></span>
        @if($eta)
            <span class="{{ $delayed ? 'text-amber-400 font-bold' : 'text-emerald-400' }}">ETA: {{ $eta }}</span>
        @endif
    </div>

    <div class="relative w-full h-2.5 rounded-full bg-slate-900 overflow-hidden border border-white/5">
        <div
            class="h-full rounded-full transition-all duration-500 {{ $delayed ? 'bg-gradient-to-r from-teal-400 via-amber-400 to-amber-500' : 'bg-gradient-to-r from-teal-400 to-emerald-400' }}"
            style="width: {{ max(5, min(100, $percentage)) }}%"
        ></div>
    </div>

    <div class="flex justify-between text-[10px] text-slate-500 font-mono">
        <span>{{ $origin }}</span>
        @if($current)
            <span class="text-amber-300 font-semibold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                {{ $current }}
            </span>
        @endif
        <span>{{ $destination }}</span>
    </div>
</div>

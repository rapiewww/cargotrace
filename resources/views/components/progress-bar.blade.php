@props([
    'percentage' => 50,
    'origin' => 'Origin',
    'destination' => 'Destination',
    'current' => null,
    'eta' => null,
    'delayed' => false,
])

<div class="space-y-2 w-full">
    <div class="flex items-center justify-between text-[11px] theme-text-muted font-mono">
        <span>From: <strong class="theme-heading">{{ $origin }}</strong></span>
        @if($eta)
            <span class="{{ $delayed ? 'text-amber-500 font-bold' : 'text-emerald-500' }}">ETA: {{ $eta }}</span>
        @endif
    </div>

    <div class="relative w-full h-2.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden border theme-border">
        <div
            class="h-full rounded-full transition-all duration-500 {{ $delayed ? 'bg-gradient-to-r from-blue-500 via-amber-400 to-amber-500' : 'bg-gradient-to-r from-blue-500 to-emerald-400' }}"
            style="width: {{ max(5, min(100, $percentage)) }}%"
        ></div>
    </div>

    <div class="flex justify-between text-[10px] theme-text-muted font-mono">
        <span>{{ $origin }}</span>
        @if($current)
            <span class="text-amber-600 dark:text-amber-400 font-semibold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                {{ $current }}
            </span>
        @endif
        <span>{{ $destination }}</span>
    </div>
</div>


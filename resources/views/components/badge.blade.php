@props([
    'variant' => 'default', // status: CREATED, BOOKED, PICKED_UP, IN_TRANSIT, DELIVERED; severity: LOW, MEDIUM, HIGH; or color: teal, emerald, amber, rose, slate
    'pulse' => false,
    'size' => 'sm', // 'xs', 'sm', 'md'
])

@php
    $style = match(strtoupper((string) $variant)) {
        'DELIVERED', 'RESOLVED', 'EMERALD', 'ON_TIME' => ['bg' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'dot' => 'bg-emerald-400'],
        'IN_TRANSIT', 'AMBER', 'WARNING', 'MEDIUM', 'DELAYED' => ['bg' => 'bg-amber-500/15 text-amber-300 border-amber-500/30', 'dot' => 'bg-amber-400'],
        'HIGH', 'ROSE', 'DANGER', 'EXCEPTION', 'CRITICAL' => ['bg-rose-500/15 text-rose-300 border-rose-500/30', 'dot' => 'bg-rose-400'],
        'BOOKED', 'PICKED_UP', 'TEAL', 'INFO', 'LOW' => ['bg' => 'bg-teal-500/15 text-teal-300 border-teal-500/30', 'dot' => 'bg-teal-400'],
        'CREATED', 'SLATE' => ['bg' => 'bg-slate-500/15 text-slate-300 border-slate-500/30', 'dot' => 'bg-slate-400'],
        default => ['bg' => 'bg-white/10 text-slate-200 border-white/15', 'dot' => 'bg-slate-300'],
    };

    $sizeClasses = match($size) {
        'xs' => 'px-2 py-0.5 text-[9px]',
        'md' => 'px-3 py-1 text-xs',
        default => 'px-2.5 py-0.5 text-[10px]',
    };
@endphp

<span {{ $attributes->merge(['class' => "rounded-full font-bold uppercase tracking-wider border inline-flex items-center gap-1.5 {$sizeClasses} {$style['bg']}"]) }}>
    @if($pulse || in_array(strtoupper((string)$variant), ['IN_TRANSIT', 'DELAYED', 'HIGH', 'CRITICAL']))
        <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }} animate-pulse"></span>
    @else
        <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }}"></span>
    @endif
    <span>{{ $slot }}</span>
</span>

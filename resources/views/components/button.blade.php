@props([
    'variant' => 'primary', // 'primary', 'secondary', 'ghost', 'danger', 'teal'
    'size' => 'md', // 'sm', 'md', 'lg'
    'type' => 'button',
    'href' => null,
])

@php
    $baseStyles = "inline-flex items-center justify-center font-bold tracking-wider uppercase transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none rounded-xl";

    $sizeStyles = match($size) {
        'sm' => 'px-3 py-1.5 text-[11px]',
        'lg' => 'px-6 py-3.5 text-sm',
        default => 'px-4 py-2 text-xs',
    };

    $variantStyles = match($variant) {
        'primary' => 'bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 shadow-lg shadow-amber-500/20',
        'secondary' => 'bg-slate-900/80 hover:bg-slate-800 text-slate-200 border border-slate-700/80 hover:text-white',
        'teal' => 'bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 shadow-lg shadow-teal-500/20',
        'danger' => 'bg-rose-500/15 hover:bg-rose-500/25 text-rose-300 border border-rose-500/30 hover:border-rose-500/50',
        'ghost' => 'bg-transparent hover:bg-white/5 text-slate-400 hover:text-white',
        default => 'bg-white/10 hover:bg-white/20 text-white',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "{$baseStyles} {$sizeStyles} {$variantStyles}"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "{$baseStyles} {$sizeStyles} {$variantStyles}"]) }}>
        {{ $slot }}
    </button>
@endif

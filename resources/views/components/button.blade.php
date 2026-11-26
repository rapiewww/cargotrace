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
        'primary' => 'bg-blue-600 hover:bg-blue-500 text-white shadow-md shadow-blue-500/20',
        'secondary' => 'theme-card-elevated hover:bg-slate-200 dark:hover:bg-slate-800 theme-text-primary border theme-border',
        'teal' => 'bg-cyan-600 hover:bg-cyan-500 text-white shadow-md shadow-cyan-500/20',
        'danger' => 'bg-rose-500/15 hover:bg-rose-500/25 text-rose-700 dark:text-rose-300 border border-rose-500/30 hover:border-rose-500/50',
        'ghost' => 'bg-transparent hover:bg-slate-500/10 theme-text-secondary hover:theme-heading',
        default => 'theme-card-elevated hover:bg-slate-200 dark:hover:bg-slate-800 theme-text-primary border theme-border',
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


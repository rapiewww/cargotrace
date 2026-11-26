@props([
    'type' => 'success', // 'success', 'error', 'info', 'warning'
    'message' => null,
])

@php
    $style = match($type) {
        'error', 'danger' => ['bg' => 'bg-rose-500/15 border-rose-500/30 text-rose-700 dark:text-rose-300', 'dot' => 'bg-rose-500 dark:bg-rose-400'],
        'warning' => ['bg' => 'bg-amber-500/15 border-amber-500/30 text-amber-800 dark:text-amber-300', 'dot' => 'bg-amber-500 dark:bg-amber-400'],
        'info' => ['bg' => 'bg-blue-500/15 border-blue-500/30 text-blue-700 dark:text-cyan-300', 'dot' => 'bg-blue-500 dark:bg-cyan-400'],
        default => ['bg' => 'bg-emerald-500/15 border-emerald-500/30 text-emerald-700 dark:text-emerald-300', 'dot' => 'bg-emerald-500 dark:bg-emerald-400'],
    };
@endphp

<div
    x-data="{ show: true }"
    x-show="show"
    {{ $attributes->merge(['class' => "p-4 rounded-2xl {$style['bg']} border text-xs flex items-center justify-between shadow-xl backdrop-blur-xl transition-all duration-300"]) }}
>
    <div class="flex items-center space-x-2.5">
        <span class="w-2 h-2 rounded-full {{ $style['dot'] }} animate-pulse"></span>
        <span class="font-medium">{{ $message ?? $slot }}</span>
    </div>
    <button type="button" onclick="this.closest('.p-4').remove()" class="text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white text-base leading-none pl-4 cursor-pointer" aria-label="{{ __('Dismiss') }}">&times;</button>
</div>


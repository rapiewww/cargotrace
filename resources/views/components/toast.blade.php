@props([
    'type' => 'success', // 'success', 'error', 'info', 'warning'
    'message' => null,
])

@php
    $style = match($type) {
        'error', 'danger' => ['bg' => 'bg-rose-500/15 border-rose-500/30 text-rose-300', 'dot' => 'bg-rose-400'],
        'warning' => ['bg' => 'bg-amber-500/15 border-amber-500/30 text-amber-300', 'dot' => 'bg-amber-400'],
        'info' => ['bg' => 'bg-teal-500/15 border-teal-500/30 text-teal-300', 'dot' => 'bg-teal-400'],
        default => ['bg' => 'bg-emerald-500/15 border-emerald-500/30 text-emerald-300', 'dot' => 'bg-emerald-400'],
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
    <button type="button" onclick="this.closest('.p-4').remove()" class="text-slate-400 hover:text-white text-base leading-none pl-4">&times;</button>
</div>


@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'required' => false,
    'error' => null,
    'hint' => null,
    'mono' => false,
])

@php
    $id = $id ?? $name ?? uniqid('select-');
    $font = $mono ? 'font-mono' : 'font-sans';
@endphp

<div class="space-y-1.5 w-full">
    @if($label)
        <label for="{{ $id }}" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
            {{ $label }} @if($required)<span class="text-amber-500">*</span>@endif
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if($required) required @endif
            {{ $attributes->merge(['class' => "w-full px-3.5 py-2.5 rounded-xl theme-input border text-xs sm:text-sm {$font} focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 focus:outline-none transition-all duration-200"]) }}
        >
            {{ $slot }}
        </select>
    </div>

    @if($error || ($name && $errors->has($name)))
        <p class="text-[11px] text-rose-500 mt-1">{{ $error ?? $errors->first($name) }}</p>
    @elseif($hint)
        <p class="text-[11px] theme-text-muted mt-1">{{ $hint }}</p>
    @endif
</div>

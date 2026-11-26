@props(['size' => 'md'])

@php
    $currentLocale = app()->getLocale();
    $heightClass = $size === 'sm' ? 'h-7 text-[11px]' : 'h-8 text-[11px]';
@endphp

<div
    role="group"
    aria-label="{{ __('Language switcher') }}"
    {{ $attributes->merge(['class' => "inline-flex items-center p-0.5 rounded-full bg-slate-200/90 dark:bg-slate-900/90 border border-slate-300 dark:border-slate-800 {$heightClass} transition-colors duration-200 shrink-0 backdrop-blur-md"]) }}
>
    <a
        href="{{ route('locale.switch', 'en') }}"
        lang="en"
        hreflang="en"
        @if($currentLocale === 'en') aria-current="true" @endif
        aria-label="{{ __('Switch language to English') }}"
        title="{{ __('English') }}"
        class="h-full px-2 sm:px-2.5 inline-flex items-center justify-center rounded-full font-bold tracking-wide transition-colors duration-150 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {{ $currentLocale === 'en' ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-700 hover:text-[#0B1728] dark:text-slate-300 dark:hover:text-white' }}"
    >
        EN
    </a>
    <a
        href="{{ route('locale.switch', 'id') }}"
        lang="id"
        hreflang="id"
        @if($currentLocale === 'id') aria-current="true" @endif
        aria-label="{{ __('Switch language to Indonesian') }}"
        title="{{ __('Indonesian') }}"
        class="h-full px-2 sm:px-2.5 inline-flex items-center justify-center rounded-full font-bold tracking-wide transition-colors duration-150 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 {{ $currentLocale === 'id' ? 'bg-[#2563EB] text-white shadow-sm' : 'text-slate-700 hover:text-[#0B1728] dark:text-slate-300 dark:hover:text-white' }}"
    >
        ID
    </a>
</div>

@extends('layouts.guest')
@section('has-locale-switcher', '1')

@section('content')
<div class="relative min-h-screen theme-page overflow-x-hidden">
    <!-- Enterprise Header -->
    <header class="sticky top-0 z-50 theme-header backdrop-blur-md border-b theme-border shadow-sm transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20 gap-2">
                <!-- Brand -->
                <div class="flex items-center space-x-2.5 sm:space-x-3.5 min-w-0">
                    <a href="{{ route('welcome') }}" class="flex items-center space-x-2.5 sm:space-x-3.5 group min-w-0">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-blue-600 flex items-center justify-center shadow-sm group-hover:bg-blue-700 transition-colors duration-200 shrink-0">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <span class="text-sm sm:text-xl font-extrabold tracking-tight theme-heading block whitespace-nowrap">{{ config('app.name', 'Nusa Freight') }}</span>
                            <span class="hidden sm:block text-[11px] theme-text-muted font-medium leading-tight">{{ __('Global spice freight by sea, air and road') }}</span>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden lg:flex items-center space-x-6 xl:space-x-7 text-xs font-medium theme-text-secondary" aria-label="{{ __('Main Navigation') }}">
                    <a href="#overview" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors whitespace-nowrap">{{ __('Platform') }}</a>
                    <a href="#modes" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors whitespace-nowrap">{{ __('Freight modes') }}</a>
                    <a href="#how-it-works" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors whitespace-nowrap">{{ __('How it works') }}</a>
                    <a href="#roles" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors whitespace-nowrap">{{ __('Roles') }}</a>
                    <a href="#timeline" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors whitespace-nowrap">{{ __('Timeline') }}</a>
                    <a href="#network" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors whitespace-nowrap">{{ __('Network map') }}</a>
                </nav>

                <!-- Actions: Theme Toggle, Locale Switcher, Portal Access & Mobile Menu -->
                <div class="flex items-center space-x-1.5 sm:space-x-2.5 shrink-0">
                    <!-- Theme Toggle Switcher (44px accessible hit height) -->
                    <div class="min-h-[44px] flex items-center">
                        <button
                            type="button"
                            id="welcome-theme-toggle"
                            role="switch"
                            aria-checked="true"
                            aria-pressed="true"
                            tabindex="0"
                            onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
                            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.CargoTrace&&window.CargoTrace.toggleTheme?window.CargoTrace.toggleTheme():null}"
                            class="relative flex items-center p-1 rounded-full bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 w-12 sm:w-14 h-8 transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 cursor-pointer shrink-0"
                            aria-label="{{ __('Toggle dark and light theme') }}"
                            title="{{ __('Switch theme') }}"
                        >
                            <span class="w-5 h-5 flex items-center justify-center text-slate-500 dark:text-cyan-400 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                            </span>
                            <span class="w-5 h-5 flex items-center justify-center text-amber-600 dark:text-slate-500 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </span>
                        </button>
                    </div>

                    <!-- Language Switcher (EN | ID) -->
                    <x-locale-switcher size="sm" />

                    @auth
                        <a href="{{ route('dashboard') }}" aria-label="{{ __('Sign In / Portal') }}" class="hidden sm:inline-flex min-h-[44px] px-4 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm transition-colors duration-200 items-center gap-1.5 whitespace-nowrap">
                            <span>{{ __('Open the portal') }}</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" aria-label="{{ __('Sign In / Portal') }}" class="hidden sm:inline-flex min-h-[44px] px-4 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-sm transition-colors duration-200 items-center gap-1.5 whitespace-nowrap">
                            <span>{{ __('Open the portal') }}</span>
                        </a>
                    @endauth

                    <!-- Mobile Menu Toggle -->
                    <button
                        type="button"
                        id="welcome-mobile-menu-toggle"
                        aria-expanded="false"
                        aria-controls="welcome-mobile-nav"
                        aria-label="{{ __('Open navigation menu') }}"
                        class="lg:hidden inline-flex items-center justify-center gap-1.5 min-h-[44px] px-2.5 rounded-xl border theme-border theme-card-elevated theme-heading text-xs font-semibold hover:border-blue-500 transition-colors cursor-pointer"
                    >
                        <svg data-menu-icon="open" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg data-menu-icon="close" class="w-4 h-4 shrink-0 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <span>{{ __('Menu') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Expandable Mobile Navigation Drawer -->
        <nav id="welcome-mobile-nav" class="hidden lg:hidden border-t theme-border theme-header px-4 py-4 space-y-1.5" aria-label="{{ __('Main Navigation') }}">
            <a href="#overview" class="flex items-center min-h-[44px] px-3 rounded-lg text-sm font-medium theme-text-primary hover:bg-blue-500/10 transition-colors">{{ __('Platform') }}</a>
            <a href="#modes" class="flex items-center min-h-[44px] px-3 rounded-lg text-sm font-medium theme-text-primary hover:bg-blue-500/10 transition-colors">{{ __('Freight modes') }}</a>
            <a href="#how-it-works" class="flex items-center min-h-[44px] px-3 rounded-lg text-sm font-medium theme-text-primary hover:bg-blue-500/10 transition-colors">{{ __('How it works') }}</a>
            <a href="#roles" class="flex items-center min-h-[44px] px-3 rounded-lg text-sm font-medium theme-text-primary hover:bg-blue-500/10 transition-colors">{{ __('Roles') }}</a>
            <a href="#timeline" class="flex items-center min-h-[44px] px-3 rounded-lg text-sm font-medium theme-text-primary hover:bg-blue-500/10 transition-colors">{{ __('Timeline') }}</a>
            <a href="#network" class="flex items-center min-h-[44px] px-3 rounded-lg text-sm font-medium theme-text-primary hover:bg-blue-500/10 transition-colors">{{ __('Network map') }}</a>
            <div class="pt-2 border-t theme-border">
                @auth
                    <a href="{{ route('dashboard') }}" class="flex items-center justify-center min-h-[44px] w-full px-4 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        {{ __('Open the portal') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex items-center justify-center min-h-[44px] w-full px-4 py-2.5 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        {{ __('Open the portal') }}
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    @php
        $feat = $featuredShipment ?? $activeShipments->first();
        $heroDarkImage = config('cargotrace.hero_images.dark')
            ?: (file_exists(public_path('images/backgrounds/hero-multimodal-dark.jpg'))
                ? 'images/backgrounds/hero-multimodal-dark.jpg'
                : 'images/backgrounds/bg-dark.webp');
        $heroLightImage = config('cargotrace.hero_images.light')
            ?: (file_exists(public_path('images/backgrounds/hero-multimodal-light.jpg'))
                ? 'images/backgrounds/hero-multimodal-light.jpg'
                : (file_exists(public_path('images/backgrounds/hero-light.webp'))
                    ? 'images/backgrounds/hero-light.webp'
                    : (file_exists(public_path('images/backgrounds/bg-light.webp'))
                        ? 'images/backgrounds/bg-light.webp'
                        : null)));
        $heroDarkIsMultimodal = str_contains($heroDarkImage, 'hero-multimodal');
        $heroLightIsMultimodal = $heroLightImage && str_contains($heroLightImage, 'hero-multimodal');

        $formatHours = static function (float|int $hours): string {
            return fmod((float) $hours, 1.0) === 0.0
                ? (string) (int) $hours
                : number_format((float) $hours, 1);
        };
        $seaLow = $formatHours($modeThresholds['SEA']['low'] ?? 12);
        $seaMed = $formatHours($modeThresholds['SEA']['medium'] ?? 48);
        $airLow = $formatHours($modeThresholds['AIR']['low'] ?? 3);
        $airMed = $formatHours($modeThresholds['AIR']['medium'] ?? 12);
        $roadLow = $formatHours($modeThresholds['ROAD']['low'] ?? 6);
        $roadMed = $formatHours($modeThresholds['ROAD']['medium'] ?? 24);
    @endphp

    <main>
    <!-- 1. MULTIMODAL HERO SECTION -->
    <section class="relative min-h-[560px] sm:min-h-[660px] lg:min-h-[760px] flex items-center overflow-hidden border-b theme-border bg-[#EEF5FB] dark:bg-[#060D1A] transition-colors duration-300 ease-in-out">
        <!-- Background Imagery: Both themes share identical positioned box and crossfade via opacity -->
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="hero-artwork-frame absolute inset-0 overflow-hidden lg:inset-y-0 lg:left-auto lg:right-0 lg:h-full lg:w-auto lg:aspect-[2752/1536]">
                <div class="hero-artwork-stage absolute inset-0">
                    {{-- Dark Theme Background Photography --}}
                    <img
                        src="{{ asset($heroDarkImage) }}"
                        alt="Multimodal spice freight terminal at night with container ship, cargo aircraft, and haulage truck"
                        width="{{ $heroDarkIsMultimodal ? 2752 : 2560 }}"
                        height="{{ $heroDarkIsMultimodal ? 1536 : 1440 }}"
                        fetchpriority="high"
                        loading="eager"
                        decoding="sync"
                        class="absolute inset-0 w-full h-full object-cover [object-position:76%_center] lg:object-contain lg:object-right opacity-0 dark:opacity-100 transition-opacity duration-300 ease-in-out filter brightness-100 contrast-100"
                    >

                    {{-- Light Theme Background Photography --}}
                    @if($heroLightImage)
                        <img
                            src="{{ asset($heroLightImage) }}"
                            alt="Daytime multimodal freight operations showing container vessel, cargo plane, and road transport"
                            width="{{ $heroLightIsMultimodal ? 2752 : 2560 }}"
                            height="{{ $heroLightIsMultimodal ? 1536 : 1440 }}"
                            fetchpriority="high"
                            loading="eager"
                            decoding="sync"
                            class="absolute inset-0 w-full h-full object-cover [object-position:76%_center] lg:object-contain lg:object-right {{ $heroLightIsMultimodal ? 'hero-light-aligned' : '' }} opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out filter brightness-100 contrast-100"
                        >
                    @else
                        <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-[#EEF5FB] via-sky-50 to-slate-200 opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out">
                            <x-svg-network-motif class="text-slate-500 opacity-10" />
                        </div>
                    @endif
                </div>
            </div>

            <!-- Dark Theme Gradient Scrim -->
            <div class="absolute inset-0 opacity-0 dark:opacity-100 transition-opacity duration-300 ease-in-out" style="background: linear-gradient(90deg, rgba(6, 13, 26, 0.96) 0%, rgba(6, 13, 26, 0.86) 28%, rgba(6, 13, 26, 0.40) 46%, rgba(6, 13, 26, 0) 58%), linear-gradient(180deg, rgba(6, 13, 26, 0.22) 0%, rgba(6, 13, 26, 0) 80px);"></div>
            <div class="absolute inset-0 lg:hidden bg-[#060D1A]/60 opacity-0 dark:opacity-100 transition-opacity duration-300 ease-in-out"></div>

            <!-- Light Theme Gradient Scrim -->
            <div class="absolute inset-0 opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out" style="background: linear-gradient(90deg, rgba(238, 245, 251, 0.97) 0%, rgba(238, 245, 251, 0.88) 28%, rgba(238, 245, 251, 0.42) 46%, rgba(238, 245, 251, 0) 58%), linear-gradient(180deg, rgba(238, 245, 251, 0.25) 0%, rgba(238, 245, 251, 0) 80px);"></div>
            <div class="absolute inset-0 lg:hidden bg-[#EEF5FB]/70 opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out"></div>
        </div>

        <div class="relative z-10 w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-14 md:py-20 lg:py-28">
            <div class="max-w-[36rem] space-y-6">
                <p class="text-xs font-semibold text-blue-700 dark:text-cyan-300 tracking-wide">
                    {{ __('Multimodal spice and essential oil export tracking') }}
                </p>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-[#0B1728] dark:text-white leading-[1.08]">
                    {{ __('Follow every consignment') }}<br>
                    <span class="text-[#2563EB] dark:text-cyan-400">
                        {{ __('across sea, air and road.') }}
                    </span>
                </h1>

                <p class="text-sm sm:text-lg text-slate-700 dark:text-slate-200 leading-relaxed">
                    {{ __('Track nutmeg, cloves, patchouli oil and more from Indonesian origins to buyers worldwide.') }}
                </p>

                <!-- Instant Tracking Search Bar -->
                <form
                    action="javascript:void(0)"
                    onsubmit="if(window.handlePublicTrack){window.handlePublicTrack();}else{var q=document.getElementById('public-track-input').value.trim();if(q){window.location.href='/track/'+encodeURIComponent(q);}}"
                    class="pt-1 space-y-2"
                    novalidate
                >
                    <label for="public-track-input" class="block text-xs font-semibold text-slate-700 dark:text-slate-200">
                        {{ __('Track by consignment, container, AWB or plate') }}
                    </label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 dark:text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input
                                type="text"
                                id="public-track-input"
                                aria-describedby="public-track-error"
                                placeholder="{{ __('Tracking number, e.g. :example', ['example' => $feat->tracking_number ?? 'CTG-SEA-2026-00244']) }}"
                                class="w-full min-h-[44px] pl-10 pr-4 py-3 rounded-xl bg-white/95 dark:bg-[#0B1728]/95 border border-[#D5DEEA] dark:border-white/15 text-[#0B1728] dark:text-[#E6EEF8] placeholder-slate-500 dark:placeholder-slate-400 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 shadow-sm"
                            >
                        </div>
                        <button
                            type="submit"
                            aria-label="{{ __('Track Shipment') }}"
                            class="min-h-[44px] px-5 py-3 rounded-xl bg-[#2563EB] hover:bg-[#1D4ED8] active:bg-blue-800 text-white font-semibold text-xs transition-colors shadow-sm whitespace-nowrap cursor-pointer flex items-center justify-center"
                        >
                            <span>{{ __('Track a shipment') }}</span>
                        </button>
                    </div>
                    <p id="public-track-error" role="alert" class="hidden text-xs font-medium text-rose-700 dark:text-rose-400">
                        {{ __('Please enter a tracking number, container, AWB, or vehicle plate.') }}
                    </p>
                </form>

                <!-- Sample Consignment Quick Links -->
                <div class="pt-1 space-y-2">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                        <span class="text-slate-700 dark:text-slate-300 font-medium">{{ __('Sample consignments:') }}</span>
                        <a href="{{ url('/track/CTG-SEA-2026-00189') }}" class="font-mono text-blue-700 dark:text-cyan-400 hover:underline inline-flex items-center min-h-[44px] sm:min-h-0 py-1">CTG-SEA-2026-00189</a>
                        <a href="{{ url('/track/CTG-SEA-2026-00244') }}" class="font-mono text-blue-700 dark:text-cyan-400 hover:underline inline-flex items-center min-h-[44px] sm:min-h-0 py-1">CTG-SEA-2026-00244</a>
                        <a href="{{ url('/track/CTG-AIR-2026-00678') }}" class="font-mono text-blue-700 dark:text-cyan-400 hover:underline inline-flex items-center min-h-[44px] sm:min-h-0 py-1">CTG-AIR-2026-00678</a>
                    </div>
                    <a href="#overview" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-cyan-400 hover:underline py-1">
                        <span>{{ __('See how Nusa Freight works') }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. LIVE OPERATIONAL METRICS STRIP -->
    <section class="theme-card-elevated border-b theme-border py-7 sm:py-9 transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-y-6 md:gap-y-0 md:divide-x theme-border text-center">
                <div class="px-3 sm:px-4">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold theme-heading tracking-tight font-mono tabular-nums">{{ $locations->count() }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('Locations in database') }}</div>
                </div>
                <div class="px-3 sm:px-4">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-blue-700 dark:text-cyan-400 tracking-tight font-mono tabular-nums">{{ $onTimePercentage }}%</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('On-time rate') }}</div>
                </div>
                <div class="px-3 sm:px-4">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold theme-heading tracking-tight font-mono tabular-nums">{{ $carriers->count() }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('Carriers') }}</div>
                </div>
                <div class="px-3 sm:px-4">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-blue-700 dark:text-cyan-400 tracking-tight font-mono tabular-nums">{{ number_format($totalShipments) }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('Shipments tracked to date') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. WHAT IS NUSA FREIGHT? (Platform Overview: Asymmetric 12-Col Split + Unified 2x2 Capability Ledger) -->
    <section id="overview" class="py-12 sm:py-16 lg:py-22 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                <!-- Left Editorial Summary -->
                <div class="lg:col-span-5 space-y-5">
                    <p class="text-xs font-semibold text-blue-700 dark:text-cyan-400">{{ __('Platform overview') }}</p>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight theme-heading leading-tight">
                        {{ __('What is Nusa Freight?') }}
                    </h2>
                    <p class="text-sm sm:text-base theme-text-secondary leading-relaxed">
                        {{ __('Nusa Freight is an end-to-end multimodal freight visibility platform built for Indonesian spice and essential oil exporters, logistics operators, and international buyers.') }}
                    </p>
                    <p class="text-sm theme-text-secondary leading-relaxed">
                        {{ __('It unifies sea, air, and road shipment tracking with rule-based ETA evaluation, port and hub weather, daily ECB reference FX valuation, and freight disruption bulletins.') }}
                    </p>
                    <div class="pt-2 space-y-2.5">
                        <p class="text-xs font-semibold theme-heading">{{ __('Export commodities') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium theme-card border theme-border theme-text-primary">{{ __('Nutmeg') }}</span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium theme-card border theme-border theme-text-primary">{{ __('Cloves') }}</span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium theme-card border theme-border theme-text-primary">{{ __('Patchouli oil') }}</span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium theme-card border theme-border theme-text-primary">{{ __('Cassia') }}</span>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-medium theme-card border theme-border theme-text-primary">{{ __('Vanilla') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right Unified Capability Ledger (2x2 Architectural Panel) -->
                <div class="lg:col-span-7">
                    <div class="rounded-2xl theme-card border theme-border overflow-hidden grid grid-cols-1 sm:grid-cols-2">
                        <!-- Capability 1 -->
                        <div class="p-6 sm:p-7 space-y-2.5 border-b theme-border sm:border-r">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-blue-500/10 text-blue-700 dark:text-cyan-400 border border-blue-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <h3 class="text-sm sm:text-base font-bold theme-heading">{{ __('Automated ETA and delay rules') }}</h3>
                            </div>
                            <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                                {{ __('Evaluates milestone timestamps against mode-specific hour thresholds and tracks the :days-day terminal free-time dwell window.', ['days' => $freeTimeDays]) }}
                            </p>
                        </div>

                        <!-- Capability 2 -->
                        <div class="p-6 sm:p-7 space-y-2.5 border-b theme-border">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-700 dark:text-sky-400 border border-sky-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                                </span>
                                <h3 class="text-sm sm:text-base font-bold theme-heading">{{ __('Port, airport and depot weather') }}</h3>
                            </div>
                            <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                                {{ __('Fetches Open-Meteo atmospheric conditions, quay crane wind advisories, and seaport wave metrics as operational context.') }}
                            </p>
                        </div>

                        <!-- Capability 3 -->
                        <div class="p-6 sm:p-7 space-y-2.5 border-b sm:border-b-0 sm:border-r theme-border">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </span>
                                <h3 class="text-sm sm:text-base font-bold theme-heading">{{ __('Multi-currency FX valuation') }}</h3>
                            </div>
                            <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                                {{ __('Converts declared manifest values across currencies using daily ECB reference rates via Frankfurter.') }}
                            </p>
                        </div>

                        <!-- Capability 4 -->
                        <div class="p-6 sm:p-7 space-y-2.5">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-lg bg-amber-500/10 text-amber-800 dark:text-amber-400 border border-amber-500/25 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </span>
                                <h3 class="text-sm sm:text-base font-bold theme-heading">{{ __('Freight disruption bulletins') }}</h3>
                            </div>
                            <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                                {{ __('Aggregates sea, air, and road RSS wire bulletins alongside operator-logged customs holds and port incidents.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. MULTIMODAL TRANSPORT CORRIDORS (Sea / Air / Road + Mode-Specific Identifiers & Thresholds) -->
    <section id="modes" class="py-12 sm:py-16 lg:py-20 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="max-w-2xl space-y-2">
                    <p class="text-xs font-semibold text-blue-700 dark:text-cyan-400">{{ __('Multimodal freight coverage') }}</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading">
                        {{ __('Three transport corridors for Indonesian spice exports') }}
                    </h2>
                    <p class="text-sm theme-text-secondary leading-relaxed">
                        {{ __('Every shipment carries mode-specific identifiers, location codes, and delay thresholds tailored to ocean, air, and overland spice trade legs.') }}
                    </p>
                </div>
                <a href="#network" class="text-xs font-semibold text-blue-700 dark:text-cyan-400 hover:underline inline-flex items-center gap-1.5 py-2 self-start md:self-auto">
                    <span>{{ __('Inspect live route map') }}</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="rounded-2xl theme-card border theme-border overflow-hidden grid grid-cols-1 lg:grid-cols-3 divide-y lg:divide-y-0 lg:divide-x theme-border">
                <!-- Sea Freight (Primary Export Corridor) -->
                <div class="p-6 sm:p-8 flex flex-col justify-between gap-5 bg-blue-500/[0.03] dark:bg-blue-500/[0.05]">
                    <div class="space-y-3.5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/15 text-blue-700 dark:text-cyan-400 border border-blue-500/25 flex items-center justify-center shrink-0">
                                    <x-mode-icon mode="SEA" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Sea Freight') }}</h3>
                                    <span class="text-[11px] font-mono theme-text-muted">ISO 6346 &bull; B/L &bull; UN/LOCODE</span>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-md bg-blue-600/15 text-blue-700 dark:text-cyan-300 border border-blue-500/25 whitespace-nowrap">
                                {{ __('Primary export') }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Containerized nutmeg, cloves, cassia, and black pepper moving from Belawan and Tanjung Priok to Rotterdam, Hamburg, Jebel Ali, and US East Coast ports.') }}
                        </p>
                    </div>
                    <div class="space-y-3 pt-4 border-t theme-border">
                        <div class="flex flex-col gap-1 text-xs">
                            <span class="theme-text-muted font-medium">{{ __('Delay thresholds') }}</span>
                            <span class="font-mono text-[11px] theme-heading tabular-nums">
                                {{ __('Low :low h • Medium :medium h • High > :medium h', ['low' => $seaLow, 'medium' => $seaMed]) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-2 border-t theme-border">
                            <span class="theme-text-muted font-medium">{{ __('Database volume') }}</span>
                            <span class="font-mono font-bold text-blue-700 dark:text-cyan-400 tabular-nums">{{ __(':count consignments', ['count' => number_format($modeTotals['SEA'] ?? 0)]) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Air Freight -->
                <div class="p-6 sm:p-8 flex flex-col justify-between gap-5">
                    <div class="space-y-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-700 dark:text-sky-400 border border-sky-500/25 flex items-center justify-center shrink-0">
                                <x-mode-icon mode="AIR" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Air Freight') }}</h3>
                                <span class="text-[11px] font-mono theme-text-muted">IATA &bull; {{ __('11-digit AWB') }}</span>
                            </div>
                        </div>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Temperature-sensitive patchouli, clove bud, and nutmeg essential oils routed through Kualanamu and Soekarno-Hatta to Paris CDG, Schiphol, and JFK.') }}
                        </p>
                    </div>
                    <div class="space-y-3 pt-4 border-t theme-border">
                        <div class="flex flex-col gap-1 text-xs">
                            <span class="theme-text-muted font-medium">{{ __('Delay thresholds') }}</span>
                            <span class="font-mono text-[11px] theme-heading tabular-nums">
                                {{ __('Low :low h • Medium :medium h • High > :medium h', ['low' => $airLow, 'medium' => $airMed]) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-2 border-t theme-border">
                            <span class="theme-text-muted font-medium">{{ __('Database volume') }}</span>
                            <span class="font-mono font-bold text-sky-700 dark:text-sky-400 tabular-nums">{{ __(':count consignments', ['count' => number_format($modeTotals['AIR'] ?? 0)]) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Road Freight -->
                <div class="p-6 sm:p-8 flex flex-col justify-between gap-5">
                    <div class="space-y-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/25 flex items-center justify-center shrink-0">
                                <x-mode-icon mode="ROAD" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Road Freight') }}</h3>
                                <span class="text-[11px] font-mono theme-text-muted">{{ __('Vehicle plate') }} &bull; CMR &bull; {{ __('Depots') }}</span>
                            </div>
                        </div>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('First-mile Sumatra plantation hauls from Lhokseumawe and Banda Aceh to Medan hubs, plus European last-mile deliveries to Grasse and Rotterdam depots.') }}
                        </p>
                    </div>
                    <div class="space-y-3 pt-4 border-t theme-border">
                        <div class="flex flex-col gap-1 text-xs">
                            <span class="theme-text-muted font-medium">{{ __('Delay thresholds') }}</span>
                            <span class="font-mono text-[11px] theme-heading tabular-nums">
                                {{ __('Low :low h • Medium :medium h • High > :medium h', ['low' => $roadLow, 'medium' => $roadMed]) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs pt-2 border-t theme-border">
                            <span class="theme-text-muted font-medium">{{ __('Database volume') }}</span>
                            <span class="font-mono font-bold text-emerald-700 dark:text-emerald-400 tabular-nums">{{ __(':count consignments', ['count' => number_format($modeTotals['ROAD'] ?? 0)]) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. HOW TO USE THE PLATFORM (4-Step Workflow) -->
    <section id="how-it-works" class="py-12 sm:py-16 lg:py-22 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="max-w-2xl space-y-2.5">
                <p class="text-xs font-semibold text-blue-700 dark:text-cyan-400">{{ __('How it works') }}</p>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight theme-heading">
                    {{ __('Four steps from booking to buyer tracking') }}
                </h2>
                <p class="text-sm sm:text-base theme-text-secondary leading-relaxed">
                    {{ __('Nusa Freight records consignment progress at every handover point across sea, air, and road corridors.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 lg:gap-6">
                <!-- Step 1 -->
                <div class="rounded-2xl theme-card border theme-border p-6 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-mono font-bold text-blue-700 dark:text-cyan-400 bg-blue-500/10 px-2.5 py-1 rounded-md border border-blue-500/20">{{ __('Step 01') }}</span>
                            <svg class="w-5 h-5 text-blue-600 dark:text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold theme-heading tracking-tight">{{ __('Register or import consignment') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Create a single booking or upload a bulk CSV manifest with automatic ISO 6346 container and 11-digit IATA AWB check-digit validation.') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t theme-border text-[11px] theme-text-muted font-mono">
                        {{ __('Single entry or bulk CSV • ISO 6346 & AWB checks') }}
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="rounded-2xl theme-card border theme-border p-6 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-mono font-bold text-blue-700 dark:text-cyan-400 bg-blue-500/10 px-2.5 py-1 rounded-md border border-blue-500/20">{{ __('Step 02') }}</span>
                            <svg class="w-5 h-5 text-sky-600 dark:text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold theme-heading tracking-tight">{{ __('Record verified checkpoints') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Log departure, transit hub, customs clearance, and arrival milestones across UN/LOCODE seaports, IATA airports, and inland depots.') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t theme-border text-[11px] theme-text-muted font-mono">
                        {{ __('UN/LOCODE, IATA & depot checkpoints • Timestamped logging') }}
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="rounded-2xl theme-card border theme-border p-6 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-mono font-bold text-blue-700 dark:text-cyan-400 bg-blue-500/10 px-2.5 py-1 rounded-md border border-blue-500/20">{{ __('Step 03') }}</span>
                            <svg class="w-5 h-5 text-amber-700 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold theme-heading tracking-tight">{{ __('Monitor ETA, delays and disruptions') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Evaluate delay severity automatically, monitor :days-day terminal free-time dwell alerts, and inspect hub weather and FX context.', ['days' => $freeTimeDays]) }}
                        </p>
                    </div>
                    <div class="pt-3 border-t theme-border text-[11px] theme-text-muted font-mono">
                        {{ __('Delay severity • Dwell alerts • Weather & FX') }}
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="rounded-2xl theme-card border theme-border p-6 space-y-4 flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs font-mono font-bold text-blue-700 dark:text-cyan-400 bg-blue-500/10 px-2.5 py-1 rounded-md border border-blue-500/20">{{ __('Step 04') }}</span>
                            <svg class="w-5 h-5 text-emerald-700 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold theme-heading tracking-tight">{{ __('Share live public tracking') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Buyers and cargo owners follow non-sensitive route milestones and arrival estimates instantly without creating an account.') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t theme-border text-[11px] theme-text-muted font-mono">
                        {{ __('Public tracking link • No login required') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. WHO IS IT FOR? (3 Roles: Administrator, Logistics Operator, Cargo Customer) -->
    <section id="roles" class="py-12 sm:py-16 lg:py-22 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="max-w-2xl space-y-2.5">
                <p class="text-xs font-semibold text-blue-700 dark:text-cyan-400">{{ __('Who is it for?') }}</p>
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight theme-heading">
                    {{ __('Tailored for every participant') }}
                </h2>
                <p class="text-sm sm:text-base theme-text-secondary leading-relaxed">
                    {{ __('Role-based access control provides dedicated tools and views for system administrators, operations staff, and cargo owners.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                <!-- Role 1: System Administrator -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-5 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-slate-900/10 text-slate-800 dark:bg-slate-700/50 dark:text-slate-100 border border-slate-400/30">{{ __('Administrator') }}</span>
                            <span class="text-xs theme-text-muted font-mono">admin@cargotrace.test</span>
                        </div>
                        <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Platform governance & settings') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Manage user accounts and permissions, configure per-mode delay thresholds (LOW and MEDIUM alert hours), manage port, airport, and depot records, and inspect audit trails.') }}
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('User management') }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('Delay thresholds') }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('Audit logs') }}</span>
                        </div>
                    </div>
                    <div class="pt-4 border-t theme-border flex items-center justify-between">
                        <span class="text-xs font-medium theme-text-muted">{{ __('Full administrative scope') }}</span>
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-700 dark:text-cyan-400 hover:underline inline-flex items-center min-h-[44px] px-1">{{ __('Sign in') }} &rarr;</a>
                    </div>
                </div>

                <!-- Role 2: Logistics Operator -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-5 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-500/15 text-blue-700 dark:text-cyan-400 border border-blue-500/25">{{ __('Logistics Operator') }}</span>
                            <span class="text-xs theme-text-muted font-mono">operator@cargotrace.test</span>
                        </div>
                        <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Consignment & milestone operations') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Create sea, air, and road consignments, assign carriers, log checkpoint milestones across hubs, and evaluate transit schedules.') }}
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('Consignment booking') }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('Milestone logging') }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('ETA evaluation') }}</span>
                        </div>
                    </div>
                    <div class="pt-4 border-t theme-border flex items-center justify-between">
                        <span class="text-xs font-medium theme-text-muted">{{ __('Operational desk scope') }}</span>
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-700 dark:text-cyan-400 hover:underline inline-flex items-center min-h-[44px] px-1">{{ __('Sign in') }} &rarr;</a>
                    </div>
                </div>

                <!-- Role 3: Cargo Customer -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-5 flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/25">{{ __('Cargo Customer') }}</span>
                            <span class="text-xs theme-text-muted font-mono">customer@cargotrace.test</span>
                        </div>
                        <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Shipment tracking & visibility') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Track consignments by tracking number or reference, review verified milestone progression, check destination location weather, and view arrival estimates.') }}
                        </p>
                        <div class="flex flex-wrap gap-1.5 pt-1">
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('Consignment tracking') }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('Location weather') }}</span>
                            <span class="text-[11px] px-2 py-0.5 rounded theme-card-elevated border theme-border theme-text-secondary font-mono">{{ __('Timeline view') }}</span>
                        </div>
                    </div>
                    <div class="pt-4 border-t theme-border flex items-center justify-between">
                        <span class="text-xs font-medium theme-text-muted">{{ __('Client portal scope') }}</span>
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-700 dark:text-cyan-400 hover:underline inline-flex items-center min-h-[44px] px-1">{{ __('Sign in') }} &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 7. LIVE SHIPMENT TIMELINE PREVIEW -->
    <section id="timeline" class="py-12 sm:py-16 lg:py-22 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <!-- Explanation -->
                <div class="lg:col-span-5 space-y-4">
                    <p class="text-xs font-semibold text-blue-700 dark:text-cyan-400">{{ __('Route progression') }}</p>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight theme-heading">
                        {{ __('See a shipment timeline') }}
                    </h2>
                    <p class="text-sm sm:text-base theme-text-secondary leading-relaxed">
                        {{ __('Every consignment follows verified milestone events across sea, air, and road corridors from origin handover to final delivery. Schedule variances recalculate ETA directly from checkpoint logs.') }}
                    </p>
                    @if($feat)
                    <div class="pt-1">
                        <a href="{{ url('/track/' . $feat->tracking_number) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-blue-700 dark:text-cyan-400 hover:underline font-mono min-h-[44px]">
                            <span>{{ __('Open public tracking view for :number', ['number' => $feat->tracking_number]) }} &rarr;</span>
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Wide Preview Card -->
                <div class="lg:col-span-7">
                    @if($feat)
                    @php
                        $featMode = strtoupper((string) ($feat->transport_mode ?? $feat->carrier?->mode ?? 'SEA'));
                        $featModeLabel = match($featMode) {
                            'AIR' => __('Air'),
                            'ROAD' => __('Road'),
                            default => __('Sea'),
                        };
                    @endphp
                    <div class="rounded-2xl theme-card border theme-border p-6 sm:p-8 space-y-6 transition-colors duration-200">
                        <!-- Card Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b theme-border">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-base font-bold theme-heading tracking-wide">{{ $feat->tracking_number }}</span>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-700 dark:text-cyan-300 border border-blue-500/20">
                                        <x-mode-icon :mode="$featMode" class="w-3.5 h-3.5" />
                                        <span>{{ $featModeLabel }}</span>
                                    </span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-md theme-card-elevated border theme-border theme-text-secondary">{{ __('Recorded milestone') }}</span>
                                </div>
                                <span class="text-xs theme-text-muted font-mono block mt-1.5">{{ $feat->carrier->name ?? 'Mediterranean Shipping Company (MSC)' }}</span>
                            </div>
                            @if($feat->is_delayed && $feat->status === 'DELIVERED')
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-400/20 text-amber-800 dark:text-amber-400 border border-amber-400/40">
                                    &bull; {{ __('Delivered late (+:hours h)', ['hours' => (int)$feat->delay_hours]) }}
                                </span>
                            @elseif($feat->is_delayed)
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-md text-xs font-semibold bg-amber-400/20 text-amber-800 dark:text-amber-400 border border-amber-400/40">
                                    &bull; {{ __('Delayed (+:hours h)', ['hours' => (int)$feat->delay_hours]) }}
                                </span>
                            @elseif($feat->status === 'DELIVERED')
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30">
                                    &bull; {{ __('Delivered') }}
                                </span>
                            @else
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-500/15 text-blue-700 dark:text-cyan-300 border border-blue-400/30">
                                    &bull; {{ __('In transit') }}
                                </span>
                            @endif
                        </div>

                        <!-- Vertical Milestone Timeline -->
                        <div class="space-y-5 relative">
                            <div class="absolute left-[7px] top-3 bottom-3 w-[2px] bg-slate-300 dark:bg-slate-700"></div>

                            <!-- Milestone 1: Origin -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full bg-blue-600 mt-0.5 shrink-0 ring-4 ring-blue-500/20 z-10 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                                <div class="flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                                    <div>
                                        <span class="font-bold theme-heading block">{{ __('Origin • Dispatched') }}</span>
                                        <span class="text-[11px] theme-text-secondary font-mono">{{ $feat->originLocation->name ?? 'Port of Singapore (SGSIN)' }}</span>
                                    </div>
                                    <span class="theme-text-muted text-[11px] font-mono tabular-nums">{{ $feat->expected_departure_at?->format('M d, H:i') ?? 'Oct 25, 15:57' }}</span>
                                </div>
                            </div>

                            <!-- Milestone 2: Current Waypoint -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full bg-cyan-600 dark:bg-cyan-400 mt-0.5 shrink-0 ring-4 ring-cyan-500/25 z-10 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white dark:bg-slate-950"></span>
                                </span>
                                <div class="flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                                    <div>
                                        <span class="font-bold text-blue-700 dark:text-cyan-300 block">{{ __('Current waypoint') }}</span>
                                        <span class="text-[11px] theme-text-secondary font-mono">{{ $feat->currentLocation->name ?? __('Transit corridor') }}</span>
                                    </div>
                                    <span class="text-blue-700 dark:text-cyan-400 text-[11px] font-mono font-semibold">{{ __('In transit') }}</span>
                                </div>
                            </div>

                            <!-- Milestone 3: Destination -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full border-2 border-slate-400 dark:border-slate-500 bg-slate-100 dark:bg-slate-900 mt-0.5 shrink-0 z-10"></span>
                                <div class="flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs">
                                    <div>
                                        <span class="font-semibold theme-text-secondary block">{{ __('Destination') }}</span>
                                        <span class="text-[11px] theme-text-muted font-mono">{{ $feat->destinationLocation->name ?? 'Port of Rotterdam (NLRTM)' }}</span>
                                    </div>
                                    <span class="theme-text-muted text-[11px] font-mono tabular-nums">{{ $feat->expected_arrival_at?->format('M d, H:i') ?? 'Nov 08, 15:57' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer: ETA -->
                        <div class="pt-4 border-t theme-border flex items-center justify-between">
                            <div class="flex items-center space-x-2 text-xs">
                                <svg class="w-4 h-4 text-blue-700 dark:text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="theme-text-secondary font-medium">{{ __('Estimated arrival') }}</span>
                            </div>
                            <span class="font-mono font-bold text-xs text-blue-700 dark:text-cyan-300 tabular-nums">
                                {{ $feat->estimated_arrival_at?->format('M d, H:i') ?? 'Nov 11, 15:57' }}
                            </span>
                        </div>
                    </div>
                    @else
                    <div class="rounded-2xl theme-card border theme-border p-8 text-center space-y-3">
                        <p class="text-sm font-semibold theme-heading">{{ __('No active shipment timeline available yet.') }}</p>
                        <p class="text-xs theme-text-secondary max-w-md mx-auto">
                            {{ __('Sign in as an operator to register a consignment and record its first verified checkpoint.') }}
                        </p>
                        <div class="pt-1">
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                {{ __('Sign in') }}
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- 8. GLOBAL ROUTE MAP (Fixed 2D Leaflet Corridor Map) -->
    <section id="network" class="py-12 sm:py-16 lg:py-22 theme-page border-b theme-border transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-blue-700 dark:text-cyan-400">{{ __('Network map') }}</p>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight theme-heading">
                        {{ __('Global freight network') }}
                    </h2>
                    <p class="text-xs sm:text-sm theme-text-secondary max-w-2xl leading-relaxed">
                        {{ __('Sea, air, and road corridors connecting Indonesian spice origins with seaports, airports, and regional depots worldwide.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs">
                    <div class="inline-flex items-center gap-1.5 theme-text-secondary font-medium">
                        <x-mode-icon mode="SEA" class="w-4 h-4 text-blue-700 dark:text-cyan-400" />
                        <span>{{ __('Sea') }}</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 theme-text-secondary font-medium">
                        <x-mode-icon mode="AIR" class="w-4 h-4 text-sky-700 dark:text-sky-400" />
                        <span>{{ __('Air') }}</span>
                    </div>
                    <div class="inline-flex items-center gap-1.5 theme-text-secondary font-medium">
                        <x-mode-icon mode="ROAD" class="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
                        <span>{{ __('Road') }}</span>
                    </div>
                    <span class="hidden sm:inline theme-text-muted">|</span>
                    <div class="flex items-center gap-2 font-mono">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-600 dark:bg-cyan-400 shrink-0"></span>
                        <span class="theme-text-secondary">{{ __('Active consignments:') }} <strong class="theme-heading tabular-nums">{{ count($activeShipments) }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2 font-mono">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shrink-0"></span>
                        <span class="theme-text-secondary">{{ __('Trade locations:') }} <strong class="theme-heading tabular-nums">{{ count($locations) }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Leaflet Container with OpenStreetMap tiles -->
            <div class="relative rounded-2xl overflow-hidden border theme-border shadow-lg theme-card">
                <div id="landing-world-map" class="w-full h-[360px] sm:h-[440px] lg:h-[480px] z-10"></div>
                @if(empty($mapShipments))
                    <div class="absolute inset-0 z-20 flex items-center justify-center p-6 pointer-events-none">
                        <div class="px-4 py-3 rounded-xl theme-card-elevated border theme-border text-xs theme-text-secondary shadow-md">
                            {{ __('No active route corridors are mapped yet.') }}
                        </div>
                    </div>
                @endif
                <div class="hidden sm:block absolute bottom-4 left-4 z-20 px-3.5 py-2 rounded-xl theme-card-elevated border theme-border text-[11px] theme-text-muted font-mono shadow-sm">
                    <span>{{ __('Interactive OpenStreetMap • Geodesic route corridors connecting ports, airports and depots') }}</span>
                </div>
            </div>
        </div>
    </section>

    </main>

    <!-- 9. PROFESSIONAL CARGO FOOTER -->
    <footer class="theme-sidebar border-t theme-border py-12 text-xs theme-text-muted transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 lg:gap-10">
                <!-- Brand & Scope Summary -->
                <div class="md:col-span-5 space-y-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-sm shrink-0">
                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold theme-heading block">{{ config('app.name', 'Nusa Freight') }}</span>
                            <span class="text-[11px] text-blue-700 dark:text-cyan-400 font-medium">{{ __('Global spice freight by sea, air and road') }}</span>
                        </div>
                    </div>
                    <p class="text-xs theme-text-secondary leading-relaxed max-w-md">
                        {{ __('Multimodal visibility platform for Indonesian spice and essential oil exports across sea, air, and road corridors.') }}
                    </p>
                    <p class="text-[11px] theme-text-muted">
                        {{ __('Individual University Web Programming Capstone Project • Laravel, Blade & Tailwind CSS') }}
                    </p>
                </div>

                <!-- Supported International Logistics Standards -->
                <div class="md:col-span-4 space-y-3">
                    <h3 class="text-xs font-bold theme-heading">{{ __('Supported logistics standards') }}</h3>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border space-y-0.5">
                            <span class="font-mono font-bold text-xs text-blue-700 dark:text-cyan-400 block">ISO 6346</span>
                            <span class="text-[11px] theme-text-secondary block leading-snug">{{ __('Container check-digit validation') }}</span>
                        </div>
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border space-y-0.5">
                            <span class="font-mono font-bold text-xs text-sky-700 dark:text-sky-400 block">IATA AWB</span>
                            <span class="text-[11px] theme-text-secondary block leading-snug">{{ __('11-digit air waybill standard') }}</span>
                        </div>
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border space-y-0.5">
                            <span class="font-mono font-bold text-xs theme-heading block">UN/LOCODE</span>
                            <span class="text-[11px] theme-text-secondary block leading-snug">{{ __('Seaport and trade hub codes') }}</span>
                        </div>
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border space-y-0.5">
                            <span class="font-mono font-bold text-xs text-emerald-700 dark:text-emerald-400 block">CMR</span>
                            <span class="text-[11px] theme-text-secondary block leading-snug">{{ __('Overland consignment notes') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Navigation -->
                <div class="md:col-span-3 space-y-3">
                    <h3 class="text-xs font-bold theme-heading">{{ __('Quick navigation') }}</h3>
                    <ul class="grid grid-cols-2 md:grid-cols-1 gap-y-2 gap-x-4 text-xs theme-text-secondary font-medium">
                        <li><a href="#overview" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors">{{ __('Platform') }}</a></li>
                        <li><a href="#modes" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors">{{ __('Freight modes') }}</a></li>
                        <li><a href="#how-it-works" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors">{{ __('How it works') }}</a></li>
                        <li><a href="#roles" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors">{{ __('Roles') }}</a></li>
                        <li><a href="#timeline" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors">{{ __('Timeline') }}</a></li>
                        <li><a href="#network" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors">{{ __('Network map') }}</a></li>
                        <li><a href="{{ route('login') }}" class="text-blue-700 dark:text-cyan-400 hover:underline font-semibold">{{ __('Sign in') }}</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-blue-700 dark:hover:text-cyan-400 transition-colors">{{ __('Create Shipper Account') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="pt-6 border-t theme-border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-[11px] theme-text-muted">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'Nusa Freight') }} &bull; {{ __('Global spice freight by sea, air and road') }}</span>
                <span>{{ __('Standardized UN/LOCODE, IATA, and depot milestones with rule-based arrival schedule evaluations.') }}</span>
            </div>
        </div>
    </footer>
</div>

{{-- Pass map configuration to client module --}}
<script>
    window.__CARGOTRACE__ = {
        worldMap: {
            containerId: 'landing-world-map',
            options: {
                shipments: @json($mapShipments),
                locations: @json($mapLocations ?? [])
            }
        }
    };
</script>
@endsection

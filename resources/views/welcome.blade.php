@extends('layouts.guest')
@section('has-locale-switcher', '1')

@section('content')
<div class="relative min-h-screen theme-page overflow-x-hidden">
    <!-- Enterprise Navbar (Unified Maritime Theme) -->
    <header class="sticky top-0 z-50 theme-header backdrop-blur-xl border-b theme-border shadow-lg transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-2">
                <!-- Brand: Unified Electric Blue Maritime Shield -->
                <div class="flex items-center space-x-2.5 sm:space-x-3.5 min-w-0">
                    <a href="{{ route('welcome') }}" class="flex items-center space-x-2 sm:space-x-3.5 group min-w-0">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform duration-200 shrink-0">
                            <!-- Inverted Triangle / Maritime Shield Logo -->
                            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-base sm:text-xl font-extrabold tracking-tight theme-heading block truncate">CargoTrace</span>
                                <span class="hidden sm:inline-block text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-cyan-400 border border-blue-500/20 font-mono">{{ __('Global') }}</span>
                            </div>
                            <span class="hidden sm:block text-[10px] uppercase tracking-widest theme-text-muted font-semibold leading-tight">{{ __('Global spice freight by sea, air and road') }}</span>
                        </div>
                    </a>
                </div>

                <!-- Nav Links: Sentence case pointing to existing sections -->
                <nav class="hidden md:flex items-center space-x-8 text-xs font-medium theme-text-secondary" aria-label="{{ __('Main Navigation') }}">
                    <a href="#modes" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">{{ __('Freight modes') }}</a>
                    <a href="#timeline" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">{{ __('Timeline') }}</a>
                    <a href="#how-it-works" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">{{ __('How it works') }}</a>
                    <a href="#network" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">{{ __('Network map') }}</a>
                    <a href="#roles" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">{{ __('Roles') }}</a>
                </nav>

                <!-- Actions: Theme Toggle, Locale Switcher & Portal Access -->
                <div class="flex items-center space-x-1.5 sm:space-x-3 shrink-0">
                    <!-- Theme Toggle Switcher -->
                    <button
                        type="button"
                        id="welcome-theme-toggle"
                        role="switch"
                        aria-checked="true"
                        aria-pressed="true"
                        tabindex="0"
                        onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.CargoTrace&&window.CargoTrace.toggleTheme?window.CargoTrace.toggleTheme():null}"
                        class="relative flex items-center p-1 rounded-full bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 w-12 sm:w-14 h-7 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shrink-0"
                        aria-label="{{ __('Toggle dark and light theme') }}"
                        title="{{ __('Switch theme') }}"
                    >
                        <span class="w-5 h-5 flex items-center justify-center text-slate-400 dark:text-cyan-400 transition-colors">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                        </span>
                        <span class="w-5 h-5 flex items-center justify-center text-amber-500 dark:text-slate-500 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </span>
                    </button>

                    <!-- Language Switcher (EN | ID) -->
                    <x-locale-switcher size="sm" />

                    @auth
                        <a href="{{ route('dashboard') }}" aria-label="{{ __('Sign In / Portal') }}" class="px-2.5 sm:px-5 py-2 sm:py-2.5 rounded-xl text-[11px] sm:text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-500/20 transition-all duration-200 flex items-center gap-1.5 whitespace-nowrap">
                            <span>{{ __('Open the portal') }}</span>
                            <svg class="hidden sm:block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" aria-label="{{ __('Sign In / Portal') }}" class="px-2.5 sm:px-5 py-2 sm:py-2.5 rounded-xl text-[11px] sm:text-xs font-semibold text-white bg-blue-600 hover:bg-blue-500 shadow-md shadow-blue-500/20 transition-all duration-200 flex items-center gap-1.5 whitespace-nowrap">
                            <span>{{ __('Open the portal') }}</span>
                            <svg class="hidden sm:block w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
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
    @endphp

    <main>
    <!-- 1. CINEMATIC MULTIMODAL HERO SECTION (Dark: hero-multimodal-dark.jpg / bg-dark.webp | Light: hero-multimodal-light.jpg / bg-light.webp) -->
    <section class="relative min-h-[600px] sm:min-h-[680px] lg:min-h-[760px] flex items-center overflow-hidden border-b theme-border bg-[#EEF5FB] dark:bg-[#060D1A] transition-colors duration-300 ease-in-out">
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

                    {{-- Light Theme Background Photography (or pale gradient + SVG motif fallback) --}}
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

            <!-- Dark Theme Gradient Scrim (crossfades smoothly, protects left typography while keeping right artwork uncropped) -->
            <div class="absolute inset-0 opacity-0 dark:opacity-100 transition-opacity duration-300 ease-in-out" style="background: linear-gradient(90deg, rgba(6, 13, 26, 0.96) 0%, rgba(6, 13, 26, 0.84) 26%, rgba(6, 13, 26, 0.36) 44%, rgba(6, 13, 26, 0) 56%), linear-gradient(180deg, rgba(6, 13, 26, 0.22) 0%, rgba(6, 13, 26, 0) 80px);"></div>
            <div class="absolute inset-0 sm:hidden bg-[#060D1A]/45 opacity-0 dark:opacity-100 transition-opacity duration-300 ease-in-out"></div>

            <!-- Light Theme Gradient Scrim (crossfades smoothly, protects left typography while keeping right artwork uncropped) -->
            <div class="absolute inset-0 opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out" style="background: linear-gradient(90deg, rgba(238, 245, 251, 0.96) 0%, rgba(238, 245, 251, 0.86) 26%, rgba(238, 245, 251, 0.38) 44%, rgba(238, 245, 251, 0) 56%), linear-gradient(180deg, rgba(238, 245, 251, 0.25) 0%, rgba(238, 245, 251, 0) 80px);"></div>
            <div class="absolute inset-0 sm:hidden bg-[#EEF5FB]/55 opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out"></div>
        </div>

        <div class="relative z-10 w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 lg:py-28">
            <!-- Hero content limited to max-width 36rem so text sits entirely inside the scrim -->
            <div class="max-w-[36rem] space-y-6">
                <!-- Understated Kicker: Sentence case -->
                <p class="text-xs font-semibold text-blue-700 dark:text-cyan-300 tracking-wide">
                    {{ __('Multimodal spice and essential oil export tracking') }}
                </p>

                <!-- Headline -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-[#0B1728] dark:text-white leading-[1.08]">
                    {{ __('Follow every consignment') }}<br>
                    <span class="text-[#2563EB] dark:text-cyan-400">
                        {{ __('across sea, air and road.') }}
                    </span>
                </h1>

                <!-- One-sentence subline -->
                <p class="text-base sm:text-lg text-slate-700 dark:text-slate-200 leading-relaxed">
                    {{ __('Track nutmeg, cloves, patchouli oil and more from Indonesian origins to buyers worldwide.') }}
                </p>

                <!-- Tracking-number search box -->
                <form
                    action="javascript:void(0)"
                    onsubmit="if(window.handlePublicTrack){window.handlePublicTrack();}else{var q=document.getElementById('public-track-input').value.trim();if(q){window.location.href='/track/'+encodeURIComponent(q);}}"
                    class="pt-2 space-y-2"
                >
                    <label for="public-track-input" class="block text-xs font-medium text-slate-700 dark:text-slate-200">
                        {{ __('Track by consignment, container, AWB or plate') }}
                    </label>
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input
                                type="text"
                                id="public-track-input"
                                placeholder="{{ __('Tracking number, e.g. :example', ['example' => $feat->tracking_number ?? 'CTG-SEA-2026-00244']) }}"
                                class="w-full min-h-[44px] pl-10 pr-4 py-3 rounded-xl bg-white/95 dark:bg-[#0B1728]/90 border border-[#D5DEEA] dark:border-white/10 text-[#0B1728] dark:text-[#E6EEF8] placeholder-slate-500 dark:placeholder-slate-400 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 shadow-sm"
                            >
                        </div>
                        <button
                            type="submit"
                            aria-label="{{ __('Track Shipment') }}"
                            class="min-h-[44px] px-5 py-3 rounded-xl bg-[#2563EB] hover:bg-[#1D4ED8] text-white font-semibold text-xs transition shadow-md shadow-blue-500/20 whitespace-nowrap cursor-pointer flex items-center justify-center gap-1.5"
                        >
                            <span>{{ __('Track a shipment') }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>

                <!-- Sample tracking quick links + secondary anchor -->
                <div class="pt-1 space-y-2">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                        <span class="text-slate-700 dark:text-slate-300">{{ __('Sample consignments:') }}</span>
                        <a href="{{ url('/track/CTG-SEA-2026-00189') }}" class="font-mono text-blue-700 dark:text-cyan-400 hover:underline py-1">CTG-SEA-2026-00189</a>
                        <a href="{{ url('/track/CTG-SEA-2026-00244') }}" class="font-mono text-blue-700 dark:text-cyan-400 hover:underline py-1">CTG-SEA-2026-00244</a>
                        <a href="{{ url('/track/CTG-AIR-2026-00678') }}" class="font-mono text-blue-700 dark:text-cyan-400 hover:underline py-1">CTG-AIR-2026-00678</a>
                    </div>
                    <a href="#modes" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-cyan-400 hover:text-blue-800 dark:hover:text-cyan-300 transition-colors py-1">
                        <span>{{ __('Explore freight modes') }}</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. GLOBAL METRICS STRIP (Plain words, sentence case) -->
    <section class="theme-card-elevated border-b theme-border py-8 transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-y md:divide-y-0 md:divide-x theme-border">
                <div class="px-4">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold theme-heading tracking-tight font-mono">{{ $locations->count() }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('Locations in database') }}</div>
                </div>
                <div class="px-4 pt-6 md:pt-0">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-blue-600 dark:text-cyan-400 tracking-tight font-mono">{{ $onTimePercentage }}%</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('On-time rate') }}</div>
                </div>
                <div class="px-4 pt-6 md:pt-0">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold theme-heading tracking-tight font-mono">{{ $carriers->count() }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('Carriers') }}</div>
                </div>
                <div class="px-4 pt-6 md:pt-0">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-blue-600 dark:text-cyan-400 tracking-tight font-mono">{{ number_format($totalShipments) }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 font-medium">{{ __('Shipments tracked to date') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2B. MULTIMODAL CAPABILITY HIGHLIGHT (3-column Sea / Air / Road spice export scope) -->
    <section id="modes" class="py-14 lg:py-20 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div class="max-w-2xl space-y-2">
                    <p class="text-xs font-semibold text-blue-600 dark:text-cyan-400">{{ __('Multimodal freight coverage') }}</p>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading">
                        {{ __('Three transport corridors for Indonesian spice exports') }}
                    </h2>
                    <p class="text-sm theme-text-secondary leading-relaxed">
                        {{ __('Every shipment carries mode-specific identifiers, location codes, and delay thresholds tailored to ocean, air, and overland spice trade legs.') }}
                    </p>
                </div>
                <a href="#network" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline inline-flex items-center gap-1.5 py-2 self-start md:self-auto">
                    <span>{{ __('Inspect live route map') }}</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="rounded-2xl theme-card border theme-border overflow-hidden grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x theme-border">
                <!-- Sea Freight (Primary Export Corridor) -->
                <div class="p-6 sm:p-8 flex flex-col justify-between gap-5 bg-blue-500/[0.03] dark:bg-blue-500/[0.05]">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/25 flex items-center justify-center">
                                    <x-mode-icon mode="SEA" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Sea Freight') }}</h3>
                                    <span class="text-[11px] font-mono theme-text-muted">UN/LOCODE &bull; ISO 6346 &bull; B/L</span>
                                </div>
                            </div>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full bg-blue-600/15 text-blue-700 dark:text-cyan-300 border border-blue-500/25 whitespace-nowrap">
                                {{ __('Primary export') }}
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Containerized nutmeg, cloves, cassia, and black pepper moving from Belawan and Tanjung Priok to Rotterdam, Hamburg, Jebel Ali, and US East Coast ports.') }}
                        </p>
                    </div>
                    <div class="pt-4 border-t theme-border flex items-center justify-between text-xs">
                        <span class="theme-text-muted font-medium">{{ __('Database volume') }}</span>
                        <span class="font-mono font-bold text-blue-600 dark:text-cyan-400 tabular-nums">{{ __(':count consignments', ['count' => number_format($modeTotals['SEA'] ?? 0)]) }}</span>
                    </div>
                </div>

                <!-- Air Freight -->
                <div class="p-6 sm:p-8 flex flex-col justify-between gap-5">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-600 dark:text-sky-400 border border-sky-500/25 flex items-center justify-center">
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
                    <div class="pt-4 border-t theme-border flex items-center justify-between text-xs">
                        <span class="theme-text-muted font-medium">{{ __('Database volume') }}</span>
                        <span class="font-mono font-bold text-sky-600 dark:text-sky-400 tabular-nums">{{ __(':count consignments', ['count' => number_format($modeTotals['AIR'] ?? 0)]) }}</span>
                    </div>
                </div>

                <!-- Road Freight -->
                <div class="p-6 sm:p-8 flex flex-col justify-between gap-5">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25 flex items-center justify-center">
                                <x-mode-icon mode="ROAD" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Road Freight') }}</h3>
                                <span class="text-[11px] font-mono theme-text-muted">{{ __('Depots') }} &bull; {{ __('Plate') }} &bull; CMR</span>
                            </div>
                        </div>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('First-mile Sumatra plantation hauls from Lhokseumawe and Banda Aceh to Medan hubs, plus European last-mile deliveries to Grasse and Rotterdam depots.') }}
                        </p>
                    </div>
                    <div class="pt-4 border-t theme-border flex items-center justify-between text-xs">
                        <span class="theme-text-muted font-medium">{{ __('Database volume') }}</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">{{ __(':count consignments', ['count' => number_format($modeTotals['ROAD'] ?? 0)]) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. SHIPMENT TIMELINE PREVIEW ("See a shipment timeline") -->
    <section id="timeline" class="py-16 lg:py-24 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <!-- Short Explanation -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-600/10 dark:bg-blue-600/20 border border-blue-600/25 dark:border-blue-400/30 text-blue-700 dark:text-cyan-300 text-xs font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-cyan-400"></span>
                        {{ __('Route progression') }}
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight theme-heading">
                        {{ __('See a shipment timeline') }}
                    </h2>
                    <p class="text-sm sm:text-base theme-text-secondary leading-relaxed">
                        {{ __('Every consignment follows verified milestone events across sea, air, and road corridors from origin handover to final delivery. Schedule variances recalculate ETA directly from checkpoint logs.') }}
                    </p>
                    @if($feat)
                    <div class="pt-2">
                        <a href="{{ url('/track/' . $feat->tracking_number) }}" class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:text-blue-700 dark:hover:text-cyan-300 font-mono transition-colors">
                            <span>{{ __('Open public tracking view for :number', ['number' => $feat->tracking_number]) }} &rarr;</span>
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Wide Preview Card (Real data and clean markup) -->
                <div class="lg:col-span-7">
                    @if($feat)
                    <div class="rounded-2xl theme-card border theme-border p-6 sm:p-8 space-y-6 shadow-xl backdrop-blur-xl transition-colors duration-200">
                        <!-- Card Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b theme-border">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-base font-bold theme-heading tracking-wide block">{{ $feat->tracking_number }}</span>
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-600 dark:text-cyan-300">{{ __('Recorded milestone') }}</span>
                                </div>
                                <span class="text-xs theme-text-muted font-mono block mt-1">{{ $feat->carrier->name ?? 'Mediterranean Shipping Company (MSC)' }}</span>
                            </div>
                            @if($feat->is_delayed && $feat->status === 'DELIVERED')
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-full text-xs font-medium bg-amber-400/20 text-amber-600 dark:text-amber-400 border border-amber-400/40">
                                    &bull; {{ __('Delivered late (+:hours h)', ['hours' => (int)$feat->delay_hours]) }}
                                </span>
                            @elseif($feat->is_delayed)
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-full text-xs font-medium bg-amber-400/20 text-amber-600 dark:text-amber-400 border border-amber-400/40">
                                    &bull; {{ __('Delayed (+:hours h)', ['hours' => (int)$feat->delay_hours]) }}
                                </span>
                            @elseif($feat->status === 'DELIVERED')
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/40">
                                    &bull; {{ __('Delivered') }}
                                </span>
                            @else
                                <span class="self-start sm:self-auto px-2.5 py-1 rounded-full text-xs font-medium bg-blue-500/20 text-blue-600 dark:text-cyan-300 border border-blue-400/30">
                                    &bull; {{ __('In transit') }}
                                </span>
                            @endif
                        </div>

                        <!-- Vertical Milestone Timeline -->
                        <div class="space-y-5 relative">
                            <!-- Vertical Connecting Line -->
                            <div class="absolute left-[7px] top-3 bottom-3 w-[2px] bg-slate-300 dark:bg-slate-700"></div>

                            <!-- Milestone 1: Origin Port -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full bg-blue-500 mt-0.5 shrink-0 ring-4 ring-blue-500/25 z-10 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-bold theme-heading block">{{ __('Origin • Dispatched') }}</span>
                                        <span class="text-[11px] theme-text-secondary font-mono">{{ $feat->originLocation->name ?? 'Port of Singapore (SGSIN)' }}</span>
                                    </div>
                                    <span class="theme-text-muted text-[11px] font-mono">{{ $feat->expected_departure_at?->format('M d, H:i') ?? 'Oct 25, 15:57' }}</span>
                                </div>
                            </div>

                            <!-- Milestone 2: Active Transit Leg -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full bg-cyan-500 dark:bg-cyan-400 mt-0.5 shrink-0 ring-4 ring-cyan-500/30 z-10 animate-pulse flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white dark:bg-slate-950"></span>
                                </span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-bold text-blue-600 dark:text-cyan-300 block">{{ __('Current waypoint') }}</span>
                                        <span class="text-[11px] theme-text-secondary font-mono">{{ $feat->currentLocation->name ?? __('Transit corridor') }}</span>
                                    </div>
                                    <span class="text-blue-600 dark:text-cyan-400 text-[11px] font-mono font-semibold">{{ __('In transit') }}</span>
                                </div>
                            </div>

                            <!-- Milestone 3: Destination -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full border-2 border-slate-400 dark:border-slate-500 bg-slate-100 dark:bg-slate-900 mt-0.5 shrink-0 z-10"></span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-semibold theme-text-secondary block">{{ __('Destination') }}</span>
                                        <span class="text-[11px] theme-text-muted font-mono">{{ $feat->destinationLocation->name ?? 'Port of Rotterdam (NLRTM)' }}</span>
                                    </div>
                                    <span class="theme-text-muted text-[11px] font-mono">{{ $feat->expected_arrival_at?->format('M d, H:i') ?? 'Nov 08, 15:57' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer: ETA & Vessel Coordinates -->
                        <div class="pt-4 border-t theme-border flex items-center justify-between">
                            <div class="flex items-center space-x-2 text-xs">
                                <svg class="w-4 h-4 text-blue-600 dark:text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                                <span class="theme-text-secondary font-medium">{{ __('Estimated arrival') }}</span>
                            </div>
                            <span class="font-mono font-bold text-xs text-blue-600 dark:text-cyan-300">
                                {{ $feat->estimated_arrival_at?->format('M d, H:i') ?? 'Nov 11, 15:57' }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- 4. HOW IT WORKS (3 steps from real features) -->
    <section id="how-it-works" class="py-16 lg:py-24 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="max-w-2xl space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-600/10 dark:bg-blue-600/20 border border-blue-600/25 dark:border-blue-400/30 text-blue-700 dark:text-cyan-300 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-cyan-400"></span>
                    {{ __('How it works') }}
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight theme-heading">
                    {{ __('Three steps from origin to destination') }}
                </h2>
                <p class="text-sm sm:text-base theme-text-secondary leading-relaxed">
                    {{ __('CargoTrace records consignment progress at every handover point across sea, air, and road corridors.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                <!-- Step 1 -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-4 shadow-sm flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-blue-600 dark:text-cyan-400 bg-blue-500/10 dark:bg-cyan-400/10 px-2.5 py-1 rounded-md border border-blue-500/20">{{ __('Step 01') }}</span>
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Book and register freight') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Log sea, air, and road consignments with ISO 6346 container numbers, 11-digit AWB codes, vehicle plates and CMR notes, carrier lines, and transit schedules.') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t theme-border text-[11px] theme-text-muted font-mono">
                        {{ __('Consignment registration • Master data validation') }}
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-4 shadow-sm flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-blue-600 dark:text-cyan-400 bg-blue-500/10 dark:bg-cyan-400/10 px-2.5 py-1 rounded-md border border-blue-500/20">{{ __('Step 02') }}</span>
                            <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Record verified checkpoints') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('Operators record dispatch, transit hubs, transshipments, and arrivals across UN/LOCODE seaports, IATA airports, and inland depots with timestamped notes.') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t theme-border text-[11px] theme-text-muted font-mono">
                        {{ __('UN/LOCODE, IATA & depot checkpoints • Timestamped logging') }}
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-4 shadow-sm flex flex-col justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold text-blue-600 dark:text-cyan-400 bg-blue-500/10 dark:bg-cyan-400/10 px-2.5 py-1 rounded-md border border-blue-500/20">{{ __('Step 03') }}</span>
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold theme-heading tracking-tight">{{ __('Evaluate delays and arrival schedules') }}</h3>
                        <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                            {{ __('The system monitors milestone timestamps against expected schedules, automatically flagging delays when schedule variance exceeds configured thresholds.') }}
                        </p>
                    </div>
                    <div class="pt-3 border-t theme-border text-[11px] theme-text-muted font-mono">
                        {{ __('Dynamic schedule evaluation • Threshold alerts') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. GLOBAL NETWORK MAP (Drawn from real seeded routes) -->
    <section id="network" class="py-16 lg:py-24 theme-page border-b theme-border transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-600/10 dark:bg-blue-600/20 border border-blue-600/25 dark:border-blue-400/30 text-blue-700 dark:text-cyan-300 text-xs font-medium mb-3">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-cyan-400"></span>
                        {{ __('Network map') }}
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight theme-heading">
                        {{ __('Global freight network') }}
                    </h2>
                    <p class="text-xs sm:text-sm theme-text-secondary mt-1 max-w-2xl">
                        {{ __('Sea, air, and road corridors connecting Indonesian spice origins with seaports, airports, and regional depots worldwide.') }}
                    </p>
                </div>
                <div class="flex items-center space-x-6 text-xs font-mono">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                        <span class="theme-text-secondary">{{ __('Active consignments:') }} <strong>{{ count($activeShipments) }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span class="theme-text-secondary">{{ __('Trade locations:') }} <strong>{{ count($locations) }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Leaflet Container with OpenStreetMap tiles -->
            <div class="relative rounded-2xl overflow-hidden border theme-border shadow-2xl theme-card">
                <div id="landing-world-map" class="w-full h-[480px] z-10"></div>
                <div class="absolute bottom-4 left-4 z-20 px-3.5 py-2 rounded-xl theme-card-elevated border theme-border text-[11px] theme-text-muted font-mono shadow-lg">
                    <span>{{ __('Interactive OpenStreetMap • Geodesic route corridors connecting ports, airports and depots') }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. THREE ROLES (Admin, Operator, Customer) -->
    <section id="roles" class="py-16 lg:py-24 theme-page border-b theme-border transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="max-w-2xl space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-600/10 dark:bg-blue-600/20 border border-blue-600/25 dark:border-blue-400/30 text-blue-700 dark:text-cyan-300 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-cyan-400"></span>
                    {{ __('User roles') }}
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight theme-heading">
                    {{ __('Tailored for every participant') }}
                </h2>
                <p class="text-sm sm:text-base theme-text-secondary leading-relaxed">
                    {{ __('Role-based access control provides dedicated tools and views for system administrators, operations staff, and cargo owners.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                <!-- Role 1: System Administrator -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-5 shadow-sm flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/20">{{ __('Administrator') }}</span>
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
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">{{ __('Sign in') }} &rarr;</a>
                    </div>
                </div>

                <!-- Role 2: Logistics Operator -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-5 shadow-sm flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/20">{{ __('Logistics Operator') }}</span>
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
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">{{ __('Sign in') }} &rarr;</a>
                    </div>
                </div>

                <!-- Role 3: Cargo Customer -->
                <div class="rounded-2xl theme-card border theme-border p-6 sm:p-7 space-y-5 shadow-sm flex flex-col justify-between">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">{{ __('Cargo Customer') }}</span>
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
                        <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline">{{ __('Sign in') }} &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    </main>

    <!-- 7. ENTERPRISE FOOTER -->
    <footer class="theme-sidebar border-t theme-border py-12 text-xs theme-text-muted transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 flex items-center justify-center text-white font-bold text-xs shadow-md shadow-blue-500/25">
                        <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="theme-heading font-semibold block">&copy; {{ date('Y') }} CargoTrace &bull; {{ __('Global spice freight by sea, air and road') }}</span>
                        <span class="text-[11px] text-blue-600 dark:text-cyan-400 font-sans">{{ __('Individual University Web Programming Capstone Project • Laravel, Blade & Tailwind CSS') }}</span>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-6 theme-text-secondary font-medium">
                    <a href="#modes" class="hover:theme-heading transition">{{ __('Freight modes') }}</a>
                    <a href="#timeline" class="hover:theme-heading transition">{{ __('Timeline') }}</a>
                    <a href="#how-it-works" class="hover:theme-heading transition">{{ __('How it works') }}</a>
                    <a href="#network" class="hover:theme-heading transition">{{ __('Network map') }}</a>
                    <a href="#roles" class="hover:theme-heading transition">{{ __('Roles') }}</a>
                    <a href="{{ route('login') }}" class="text-blue-600 dark:text-cyan-400 hover:underline">{{ __('Sign in') }}</a>
                </div>
            </div>

            <div class="pt-6 border-t theme-border flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] theme-text-muted">
                <span>{{ __('Standardized UN/LOCODE, IATA, and depot milestones with rule-based arrival schedule evaluations.') }}</span>
                <span>{{ __('Global spice freight by sea, air and road.') }}</span>
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

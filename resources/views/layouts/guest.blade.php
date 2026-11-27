<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="map-tile-url" content="{{ config('cargotrace.map.tile_url') }}">
    <meta name="map-tile-attribution" content="{{ config('cargotrace.map.attribution') }}">

    <title>{{ $title ?? __('Nusa Freight: Global spice freight by sea, air and road') }}</title>

    <!-- Theme Initialization Script (Zero-Flicker, prefers-color-scheme fallback) -->
    <script>
        (function() {
            let theme = localStorage.getItem('cargotrace_theme');
            if (!theme) {
                theme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) ? 'light' : 'dark';
            }
            document.documentElement.setAttribute('data-theme', theme);
            if (theme === 'light') {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            }
        })();
    </script>

    <!-- Scripts & Styles via Vite (Inter, JetBrains Mono, Leaflet bundled) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="theme-page font-sans antialiased min-h-full flex flex-col selection:bg-blue-600 selection:text-white transition-colors duration-200">
    @if(!View::hasSection('has-locale-switcher'))
        <div class="fixed top-4 right-4 z-50">
            <x-locale-switcher size="sm" />
        </div>
    @endif

    @hasSection('auth-shell')
        @php
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

        <div class="min-h-screen relative flex flex-col justify-between overflow-x-hidden bg-[#EEF5FB] dark:bg-[#060D1A] transition-colors duration-300 ease-in-out">
            <!-- Multimodal Background Imagery: Both themes share identical positioned box and crossfade via opacity -->
            <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none" aria-hidden="true">
                <div class="hero-artwork-frame absolute inset-0 overflow-hidden lg:inset-y-0 lg:left-auto lg:right-0 lg:h-full lg:w-auto lg:aspect-[2752/1536]">
                    <div class="hero-artwork-stage absolute inset-0">
                        {{-- Dark Theme Background Photography --}}
                        <img
                            src="{{ asset($heroDarkImage) }}"
                            alt=""
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
                                alt=""
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

                <!-- Dark Theme Gradient Scrim (protects left card and header while keeping right ship/plane/truck clear) -->
                <div class="absolute inset-0 opacity-0 dark:opacity-100 transition-opacity duration-300 ease-in-out" style="background: linear-gradient(90deg, rgba(6, 13, 26, 0.96) 0%, rgba(6, 13, 26, 0.86) 28%, rgba(6, 13, 26, 0.40) 46%, rgba(6, 13, 26, 0) 58%), linear-gradient(180deg, rgba(6, 13, 26, 0.28) 0%, rgba(6, 13, 26, 0) 90px);"></div>
                <div class="absolute inset-0 lg:hidden bg-[#060D1A]/75 opacity-0 dark:opacity-100 transition-opacity duration-300 ease-in-out"></div>

                <!-- Light Theme Gradient Scrim -->
                <div class="absolute inset-0 opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out" style="background: linear-gradient(90deg, rgba(238, 245, 251, 0.97) 0%, rgba(238, 245, 251, 0.88) 28%, rgba(238, 245, 251, 0.42) 46%, rgba(238, 245, 251, 0) 58%), linear-gradient(180deg, rgba(238, 245, 251, 0.35) 0%, rgba(238, 245, 251, 0) 90px);"></div>
                <div class="absolute inset-0 lg:hidden bg-[#EEF5FB]/80 opacity-100 dark:opacity-0 transition-opacity duration-300 ease-in-out"></div>
            </div>

            <!-- Top Header Bar -->
            <header class="relative z-20 w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 xl:px-14 pt-4 sm:pt-5 pb-2 flex items-center justify-between gap-2">
                <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2.5 sm:gap-3 group min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#2563EB] group-hover:bg-[#1D4ED8] text-white flex items-center justify-center shadow-sm transition-colors duration-200 shrink-0">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <span class="text-base sm:text-xl font-extrabold tracking-tight text-[#0B1728] dark:text-white block leading-none truncate">{{ config('app.name', 'Nusa Freight') }}</span>
                        <span class="hidden sm:block text-[11px] font-medium text-slate-600 dark:text-slate-300 leading-tight mt-0.5">{{ __('Global spice freight by sea, air and road') }}</span>
                    </div>
                </a>

                <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
                    <a href="{{ route('welcome') }}" aria-label="{{ __('Back to Portal') }}" class="inline-flex items-center gap-1.5 min-h-[44px] px-3 sm:px-3.5 py-2 rounded-xl text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8] bg-white/95 hover:bg-white active:bg-slate-100 dark:bg-[#0B1728]/90 dark:hover:bg-[#11233B] dark:active:bg-[#162D4A] border border-[#D5DEEA] dark:border-white/15 shadow-sm transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span class="hidden sm:inline">{{ __('Back to Portal') }}</span>
                    </a>

                    <div class="min-h-[44px] flex items-center">
                        <button
                            type="button"
                            id="@yield('auth-theme-toggle-id', 'auth-theme-toggle')"
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

                    <x-locale-switcher size="sm" />
                </div>
            </header>

            <!-- Main Content: Left-aligned on desktop (lg:), centered on mobile/tablet -->
            <main class="relative z-20 flex-1 flex items-center w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 xl:px-14 py-6 sm:py-8">
                @yield('content')
            </main>

            <!-- Bottom Attribution -->
            <footer class="relative z-20 w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-10 xl:px-14 pt-2 pb-4 sm:pb-5 text-center lg:text-left text-xs font-medium text-slate-700 dark:text-slate-300">
                <span>&copy; {{ date('Y') }} {{ config('app.name', 'Nusa Freight') }} &bull; {{ __('Global spice freight by sea, air and road') }}</span>
            </footer>
        </div>
    @else
        @yield('content')
    @endif

    @stack('scripts')
</body>
</html>

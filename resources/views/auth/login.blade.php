@extends('layouts.guest')

@section('content')
@php
    $heroLightImage = config('cargotrace.hero_images.light') ?? (file_exists(public_path('images/backgrounds/hero-light.webp')) ? 'images/backgrounds/hero-light.webp' : null);
@endphp

<div class="min-h-screen flex flex-col md:flex-row theme-page">
    <!-- Left: Maritime Photography Panel (Hidden on mobile, split on md+) -->
    <div class="hidden md:flex md:w-5/12 lg:w-1/2 relative flex-col justify-between p-8 lg:p-12 overflow-hidden border-r theme-border select-none">
        <!-- Background Imagery: Two <img> siblings toggled by [data-theme] in CSS -->
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            {{-- Dark Theme Image --}}
            <img
                src="{{ asset('images/backgrounds/bg-dark.webp') }}"
                alt="Container ship and gantry cranes at night"
                width="2560"
                height="1440"
                fetchpriority="high"
                loading="eager"
                decoding="sync"
                class="hero-img-dark w-full h-full object-cover [object-position:right_bottom] md:object-center filter brightness-95 contrast-105"
            >

            {{-- Light Theme Image (or pale gradient + SVG motif fallback) --}}
            @if($heroLightImage)
                <img
                    src="{{ asset($heroLightImage) }}"
                    alt="Daytime maritime container port operations"
                    width="2560"
                    height="1440"
                    fetchpriority="low"
                    loading="lazy"
                    decoding="async"
                    class="hero-img-light w-full h-full object-cover [object-position:right_bottom] md:object-center filter brightness-100 contrast-100"
                >
            @else
                <div class="hero-fallback-light w-full h-full bg-gradient-to-br from-[#F4F7FB] via-sky-50 to-slate-200 relative">
                    <x-svg-network-motif class="text-slate-500 opacity-10" />
                </div>
            @endif

            {{-- Per-Theme Overlays tuned for WCAG AA --}}
            <!-- Dark Overlay -->
            <div class="absolute inset-0 hidden dark:block bg-gradient-to-t from-[#050B14] via-[#050B14]/70 to-[#050B14]/30"></div>
            <!-- Light Overlay -->
            <div class="absolute inset-0 block dark:hidden bg-gradient-to-t from-[#F4F7FB] via-[#F4F7FB]/75 to-[#F4F7FB]/40"></div>
        </div>

        <!-- Top Left Brand in Photo Panel -->
        <div class="relative z-10">
            <a href="{{ route('welcome') }}" class="inline-flex items-center space-x-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform duration-200">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                    </svg>
                </div>
                <div>
                    <span class="text-xl font-extrabold tracking-tight theme-heading font-sans block leading-none">CargoTrace</span>
                    <span class="text-[9px] uppercase tracking-widest text-blue-600 dark:text-cyan-400 font-mono font-bold">Pure Maritime Tracking</span>
                </div>
            </a>
        </div>

        <!-- Bottom Caption in Photo Panel -->
        <div class="relative z-10 space-y-3 max-w-md">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full theme-card-elevated border theme-border text-[11px] font-mono text-blue-600 dark:text-cyan-400">
                <span class="w-2 h-2 rounded-full bg-blue-600 dark:bg-cyan-400 animate-pulse"></span>
                Ocean Container Consignment Telemetry
            </div>
            <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight theme-heading leading-snug">
                Precision maritime tracking across global container lanes.
            </h2>
            <p class="text-xs sm:text-sm theme-text-secondary leading-relaxed">
                Operator-recorded checkpoints, verified UN/LOCODE seaport milestones, and ETA calculations for international shipping lines.
            </p>
        </div>
    </div>

    <!-- Right: Login Form Column -->
    <div class="w-full md:w-7/12 lg:w-1/2 flex flex-col justify-between p-6 sm:p-10 lg:p-14 min-h-screen theme-page relative z-10">
        <!-- Top Controls: Back Link + Theme Switcher -->
        <div class="flex items-center justify-between pb-6">
            <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-xs font-semibold theme-text-secondary hover:theme-heading transition px-3.5 py-2 rounded-xl theme-card-elevated border theme-border">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Portal</span>
            </a>

            <!-- Theme Toggle Switcher -->
            <button
                type="button"
                id="login-theme-toggle"
                role="switch"
                aria-checked="true"
                aria-pressed="true"
                tabindex="0"
                onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.CargoTrace&&window.CargoTrace.toggleTheme?window.CargoTrace.toggleTheme():null}"
                class="relative flex items-center p-1 rounded-full bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 w-14 h-7 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                aria-label="Toggle dark and light theme"
                title="Switch theme"
            >
                <span class="w-5 h-5 flex items-center justify-center text-slate-400 dark:text-cyan-400 transition-colors">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                </span>
                <span class="w-5 h-5 flex items-center justify-center text-amber-500 dark:text-slate-500 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </button>
        </div>

        <!-- Center: Form Card -->
        <div class="max-w-md w-full mx-auto my-auto space-y-6 py-6">
            <div class="space-y-2">
                <!-- Mobile Logo Header (Shown only on small screens) -->
                <div class="md:hidden flex items-center space-x-3 pb-2">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 text-white flex items-center justify-center shadow-md">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-extrabold tracking-tight theme-heading font-sans block leading-none">CargoTrace</span>
                        <span class="text-[9px] uppercase tracking-widest text-blue-600 dark:text-cyan-400 font-mono font-bold">Pure Maritime Tracking</span>
                    </div>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading">Operations Sign In</h1>
                <p class="text-xs sm:text-sm theme-text-secondary">Enter credentials to manage shipments, seaports, and milestones.</p>
            </div>

            @if($errors->any())
                <div class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-600 dark:text-rose-300 text-xs flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label for="email" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="name@cargotrace.test"
                        class="w-full px-4 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                    >
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="uppercase text-xs tracking-wider theme-text-muted font-semibold">Password</label>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full px-4 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition font-mono"
                    >
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center space-x-2 theme-text-secondary cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded theme-input text-blue-600 focus:ring-0">
                        <span>Keep signed in</span>
                    </label>
                </div>

                <button
                    type="submit"
                    class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-lg shadow-blue-500/25 flex items-center justify-center gap-2 cursor-pointer"
                >
                    <span>Sign In to Platform</span>
                    <span>&rarr;</span>
                </button>
            </form>

            <!-- 1-Click Quick Demo Access (Admin, Logistics Operator, Customer) -->
            <div class="pt-4 border-t theme-border space-y-2.5">
                <span class="text-[10px] font-bold uppercase tracking-wider theme-text-muted text-center block">Quick Demo Access (1-Click Login)</span>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <button
                        type="button"
                        data-demo-login="admin@cargotrace.test"
                        onclick="autoLogin('admin@cargotrace.test')"
                        class="px-2.5 py-2.5 rounded-xl theme-card-elevated border border-blue-500/30 hover:border-blue-500 text-blue-600 dark:text-blue-400 font-semibold transition text-center shadow-sm cursor-pointer"
                    >
                        <span class="block text-[9px] theme-text-muted uppercase font-mono">Admin</span>
                        Director
                    </button>
                    <button
                        type="button"
                        data-demo-login="operator@cargotrace.test"
                        onclick="autoLogin('operator@cargotrace.test')"
                        class="px-2.5 py-2.5 rounded-xl theme-card-elevated border border-cyan-500/30 hover:border-cyan-500 text-cyan-700 dark:text-cyan-400 font-semibold transition text-center shadow-sm cursor-pointer"
                    >
                        <span class="block text-[9px] theme-text-muted uppercase font-mono">Operator</span>
                        Operations
                    </button>
                    <button
                        type="button"
                        data-demo-login="customer@cargotrace.test"
                        onclick="autoLogin('customer@cargotrace.test')"
                        class="px-2.5 py-2.5 rounded-xl theme-card-elevated border border-emerald-500/30 hover:border-emerald-500 text-emerald-600 dark:text-emerald-400 font-semibold transition text-center shadow-sm cursor-pointer"
                    >
                        <span class="block text-[9px] theme-text-muted uppercase font-mono">Customer</span>
                        Shipper
                    </button>
                </div>
            </div>

            <div class="text-center text-xs theme-text-secondary pt-2 border-t theme-border">
                Don't have an enterprise account?
                <a href="{{ route('register') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold ml-1">Register here</a>
            </div>
        </div>

        <!-- Bottom: Honest Attribution -->
        <div class="pt-6 border-t theme-border flex items-center justify-between text-[11px] theme-text-muted">
            <span>&copy; {{ date('Y') }} CargoTrace &bull; Ocean Container Platform</span>
            <span>Academic Capstone</span>
        </div>
    </div>
</div>

<script>
    function autoLogin(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
        document.querySelector('form').submit();
    }
</script>
@endsection

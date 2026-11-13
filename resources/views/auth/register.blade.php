@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6 theme-page overflow-hidden">
    {{-- Faint SVG Network Motif Background (No raster photography per DESIGN.md) --}}
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden" aria-hidden="true">
        <x-svg-network-motif class="text-slate-500 dark:text-slate-300" />
    </div>

    <!-- Top Floating Controls (Theme Switcher & Back Link) -->
    <div class="absolute top-6 inset-x-0 max-w-6xl mx-auto px-6 flex items-center justify-between z-20">
        <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-xs font-semibold theme-text-secondary hover:theme-heading transition px-3.5 py-2 rounded-xl theme-card-elevated border theme-border">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Maritime Portal</span>
        </a>

        <!-- Theme Toggle Switcher -->
        <button
            type="button"
            id="register-theme-toggle"
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

    <!-- Center Card with Unified Maritime Design System -->
    <div class="relative z-10 w-full max-w-lg p-6 sm:p-8 rounded-2xl theme-card border theme-border shadow-2xl space-y-6 my-16">
        <!-- Unified Brand Header -->
        <div class="text-center space-y-2.5">
            <a href="{{ route('welcome') }}" class="inline-flex items-center space-x-3 group transition-transform duration-200 hover:scale-[1.02]">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition">
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                    </svg>
                </div>
                <div class="text-left">
                    <span class="text-2xl font-extrabold tracking-tight theme-heading font-sans block leading-none">CargoTrace</span>
                    <span class="text-[9px] uppercase tracking-widest text-cyan-500 dark:text-cyan-400 font-mono font-bold">Maritime Logistics Intelligence</span>
                </div>
            </a>
            <h2 class="text-xl font-bold theme-heading tracking-tight pt-1">Create Shipper Account</h2>
            <p class="text-xs theme-text-secondary">Join global ocean supply chains with end-to-end multi-currency visibility.</p>
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-600 dark:text-rose-300 text-xs flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="name" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Full Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="John Doe" class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition">
                </div>

                <div class="space-y-1.5">
                    <label for="email" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Work Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="john@enterprise.com" class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="company_name" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Company Name</label>
                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" placeholder="Pacific Trade Ltd" class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition">
                </div>

                <div class="space-y-1.5">
                    <label for="phone" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+1-555-0199" class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="password" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Min 8 characters" class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition font-mono">
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password" class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition font-mono">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-lg shadow-blue-500/25 flex items-center justify-center gap-2">
                <span>Complete Registration</span>
                <span>&rarr;</span>
            </button>
        </form>

        <div class="text-center text-xs theme-text-secondary pt-2 border-t theme-border">
            Already have an active account?
            <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold ml-1">Sign In</a>
        </div>
    </div>
</div>
@endsection

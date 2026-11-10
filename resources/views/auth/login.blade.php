@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6 theme-page overflow-hidden">
    <!-- Cinematic Sunset Maritime Container Port Backdrop -->
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('images/hero-sunset-terminal.jpg') }}" alt="Container ship moored at deepwater container terminal" class="w-full h-full object-cover filter brightness-75 contrast-110">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/60 to-slate-950/40"></div>
    </div>

    <!-- Top Floating Controls (Theme Switcher & Back Link) -->
    <div class="absolute top-6 inset-x-0 max-w-6xl mx-auto px-6 flex items-center justify-between z-20">
        <a href="{{ route('welcome') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-300 hover:text-white transition bg-slate-900/60 hover:bg-slate-900/80 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/10">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to Maritime Portal</span>
        </a>

        <!-- Theme Toggle Switcher -->
        <button
            type="button"
            onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
            class="relative flex items-center p-1 rounded-full bg-slate-900/80 backdrop-blur-md border border-white/15 w-14 h-7 transition-colors duration-200 focus:outline-none"
            aria-label="Toggle dark and light theme"
            title="Switch theme"
        >
            <span class="w-5 h-5 flex items-center justify-center text-cyan-400">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
            </span>
            <span class="w-5 h-5 flex items-center justify-center text-slate-400">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </span>
        </button>
    </div>

    <!-- Center Login Card with Unified Maritime Style -->
    <div class="relative z-10 w-full max-w-md p-6 sm:p-8 rounded-2xl theme-card border theme-border shadow-2xl space-y-6 my-16">
        <!-- Unified Brand Header -->
        <div class="text-center space-y-2.5">
            <a href="{{ route('welcome') }}" class="inline-flex items-center space-x-3 group transition-transform duration-200 hover:scale-[1.02]">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 text-white flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition">
                    <!-- Unified Inverted Triangle / Maritime Shield Logo -->
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                    </svg>
                </div>
                <div class="text-left">
                    <span class="text-2xl font-extrabold tracking-tight theme-heading font-sans block leading-none">CargoTrace</span>
                    <span class="text-[9px] uppercase tracking-widest text-cyan-500 dark:text-cyan-400 font-mono font-bold">Maritime Logistics Intelligence</span>
                </div>
            </a>
            <h2 class="text-xl font-bold theme-heading tracking-tight pt-1">Access Visibility Command</h2>
            <p class="text-xs theme-text-secondary">Sign in to monitor fleet, checkpoints, and maritime telemetry.</p>
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-600 dark:text-rose-300 text-xs flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <label for="email" class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@cargotrace.test" class="w-full px-4 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition">
            </div>

            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="uppercase text-xs tracking-wider theme-text-muted font-semibold">Password</label>
                </div>
                <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl theme-input border text-xs sm:text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition font-mono">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center space-x-2 theme-text-secondary cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded theme-input text-blue-600 focus:ring-0">
                    <span>Keep signed in</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-lg shadow-blue-500/25 flex items-center justify-center gap-2">
                <span>Sign In to Platform</span>
                <span>&rarr;</span>
            </button>
        </form>

        <!-- 1-Click Quick Demo Access (Admin, Logistics Operator, Customer) -->
        <div class="pt-4 border-t theme-border space-y-2.5">
            <span class="text-[10px] font-bold uppercase tracking-wider theme-text-muted text-center block">Quick Demo Access (1-Click Login)</span>
            <div class="grid grid-cols-3 gap-2 text-xs">
                <button type="button" data-demo-login="admin@cargotrace.test" onclick="autoLogin('admin@cargotrace.test')" class="px-2.5 py-2.5 rounded-xl theme-card-elevated border border-blue-500/30 hover:bg-blue-500/10 text-blue-600 dark:text-blue-400 font-semibold transition text-center shadow-sm">
                    <span class="block text-[9px] theme-text-muted uppercase font-mono">Admin</span>
                    Director
                </button>
                <button type="button" data-demo-login="operator@cargotrace.test" onclick="autoLogin('operator@cargotrace.test')" class="px-2.5 py-2.5 rounded-xl theme-card-elevated border border-cyan-500/30 hover:bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-semibold transition text-center shadow-sm">
                    <span class="block text-[9px] theme-text-muted uppercase font-mono">Operator</span>
                    Logistics Operator
                </button>
                <button type="button" data-demo-login="customer@cargotrace.test" onclick="autoLogin('customer@cargotrace.test')" class="px-2.5 py-2.5 rounded-xl theme-card-elevated border border-emerald-500/30 hover:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-semibold transition text-center shadow-sm">
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
</div>

<script>
    function autoLogin(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
        document.querySelector('form').submit();
    }
</script>
@endsection

@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[#050B14] overflow-hidden">
    <!-- Full-Screen Cargo Ship Maritime Backdrop -->
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=2000&q=80" alt="Container ship in ocean at twilight" class="w-full h-full object-cover opacity-25 filter brightness-75 contrast-125">
        <div class="absolute inset-0 bg-gradient-to-tr from-[#050B14] via-[#050B14]/90 to-transparent"></div>
    </div>

    <!-- Center Card with Strict Enterprise Glassmorphism -->
    <div class="relative z-10 w-full max-w-md p-6 sm:p-8 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-6">
        <!-- Brand Header -->
        <div class="text-center space-y-2">
            <a href="{{ route('welcome') }}" class="inline-flex items-center space-x-2.5 group transition-transform duration-200 hover:scale-[1.02]">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 via-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25 group-hover:scale-105 transition">
                    <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">CargoTrace</span>
            </a>
            <h2 class="text-xl font-bold text-white tracking-tight">Access Visibility Command</h2>
            <p class="text-xs text-slate-400">Sign in to monitor fleet, checkpoints, and maritime telemetry.</p>
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Login Form -->
        <form action="{{ route('login') }}" method="POST" class="space-y-4">
            @csrf

            <div class="space-y-1.5">
                <label for="email" class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">Email Address</label>
                <div class="relative">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@cargotrace.test" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>
            </div>

            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="uppercase text-xs tracking-wider text-slate-500 font-semibold">Password</label>
                </div>
                <div class="relative">
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full px-4 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center space-x-2 text-slate-400 cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-0">
                    <span>Keep signed in</span>
                </label>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/25">
                Sign In to Platform &rarr;
            </button>
        </form>

        <!-- 1-Click Quick Demo Access -->
        <div class="pt-4 border-t border-white/10 space-y-2.5">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 text-center block">Quick Demo Access (1-Click Login)</span>
            <div class="grid grid-cols-3 gap-2.5 text-xs">
                <button type="button" onclick="autoLogin('admin@cargotrace.test')" class="px-2.5 py-2.5 rounded-xl bg-slate-950/80 border border-amber-500/30 hover:bg-amber-500/10 text-amber-400 font-semibold transition-all duration-200 hover:scale-[1.02] text-center shadow-md">
                    <span class="block text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Director</span>
                    Admin
                </button>
                <button type="button" onclick="autoLogin('operator@cargotrace.test')" class="px-2.5 py-2.5 rounded-xl bg-slate-950/80 border border-teal-500/30 hover:bg-teal-500/10 text-teal-400 font-semibold transition-all duration-200 hover:scale-[1.02] text-center shadow-md">
                    <span class="block text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Port Ops</span>
                    Operator
                </button>
                <button type="button" onclick="autoLogin('customer@cargotrace.test')" class="px-2.5 py-2.5 rounded-xl bg-slate-950/80 border border-blue-500/30 hover:bg-blue-500/10 text-blue-400 font-semibold transition-all duration-200 hover:scale-[1.02] text-center shadow-md">
                    <span class="block text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Shipper</span>
                    Customer
                </button>
            </div>
        </div>

        <div class="text-center text-xs text-slate-400 pt-2 border-t border-white/5">
            Don't have an enterprise account?
            <a href="{{ route('register') }}" class="text-amber-400 hover:underline font-semibold ml-1">Register here</a>
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

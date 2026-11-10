@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen flex items-center justify-center p-4 sm:p-6 bg-[#050B14] overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=2000&q=80" alt="Maritime port twilight" class="w-full h-full object-cover opacity-25 filter brightness-75 contrast-125">
        <div class="absolute inset-0 bg-gradient-to-tr from-[#050B14] via-[#050B14]/90 to-transparent"></div>
    </div>

    <!-- Center Card with Strict Enterprise Glassmorphism -->
    <div class="relative z-10 w-full max-w-lg p-6 sm:p-8 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-6">
        <!-- Brand Header -->
        <div class="text-center space-y-2">
            <a href="{{ route('welcome') }}" class="inline-flex items-center space-x-2.5 transition-transform duration-200 hover:scale-[1.02]">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/25">
                    <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">CargoTrace</span>
            </a>
            <h2 class="text-xl font-bold text-white tracking-tight">Create Shipper Account</h2>
            <p class="text-xs text-slate-400">Join global supply chains with end-to-end multi-currency visibility.</p>
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="name" class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">Full Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="John Doe" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>

                <div class="space-y-1.5">
                    <label for="email" class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">Work Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="john@enterprise.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="company_name" class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">Company Name</label>
                    <input type="text" id="company_name" name="company_name" value="{{ old('company_name') }}" placeholder="Pacific Trade Ltd" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>

                <div class="space-y-1.5">
                    <label for="phone" class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">Phone</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+1-555-0199" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="password" class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Min 8 characters" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Repeat password" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white text-xs sm:text-sm placeholder-slate-500 focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all duration-200">
                </div>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/25">
                Complete Registration &rarr;
            </button>
        </form>

        <div class="text-center text-xs text-slate-400 pt-2 border-t border-white/10">
            Already have an active account?
            <a href="{{ route('login') }}" class="text-amber-400 hover:underline font-semibold ml-1">Sign In</a>
        </div>
    </div>
</div>
@endsection

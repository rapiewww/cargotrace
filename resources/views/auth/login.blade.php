@extends('layouts.guest')
@section('has-locale-switcher', '1')
@section('auth-shell', '1')
@section('auth-theme-toggle-id', 'login-theme-toggle')

@section('content')
<div class="w-full max-w-[27.5rem] mx-auto lg:mx-0 p-6 sm:p-8 rounded-2xl bg-white/95 dark:bg-[#0B1728]/90 backdrop-blur-md border border-[#D5DEEA] dark:border-white/15 shadow-xl shadow-slate-900/10 dark:shadow-2xl dark:shadow-black/50 space-y-6 transition-colors duration-200">
    <div class="space-y-1.5 text-left">
        <h1 class="text-2xl sm:text-[1.75rem] font-extrabold tracking-tight text-[#0B1728] dark:text-white leading-tight">{{ __('Operations Sign In') }}</h1>
        <p class="text-sm text-[#52647A] dark:text-slate-300">{{ __('Global spice freight by sea, air and road.') }}</p>
    </div>

    @if($errors->any())
        <div role="alert" class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-500/40 text-rose-800 dark:text-rose-200 text-xs font-medium flex items-start gap-2.5">
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.11 21h17.78a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <!-- Login Form -->
    <form id="login-form" action="{{ route('login') }}" method="POST" class="space-y-4">
        @csrf

        <div class="space-y-1.5">
            <label for="email" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Email Address') }}</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                value="{{ old('email') }}"
                required
                autofocus
                placeholder="name@cargotrace.test"
                class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
            >
        </div>

        <div class="space-y-1.5">
            <label for="password" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Password') }}</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
                placeholder="••••••••"
                class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
            >
        </div>

        <div class="flex items-center justify-between">
            <label class="inline-flex items-center gap-2.5 min-h-[44px] text-xs font-medium text-[#52647A] dark:text-slate-300 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#CBD5E1] dark:border-white/20 text-[#2563EB] focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400">
                <span>{{ __('Keep signed in') }}</span>
            </label>
        </div>

        <button
            type="submit"
            class="w-full min-h-[44px] py-3 px-4 rounded-xl bg-[#2563EB] hover:bg-[#1D4ED8] active:bg-blue-800 text-white font-semibold text-sm transition-colors duration-200 shadow-sm flex items-center justify-center cursor-pointer"
        >
            {{ __('Sign In to Platform') }}
        </button>
    </form>

    <!-- 1-Click Quick Demo Access (Admin, Logistics Operator, Customer) -->
    <div class="pt-4 border-t border-[#D5DEEA] dark:border-white/10 space-y-2.5">
        <p class="text-xs font-semibold text-[#52647A] dark:text-slate-300 text-center">{{ __('Quick Demo Access (1-Click Login)') }}</p>
        <div class="grid grid-cols-3 gap-2 text-xs">
            <button
                type="button"
                data-demo-login="admin@cargotrace.test"
                onclick="autoLogin('admin@cargotrace.test')"
                class="min-h-[48px] px-2.5 py-2.5 rounded-xl bg-slate-50 hover:bg-blue-50 active:bg-blue-100 dark:bg-[#11233B] dark:hover:bg-[#162D4A] dark:active:bg-[#1B3759] border border-blue-200 hover:border-blue-500 dark:border-blue-400/30 dark:hover:border-blue-400 text-blue-700 dark:text-blue-300 font-semibold transition-colors text-center shadow-xs cursor-pointer"
            >
                <span class="block text-[11px] font-medium text-[#475569] dark:text-slate-300">{{ __('Admin') }}</span>
                <span>{{ __('Director') }}</span>
            </button>
            <button
                type="button"
                data-demo-login="operator@cargotrace.test"
                onclick="autoLogin('operator@cargotrace.test')"
                class="min-h-[48px] px-2.5 py-2.5 rounded-xl bg-slate-50 hover:bg-cyan-50 active:bg-cyan-100 dark:bg-[#11233B] dark:hover:bg-[#162D4A] dark:active:bg-[#1B3759] border border-cyan-200 hover:border-cyan-600 dark:border-cyan-400/30 dark:hover:border-cyan-400 text-cyan-800 dark:text-cyan-300 font-semibold transition-colors text-center shadow-xs cursor-pointer"
            >
                <span class="block text-[11px] font-medium text-[#475569] dark:text-slate-300">{{ __('Operator') }}</span>
                <span>{{ __('Operations') }}</span>
            </button>
            <button
                type="button"
                data-demo-login="customer@cargotrace.test"
                onclick="autoLogin('customer@cargotrace.test')"
                class="min-h-[48px] px-2.5 py-2.5 rounded-xl bg-slate-50 hover:bg-emerald-50 active:bg-emerald-100 dark:bg-[#11233B] dark:hover:bg-[#162D4A] dark:active:bg-[#1B3759] border border-emerald-200 hover:border-emerald-600 dark:border-emerald-400/30 dark:hover:border-emerald-400 text-emerald-700 dark:text-emerald-300 font-semibold transition-colors text-center shadow-xs cursor-pointer"
            >
                <span class="block text-[11px] font-medium text-[#475569] dark:text-slate-300">{{ __('Customer') }}</span>
                <span>{{ __('Shipper') }}</span>
            </button>
        </div>
    </div>

    <div class="text-center text-xs text-[#52647A] dark:text-slate-300 pt-3 border-t border-[#D5DEEA] dark:border-white/10">
        {{ __("Don't have an enterprise account?") }}
        <a href="{{ route('register') }}" class="text-[#1D4ED8] dark:text-cyan-400 hover:underline font-semibold ml-1">{{ __('Register here') }}</a>
    </div>
</div>

<script>
    function autoLogin(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
        document.getElementById('login-form').submit();
    }
</script>
@endsection

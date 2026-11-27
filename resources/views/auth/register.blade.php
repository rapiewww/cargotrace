@extends('layouts.guest')
@section('has-locale-switcher', '1')
@section('auth-shell', '1')
@section('auth-theme-toggle-id', 'register-theme-toggle')

@section('content')
<div class="w-full max-w-[31rem] mx-auto lg:mx-0 p-6 sm:p-8 rounded-2xl bg-white/95 dark:bg-[#0B1728]/90 backdrop-blur-md border border-[#D5DEEA] dark:border-white/15 shadow-xl shadow-slate-900/10 dark:shadow-2xl dark:shadow-black/50 space-y-5 transition-colors duration-200">
    <div class="space-y-1.5 text-left">
        <h1 class="text-2xl sm:text-[1.75rem] font-extrabold tracking-tight text-[#0B1728] dark:text-white leading-tight">{{ __('Create Shipper Account') }}</h1>
        <p class="text-sm text-[#52647A] dark:text-slate-300">{{ __('Track sea, air, and road spice consignments with multi-currency visibility.') }}</p>
    </div>

    @if($errors->any())
        <div role="alert" class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-300 dark:border-rose-500/40 text-rose-800 dark:text-rose-200 text-xs font-medium flex items-start gap-2.5">
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-8.48 14.7A1.5 1.5 0 003.11 21h17.78a1.5 1.5 0 001.3-2.44l-8.48-14.7a1.5 1.5 0 00-2.6 0z"/>
            </svg>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form action="{{ route('register') }}" method="POST" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div class="space-y-1.5">
                <label for="name" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Full Name') }}</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    autocomplete="name"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    placeholder="John Doe"
                    class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
                >
            </div>

            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Work Email') }}</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="email"
                    value="{{ old('email') }}"
                    required
                    placeholder="john@enterprise.com"
                    class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
                >
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div class="space-y-1.5">
                <label for="company_name" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Company Name') }}</label>
                <input
                    type="text"
                    id="company_name"
                    name="company_name"
                    autocomplete="organization"
                    value="{{ old('company_name') }}"
                    placeholder="Pacific Trade Ltd"
                    class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
                >
            </div>

            <div class="space-y-1.5">
                <label for="phone" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Phone Number') }}</label>
                <input
                    type="text"
                    id="phone"
                    name="phone"
                    autocomplete="tel"
                    value="{{ old('phone') }}"
                    placeholder="+1-555-0199"
                    class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
                >
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Password') }}</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    required
                    placeholder="{{ __('Min 8 characters') }}"
                    class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
                >
            </div>

            <div class="space-y-1.5">
                <label for="password_confirmation" class="block text-xs font-semibold text-[#0B1728] dark:text-[#E6EEF8]">{{ __('Confirm Password') }}</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                    placeholder="{{ __('Repeat password') }}"
                    class="w-full min-h-[44px] px-3.5 py-2.5 rounded-xl theme-input border text-sm focus:outline-none focus:ring-2 focus:ring-[#2563EB] dark:focus:ring-cyan-400 transition-colors"
                >
            </div>
        </div>

        <button
            type="submit"
            class="w-full min-h-[44px] py-3 px-4 rounded-xl bg-[#2563EB] hover:bg-[#1D4ED8] active:bg-blue-800 text-white font-semibold text-sm transition-colors duration-200 shadow-sm flex items-center justify-center cursor-pointer"
        >
            {{ __('Complete Registration') }}
        </button>
    </form>

    <div class="text-center text-xs text-[#52647A] dark:text-slate-300 pt-3 border-t border-[#D5DEEA] dark:border-white/10">
        {{ __('Already have an active account?') }}
        <a href="{{ route('login') }}" class="text-[#1D4ED8] dark:text-cyan-400 hover:underline font-semibold ml-1">{{ __('Sign In') }}</a>
    </div>
</div>
@endsection

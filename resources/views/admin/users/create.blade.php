@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-3xl mx-auto">
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs theme-text-muted font-mono">
            <a href="{{ route('admin.users.index') }}" class="hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">{{ __('Users') }}</a>
            <span>&rarr;</span>
            <span class="text-blue-600 dark:text-cyan-400">{{ __('Create User') }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
            {{ __('Add New Platform User') }}
        </h1>
        <p class="text-xs sm:text-sm theme-text-secondary">
            {{ __('Create account credentials, assign roles (Admin, Operator, Customer), and company affiliations.') }}
        </p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs space-y-1">
            <div class="font-bold">{{ __('Please correct the following errors:') }}</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card>
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5 text-xs">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="create-user-name" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Full Name') }} *</label>
                    <input id="create-user-name" type="text" name="name" value="{{ old('name') }}" required placeholder="Jane Doe" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="space-y-1.5">
                    <label for="create-user-email" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Email Address') }} *</label>
                    <input id="create-user-email" type="email" name="email" value="{{ old('email') }}" required placeholder="jane@cargotrace.test" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label for="create-user-role" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Role') }} *</label>
                    <select id="create-user-role" name="role" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="customer" {{ old('role') === 'customer' ? 'selected' : '' }}>{{ __('Customer (Shipper)') }}</option>
                        <option value="operator" {{ old('role') === 'operator' ? 'selected' : '' }}>{{ __('Operator (Port Operations)') }}</option>
                        <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>{{ __('Admin (System Director)') }}</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label for="create-user-company" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Company Name') }}</label>
                    <input id="create-user-company" type="text" name="company_name" value="{{ old('company_name') }}" placeholder="Pacific Logistics Co." class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="space-y-1.5">
                    <label for="create-user-phone" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Phone Number') }}</label>
                    <input id="create-user-phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="+1-555-0199" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="create-user-currency" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Preferred Currency') }}</label>
                    <select id="create-user-currency" name="preferred_currency" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="IDR">IDR</option>
                        <option value="SGD">SGD</option>
                        <option value="CNY">CNY</option>
                        <option value="GBP">GBP</option>
                    </select>
                </div>
                <div class="space-y-1.5 flex flex-col justify-end">
                    <label class="flex items-center space-x-2 theme-text-primary cursor-pointer pb-2.5">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="rounded theme-input border theme-border text-blue-600">
                        <span>{{ __('Account is active & enabled') }}</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t theme-border">
                <div class="space-y-1.5">
                    <label for="create-user-password" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Password (min 8 chars)') }} *</label>
                    <input id="create-user-password" type="password" name="password" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="space-y-1.5">
                    <label for="create-user-password-confirm" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Confirm Password') }} *</label>
                    <input id="create-user-password-confirm" type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 border-t theme-border flex items-center justify-between">
                <a href="{{ route('admin.users.index') }}" class="theme-text-secondary hover:theme-heading transition">{{ __('Cancel') }}</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-md shadow-blue-500/20 cursor-pointer">
                    {{ __('Create User Account') }} &rarr;
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection

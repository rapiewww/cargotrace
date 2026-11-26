@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-3xl mx-auto">
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs theme-text-muted font-mono">
            <a href="{{ route('admin.users.index') }}" class="hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">{{ __('Users') }}</a>
            <span>&rarr;</span>
            <span class="text-blue-600 dark:text-cyan-400">{{ __('Edit User') }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
            {{ __('Edit User: :name', ['name' => $user->name]) }}
        </h1>
        <p class="text-xs sm:text-sm theme-text-secondary">
            {{ __('Update account details, role permissions, or reset access password.') }}
        </p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs space-y-1">
            <div class="font-bold">{{ __('Please correct the following:') }}</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card>
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-5 text-xs">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="edit-user-name" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Full Name') }} *</label>
                    <input id="edit-user-name" type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="space-y-1.5">
                    <label for="edit-user-email" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Email Address') }} *</label>
                    <input id="edit-user-email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label for="edit-user-role" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Role') }} *</label>
                    <select id="edit-user-role" name="role" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="customer" {{ old('role', $user->role) === 'customer' ? 'selected' : '' }}>{{ __('Customer (Shipper)') }}</option>
                        <option value="operator" {{ old('role', $user->role) === 'operator' ? 'selected' : '' }}>{{ __('Operator (Port Operations)') }}</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>{{ __('Admin (System Director)') }}</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label for="edit-user-company" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Company Name') }}</label>
                    <input id="edit-user-company" type="text" name="company_name" value="{{ old('company_name', $user->company_name) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="space-y-1.5">
                    <label for="edit-user-phone" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Phone Number') }}</label>
                    <input id="edit-user-phone" type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="edit-user-currency" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Preferred Currency') }}</label>
                    <select id="edit-user-currency" name="preferred_currency" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        @foreach(['USD', 'EUR', 'IDR', 'SGD', 'CNY', 'GBP'] as $curr)
                            <option value="{{ $curr }}" {{ old('preferred_currency', $user->preferred_currency) === $curr ? 'selected' : '' }}>{{ $curr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5 flex flex-col justify-end">
                    <label class="flex items-center space-x-2 theme-text-primary cursor-pointer pb-2.5">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="rounded theme-input border theme-border text-blue-600">
                        <span>{{ __('Account is active & enabled') }}</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t theme-border">
                <div class="space-y-1.5">
                    <label for="edit-user-password" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('New Password (leave blank to keep current)') }}</label>
                    <input id="edit-user-password" type="password" name="password" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <div class="space-y-1.5">
                    <label for="edit-user-password-confirm" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Confirm New Password') }}</label>
                    <input id="edit-user-password-confirm" type="password" name="password_confirmation" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 border-t theme-border flex items-center justify-between">
                <a href="{{ route('admin.users.index') }}" class="theme-text-secondary hover:theme-heading transition">{{ __('Cancel') }}</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-md shadow-blue-500/20 cursor-pointer">
                    {{ __('Update User Account') }} &rarr;
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection

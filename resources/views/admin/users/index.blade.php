@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    {{ __('User & Role Management') }}
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30 font-mono">
                    {{ __('Admin Portal (:count)', ['count' => $users->total()]) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                {{ __('Configure platform users, access roles (Admin, Operator, Customer), and account credentials.') }}
            </p>
        </div>

        <a href="{{ route('admin.users.create') }}" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-1.5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-md shadow-blue-500/25">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('Add New User') }}
        </a>
    </div>

    <!-- Filter Bar -->
    <x-card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
            <div class="space-y-1.5">
                <label for="user-filter-search" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Search Name / Email / Company') }}</label>
                <input id="user-filter-search" type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('e.g. John Doe...') }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
            </div>

            <div class="space-y-1.5">
                <label for="user-filter-role" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Role') }}</label>
                <select id="user-filter-role" name="role" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    <option value="">{{ __('All Roles') }}</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                    <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>{{ __('Operator') }}</option>
                    <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>{{ __('Customer') }}</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label for="user-filter-status" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">{{ __('Account Status') }}</label>
                <select id="user-filter-status" name="is_active" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    <option value="">{{ __('All Accounts') }}</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>{{ __('Inactive Only') }}</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition cursor-pointer">
                    {{ __('Filter Users') }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2.5 rounded-xl theme-card-elevated theme-text-secondary hover:theme-heading border theme-border transition whitespace-nowrap">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </x-card>

    <!-- Users Table -->
    <x-card :padded="false" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="theme-card-elevated theme-text-muted uppercase tracking-wider text-[10px] border-b theme-border font-mono">
                    <tr>
                        <th class="py-3.5 px-4">{{ __('User') }}</th>
                        <th class="py-3.5 px-4">{{ __('Role') }}</th>
                        <th class="py-3.5 px-4">{{ __('Company') }}</th>
                        <th class="py-3.5 px-4">{{ __('Contact') }}</th>
                        <th class="py-3.5 px-4">{{ __('Currency') }}</th>
                        <th class="py-3.5 px-4">{{ __('Status') }}</th>
                        <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border theme-text-primary">
                    @forelse($users as $u)
                        <tr class="hover:bg-blue-600/5 transition-colors">
                            <td class="py-4 px-4">
                                <span class="font-bold theme-heading block">{{ $u->name }}</span>
                                <span class="text-[11px] theme-text-muted font-mono">{{ $u->email }}</span>
                            </td>
                            <td class="py-4 px-4 font-mono">
                                @php
                                    $roleBadge = match($u->role) {
                                        'admin' => 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30',
                                        'operator' => 'bg-cyan-500/15 text-cyan-700 dark:text-cyan-300 border-cyan-500/30',
                                        default => 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border-blue-500/30'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $roleBadge }}">
                                    {{ __(ucfirst($u->role)) }}
                                </span>
                            </td>
                            <td class="py-4 px-4 theme-text-primary">
                                {{ $u->company_name ?? '-' }}
                            </td>
                            <td class="py-4 px-4 font-mono theme-text-secondary">
                                {{ $u->phone ?? '-' }}
                            </td>
                            <td class="py-4 px-4 font-mono font-semibold text-cyan-700 dark:text-cyan-400">
                                {{ $u->preferred_currency }}
                            </td>
                            <td class="py-4 px-4">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap space-x-2">
                                <a href="{{ route('admin.users.edit', $u) }}" class="inline-flex px-3 py-1 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-700 dark:text-cyan-300 border border-blue-500/30 text-xs font-semibold transition">
                                    {{ __('Edit') }}
                                </a>
                                @if(auth()->id() !== $u->id)
                                    <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm(@js(__('Are you sure you want to delete this user?')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex px-2.5 py-1 rounded-lg text-rose-700 dark:text-rose-400 hover:bg-rose-500/10 border theme-border text-xs transition cursor-pointer">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center theme-text-muted">{{ __('No users found matching query.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t theme-border theme-card-elevated">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection

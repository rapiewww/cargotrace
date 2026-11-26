@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white font-sans">
                    {{ __('User & Role Management') }}
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 font-mono">
                    {{ __('Admin Portal (:count)', ['count' => $users->total()]) }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                {{ __('Configure platform users, access roles (Admin, Operator, Customer), and account credentials.') }}
            </p>
        </div>

        <a href="{{ route('admin.users.create') }}" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/20">
            <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('Add New User') }}
        </a>
    </div>

    <!-- Filter Bar -->
    <x-card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">{{ __('Search Name / Email / Company') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('e.g. John Doe...') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500 focus:outline-none transition">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">{{ __('Role') }}</label>
                <select name="role" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500 focus:outline-none transition">
                    <option value="">{{ __('All Roles') }}</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                    <option value="operator" {{ request('role') === 'operator' ? 'selected' : '' }}>{{ __('Operator') }}</option>
                    <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>{{ __('Customer') }}</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">{{ __('Account Status') }}</label>
                <select name="is_active" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500 focus:outline-none transition">
                    <option value="">{{ __('All Accounts') }}</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>{{ __('Active Only') }}</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>{{ __('Inactive Only') }}</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold text-xs transition cursor-pointer">
                    {{ __('Filter Users') }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-950/70 text-slate-400 hover:text-white border border-slate-800 transition">
                    {{ __('Reset') }}
                </a>
            </div>
        </form>
    </x-card>

    <!-- Users Table -->
    <x-card :padded="false" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-white/10 font-mono">
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
                <tbody class="divide-y divide-white/5 text-slate-200">
                    @forelse($users as $u)
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="py-4 px-4">
                                <span class="font-bold text-white block">{{ $u->name }}</span>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $u->email }}</span>
                            </td>
                            <td class="py-4 px-4 font-mono">
                                @php
                                    $roleBadge = match($u->role) {
                                        'admin' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                        'operator' => 'bg-teal-500/20 text-teal-300 border-teal-500/30',
                                        default => 'bg-blue-500/20 text-blue-300 border-blue-500/30'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border {{ $roleBadge }}">
                                    {{ __(ucfirst($u->role)) }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                {{ $u->company_name ?? '-' }}
                            </td>
                            <td class="py-4 px-4 font-mono text-slate-400">
                                {{ $u->phone ?? '-' }}
                            </td>
                            <td class="py-4 px-4 font-mono text-teal-300">
                                {{ $u->preferred_currency }}
                            </td>
                            <td class="py-4 px-4">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> {{ __('Active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/15 text-rose-300 border border-rose-500/30 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> {{ __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap space-x-2">
                                <a href="{{ route('admin.users.edit', $u) }}" class="inline-flex px-3 py-1 rounded-lg bg-teal-500/10 hover:bg-teal-500/20 text-teal-300 border border-teal-500/30 text-xs font-semibold transition">
                                    {{ __('Edit') }}
                                </a>
                                @if(auth()->id() !== $u->id)
                                    <form action="{{ route('admin.users.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm(@js(__('Are you sure you want to delete this user?')));">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex px-2.5 py-1 rounded-lg text-rose-400 hover:bg-rose-500/10 border border-white/5 text-xs transition cursor-pointer">
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">{{ __('No users found matching query.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-white/10 bg-slate-950/60">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection


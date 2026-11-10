@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-3xl mx-auto">
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs text-slate-400 font-mono">
            <a href="{{ route('admin.users.index') }}" class="hover:text-teal-300 transition-colors">Users</a>
            <span>&rarr;</span>
            <span class="text-teal-300">Edit User</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white font-sans">
            Edit User: {{ $user->name }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-400">
            Update account details, role permissions, or reset access password.
        </p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            <div class="font-bold">Please correct the following:</div>
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
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Full Name *</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Email Address *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Role *</label>
                    <select name="role" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                        <option value="customer" {{ old('role', $user->role) === 'customer' ? 'selected' : '' }}>Customer (Shipper)</option>
                        <option value="operator" {{ old('role', $user->role) === 'operator' ? 'selected' : '' }}>Operator (Port Operations)</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin (System Director)</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Company Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $user->company_name) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Preferred Currency</label>
                    <select name="preferred_currency" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                        @foreach(['USD', 'EUR', 'IDR', 'SGD', 'CNY', 'GBP'] as $curr)
                            <option value="{{ $curr }}" {{ old('preferred_currency', $user->preferred_currency) === $curr ? 'selected' : '' }}>{{ $curr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="space-y-1.5 flex flex-col justify-end">
                    <label class="flex items-center space-x-2 text-slate-300 cursor-pointer pb-2.5">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="rounded bg-slate-950 border-slate-800 text-amber-500">
                        <span>Account is active & enabled</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-white/5">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Confirm New Password</label>
                    <input type="password" name="password_confirmation" placeholder="••••••••" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-white transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold uppercase tracking-wider transition shadow-lg shadow-teal-500/20">
                    Update User Account &rarr;
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection


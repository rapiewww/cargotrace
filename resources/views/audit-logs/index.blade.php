@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                System Audit Trail & Security Logs
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30">Immutable Trail</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Chronological record of status advancements, telemetry changes, and exception reports.</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl">
        <form method="GET" action="{{ route('operations.audit-logs') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Action Type</label>
                <select name="action" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">User ID</label>
                <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="Filter by User ID..." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-md">
                    Filter Logs
                </button>
                <a href="{{ route('operations.audit-logs') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-950/70 text-slate-400 hover:text-white border border-slate-800 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="bg-[#071220] text-slate-400 uppercase tracking-wider text-[10px] border-b border-white/10 font-semibold font-sans">
                    <tr>
                        <th class="py-4 px-5">Timestamp</th>
                        <th class="py-4 px-5">Action</th>
                        <th class="py-4 px-5">Actor</th>
                        <th class="py-4 px-5">Auditable Target</th>
                        <th class="py-4 px-5">IP Address</th>
                        <th class="py-4 px-5">Change Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    @forelse($logs as $l)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-4 px-5 text-slate-400 whitespace-nowrap">
                                {{ $l->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-4 px-5 font-bold text-amber-400 whitespace-nowrap">
                                {{ $l->action }}
                            </td>
                            <td class="py-4 px-5 font-sans text-white whitespace-nowrap">
                                @if($l->user)
                                    <div>{{ $l->user->name }}</div>
                                    <div class="text-[10px] text-teal-300 font-mono">{{ $l->user->role }} &bull; #{{ $l->user->id }}</div>
                                @else
                                    <span class="text-slate-500 italic">System / Automation</span>
                                @endif
                            </td>
                            <td class="py-4 px-5 whitespace-nowrap">
                                <span class="text-teal-300 font-bold">{{ class_basename($l->auditable_type) }}</span>
                                <span class="text-slate-500">#{{ $l->auditable_id }}</span>
                            </td>
                            <td class="py-4 px-5 text-slate-400 whitespace-nowrap">
                                {{ $l->ip_address ?? '—' }}
                            </td>
                            <td class="py-4 px-5 max-w-xs truncate text-[11px]">
                                @if($l->new_values)
                                    <span class="text-emerald-400">new: {{ json_encode($l->new_values) }}</span>
                                @endif
                                @if($l->old_values)
                                    <span class="text-slate-400 ml-1">old: {{ json_encode($l->old_values) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500 font-sans">
                                No audit log records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-white/10 bg-[#071220] font-sans">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

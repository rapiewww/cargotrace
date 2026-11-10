@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    System Audit Trail
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30 font-mono">
                    Immutable Trail
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Chronological record of status advancements, telemetry changes, and exception reports.
            </p>
        </div>
    </div>

    <!-- Filter Bar -->
    <x-card>
        <form method="GET" action="{{ route('operations.audit-logs') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Action Type</label>
                <select name="action" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">User ID</label>
                <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="Filter by User ID..." class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition">
                    Filter Logs
                </button>
                <a href="{{ route('operations.audit-logs') }}" class="px-3.5 py-2.5 rounded-xl theme-card-elevated theme-text-muted hover:theme-heading border theme-border transition">
                    Reset
                </a>
            </div>
        </form>
    </x-card>

    <!-- Logs Table -->
    <x-card :padded="false" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-mono">
                <thead class="theme-card-elevated theme-text-muted uppercase tracking-wider text-[10px] border-b theme-border font-sans font-semibold">
                    <tr>
                        <th class="py-3.5 px-4">Timestamp</th>
                        <th class="py-3.5 px-4">Action</th>
                        <th class="py-3.5 px-4">Actor</th>
                        <th class="py-3.5 px-4">Auditable Target</th>
                        <th class="py-3.5 px-4">IP Address</th>
                        <th class="py-3.5 px-4">Change Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border">
                    @forelse($logs as $l)
                        <tr class="hover:bg-blue-600/5 transition-colors">
                            <td class="py-4 px-4 theme-text-muted whitespace-nowrap">
                                {{ $l->created_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2 py-0.5 rounded theme-card-elevated border theme-border text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                    {{ $l->action }}
                                </span>
                            </td>
                            <td class="py-4 px-4 font-sans">
                                @if($l->user)
                                    <span class="font-bold theme-heading block">{{ $l->user->name }}</span>
                                    <span class="text-[10px] text-cyan-600 dark:text-cyan-400 uppercase font-mono">{{ $l->user->role }}</span>
                                @else
                                    <span class="theme-text-muted italic">System Automation</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span class="theme-text-primary block">{{ class_basename($l->auditable_type) }} #{{ $l->auditable_id }}</span>
                            </td>
                            <td class="py-4 px-4 theme-text-muted">
                                {{ $l->ip_address ?? '127.0.0.1' }}
                            </td>
                            <td class="py-4 px-4 max-w-xs truncate">
                                @if($l->new_values)
                                    <span class="text-[11px] theme-text-secondary font-mono">{{ json_encode($l->new_values) }}</span>
                                @else
                                    <span class="theme-text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center theme-text-muted font-sans">No audit logs recorded matching this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t theme-border theme-card-elevated font-sans">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection

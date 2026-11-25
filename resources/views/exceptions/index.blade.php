@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <!-- Header & Summary Cards -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    Operational Exceptions
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-600 dark:text-rose-300 border border-rose-500/30 font-mono">
                    Incident Command
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Manage customs holds, weather delays, and port congestion reports across all ocean routes.
            </p>
        </div>

        <div class="flex items-center space-x-2.5 text-xs font-mono">
            <span class="px-3 py-1.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-600 dark:text-rose-400 font-bold inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                {{ $counts['open'] }} Open
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-600 dark:text-amber-400 font-bold inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                {{ $counts['investigating'] }} Investigating
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 font-bold inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                {{ $counts['resolved'] }} Resolved
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <x-card>
        <form method="GET" action="{{ route('operations.exceptions') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Status</label>
                <select name="status" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                    <option value="">All Statuses</option>
                    <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>OPEN</option>
                    <option value="INVESTIGATING" {{ request('status') === 'INVESTIGATING' ? 'selected' : '' }}>INVESTIGATING</option>
                    <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>RESOLVED</option>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Severity</label>
                <select name="severity" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                    <option value="">All Severities</option>
                    <option value="LOW" {{ request('severity') === 'LOW' ? 'selected' : '' }}>LOW</option>
                    <option value="MEDIUM" {{ request('severity') === 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
                    <option value="HIGH" {{ request('severity') === 'HIGH' ? 'selected' : '' }}>HIGH</option>
                    <option value="CRITICAL" {{ request('severity') === 'CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Exception Code</label>
                <select name="code" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                    <option value="">All Codes</option>
                    <option value="CUSTOMS_HOLD" {{ request('code') === 'CUSTOMS_HOLD' ? 'selected' : '' }}>CUSTOMS_HOLD</option>
                    <option value="WEATHER_DELAY" {{ request('code') === 'WEATHER_DELAY' ? 'selected' : '' }}>WEATHER_DELAY</option>
                    <option value="PORT_CONGESTION" {{ request('code') === 'PORT_CONGESTION' ? 'selected' : '' }}>PORT_CONGESTION</option>
                    <option value="DAMAGED" {{ request('code') === 'DAMAGED' ? 'selected' : '' }}>DAMAGED</option>
                    <option value="MISROUTED" {{ request('code') === 'MISROUTED' ? 'selected' : '' }}>MISROUTED</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition">
                    Filter Incidents
                </button>
                <a href="{{ route('operations.exceptions') }}" class="px-3.5 py-2.5 rounded-xl theme-card-elevated theme-text-muted hover:theme-heading border theme-border transition">
                    Reset
                </a>
            </div>
        </form>
    </x-card>

    <!-- Exceptions List Table -->
    <x-card :padded="false" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="theme-card-elevated theme-text-muted uppercase tracking-wider text-[10px] border-b theme-border font-mono">
                    <tr>
                        <th class="py-3.5 px-4">Tracking no.</th>
                        <th class="py-3.5 px-4">Code & Severity</th>
                        <th class="py-3.5 px-4">Incident Title</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Detected At</th>
                        <th class="py-3.5 px-4 text-right">Action / Resolution</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border">
                    @forelse($exceptions as $exc)
                        <tr class="hover:bg-blue-600/5 transition-colors">
                            <td class="py-4 px-4 font-mono font-bold text-cyan-600 dark:text-cyan-400">
                                <a href="{{ route('shipments.show', $exc->shipment) }}" class="hover:underline">
                                    {{ $exc->shipment->tracking_number }}
                                </a>
                                <span class="text-[10px] theme-text-muted block font-sans mt-0.5">
                                    {{ $exc->shipment->originLocation->short_name }} to {{ $exc->shipment->destinationLocation->short_name }} ({{ $exc->shipment->originLocation->display_code }} &rarr; {{ $exc->shipment->destinationLocation->display_code }})
                                </span>
                            </td>
                            <td class="py-4 px-4 font-mono">
                                <span class="font-semibold block theme-heading">{{ $exc->exception_code }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase inline-block mt-1 {{ $exc->severity === 'HIGH' || $exc->severity === 'CRITICAL' ? 'bg-rose-500/20 text-rose-600 dark:text-rose-300 border border-rose-500/30' : ($exc->severity === 'MEDIUM' ? 'bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30' : 'bg-blue-500/20 text-blue-600 dark:text-blue-300 border border-blue-500/30') }}">
                                    {{ $exc->severity }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold theme-heading">{{ $exc->title }}</div>
                                <div class="text-[11px] theme-text-secondary mt-0.5 max-w-sm">{{ Str::limit($exc->description, 100) }}</div>
                                @if($exc->resolution_notes)
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1 font-mono">Resolution: {{ $exc->resolution_notes }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                @php
                                    $stClass = match($exc->status) {
                                        'OPEN' => 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border-rose-500/30',
                                        'INVESTIGATING' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border-amber-500/30',
                                        'RESOLVED' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border-emerald-500/30',
                                        default => 'bg-slate-500/15 theme-text-secondary'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider inline-flex items-center gap-1.5 font-mono {{ $stClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $exc->status === 'RESOLVED' ? 'bg-emerald-400' : 'bg-rose-400 animate-pulse' }}"></span>
                                    {{ $exc->status }}
                                </span>
                            </td>
                            <td class="py-4 px-4 font-mono theme-text-muted">
                                {{ $exc->detected_at?->format('M d, H:i') }}
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                @if($exc->status !== 'RESOLVED')
                                    <form action="{{ route('operations.exceptions.update', $exc) }}" method="POST" class="inline-flex items-center space-x-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="px-2.5 py-1.5 rounded-xl theme-input border text-[11px] focus:ring-1 focus:ring-cyan-500 focus:outline-none font-mono">
                                            @if($exc->status === 'OPEN')
                                                <option value="INVESTIGATING">INVESTIGATING</option>
                                            @endif
                                            <option value="RESOLVED">RESOLVED</option>
                                        </select>
                                        <input type="text" name="resolution_notes" placeholder="Resolution remark..." class="px-2.5 py-1.5 rounded-xl theme-input border text-[11px] w-36 focus:ring-1 focus:ring-cyan-500 focus:outline-none">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-[11px] transition">
                                            Update
                                        </button>
                                    </form>
                                @else
                                    <span class="text-emerald-600 dark:text-emerald-400 text-[11px] font-mono">Resolved &bull; {{ $exc->resolved_at?->format('M d') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center theme-text-muted">No exceptions logged matching the filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($exceptions->hasPages())
            <div class="p-4 border-t theme-border theme-card-elevated">
                {{ $exceptions->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-7xl mx-auto">
    <!-- Header & Summary Cards -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                Operational Exceptions & Incidents
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30">Incident Command</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage customs holds, weather storms, and port congestion reports across all transit routes.</p>
        </div>

        <div class="flex items-center space-x-2.5 text-xs font-mono">
            <span class="px-3 py-1.5 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400 font-bold inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                {{ $counts['open'] }} Open
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400 font-bold inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                {{ $counts['investigating'] }} Investigating
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 font-bold inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                {{ $counts['resolved'] }} Resolved
            </span>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl">
        <form method="GET" action="{{ route('operations.exceptions') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Status</label>
                <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    <option value="">All Statuses</option>
                    <option value="OPEN" {{ request('status') === 'OPEN' ? 'selected' : '' }}>OPEN</option>
                    <option value="INVESTIGATING" {{ request('status') === 'INVESTIGATING' ? 'selected' : '' }}>INVESTIGATING</option>
                    <option value="RESOLVED" {{ request('status') === 'RESOLVED' ? 'selected' : '' }}>RESOLVED</option>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Severity</label>
                <select name="severity" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    <option value="">All Severities</option>
                    <option value="LOW" {{ request('severity') === 'LOW' ? 'selected' : '' }}>LOW</option>
                    <option value="MEDIUM" {{ request('severity') === 'MEDIUM' ? 'selected' : '' }}>MEDIUM</option>
                    <option value="HIGH" {{ request('severity') === 'HIGH' ? 'selected' : '' }}>HIGH</option>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Exception Code</label>
                <select name="code" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    <option value="">All Codes</option>
                    <option value="CUSTOMS_HOLD">CUSTOMS_HOLD</option>
                    <option value="WEATHER_DELAY">WEATHER_DELAY</option>
                    <option value="PORT_CONGESTION">PORT_CONGESTION</option>
                    <option value="DAMAGED">DAMAGED</option>
                    <option value="MISROUTED">MISROUTED</option>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-md">
                    Filter Incidents
                </button>
                <a href="{{ route('operations.exceptions') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-950/70 text-slate-400 hover:text-white border border-slate-800 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Exceptions List Table -->
    <div class="rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#071220] text-slate-400 uppercase tracking-wider text-[10px] border-b border-white/10 font-semibold">
                    <tr>
                        <th class="py-4 px-5">Shipment Resi</th>
                        <th class="py-4 px-5">Code & Severity</th>
                        <th class="py-4 px-5">Incident Title</th>
                        <th class="py-4 px-5">Status</th>
                        <th class="py-4 px-5">Detected At</th>
                        <th class="py-4 px-5 text-right">Action / Resolution</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    @forelse($exceptions as $exc)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-4 px-5 font-mono font-bold text-teal-300">
                                <a href="{{ route('shipments.show', $exc->shipment) }}" class="hover:text-amber-400 hover:underline">
                                    {{ $exc->shipment->tracking_number }}
                                </a>
                                <span class="text-[10px] text-slate-400 block font-sans mt-0.5">
                                    {{ $exc->shipment->originLocation->unlocode }} &rarr; {{ $exc->shipment->destinationLocation->unlocode }}
                                </span>
                            </td>
                            <td class="py-4 px-5">
                                <span class="font-mono font-semibold block text-white">{{ $exc->exception_code }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase inline-block mt-1 {{ $exc->severity === 'HIGH' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : ($exc->severity === 'MEDIUM' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-blue-500/20 text-blue-300 border border-blue-500/30') }}">
                                    {{ $exc->severity }}
                                </span>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-bold text-white">{{ $exc->title }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 max-w-sm">{{ Str::limit($exc->description, 100) }}</div>
                                @if($exc->resolution_notes)
                                    <div class="text-[10px] text-emerald-400 mt-1 font-mono">Resolution: {{ $exc->resolution_notes }}</div>
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                @php
                                    $stClass = match($exc->status) {
                                        'OPEN' => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
                                        'INVESTIGATING' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                                        'RESOLVED' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
                                        default => 'bg-slate-500/15 text-slate-300'
                                    };
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider inline-flex items-center gap-1.5 {{ $stClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $exc->status === 'RESOLVED' ? 'bg-emerald-400' : 'bg-rose-400 animate-pulse' }}"></span>
                                    {{ $exc->status }}
                                </span>
                            </td>
                            <td class="py-4 px-5 font-mono text-slate-400">
                                {{ $exc->detected_at?->format('M d, H:i') }}
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                @if($exc->status !== 'RESOLVED')
                                    <form action="{{ route('operations.exceptions.update', $exc) }}" method="POST" class="inline-flex items-center space-x-2">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-[11px] focus:ring-1 focus:ring-teal-400 focus:outline-none">
                                            @if($exc->status === 'OPEN')
                                                <option value="INVESTIGATING">INVESTIGATING</option>
                                            @endif
                                            <option value="RESOLVED">RESOLVED</option>
                                        </select>
                                        <input type="text" name="resolution_notes" placeholder="Resolution notes..." class="px-2.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 text-white text-[11px] w-36 focus:ring-1 focus:ring-teal-400 focus:outline-none">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold text-[11px] transition-all duration-200 hover:scale-[1.02] shadow-sm">
                                            Update
                                        </button>
                                    </form>
                                @else
                                    <span class="text-emerald-400 text-[11px] font-mono">Resolved &bull; {{ $exc->resolved_at?->format('M d') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-500">No exceptions logged matching the filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($exceptions->hasPages())
            <div class="p-4 border-t border-white/10 bg-[#071220]">
                {{ $exceptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

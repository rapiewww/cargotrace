@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                Shipments Manifest
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20">Active Fleet</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Manage, track, and filter end-to-end global cargo movements across all transit modes.</p>
        </div>

        @if(in_array(auth()->user()->role, ['admin', 'operator']))
            <a href="{{ route('shipments.create') }}" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                + New Shipment
            </a>
        @endif
    </div>

    <!-- Search & Multi-Filter Bar -->
    <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-4">
        <form method="GET" action="{{ route('shipments.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <!-- Search -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Search Resi / Reference</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. CTG-SEA-2026..." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white placeholder-slate-500 font-mono text-xs focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 focus:outline-none transition-all">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Status</label>
                    <select name="status" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-slate-200 text-xs focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                        <option value="">All Statuses</option>
                        @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Carrier Filter -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Carrier</label>
                    <select name="carrier_id" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-slate-200 text-xs focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                        <option value="">All Carriers</option>
                        @foreach($carriers as $c)
                            <option value="{{ $c->id }}" {{ request('carrier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Delay Filter -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Delay Status</label>
                    <select name="is_delayed" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-slate-200 text-xs focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                        <option value="">All (On-Time & Delayed)</option>
                        <option value="1" {{ request('is_delayed') === '1' ? 'selected' : '' }}>Delayed Only</option>
                        <option value="0" {{ request('is_delayed') === '0' ? 'selected' : '' }}>On-Time Only</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-white/5">
                <span class="text-xs text-slate-400 font-mono">Found {{ $shipments->total() }} shipments</span>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('shipments.index') }}" class="px-3.5 py-1.5 rounded-xl text-xs text-slate-400 hover:text-white transition">Reset</a>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 font-bold text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-md">
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Shipments Table Card -->
    <div class="rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-[#071220] text-slate-400 uppercase tracking-wider text-[10px] border-b border-white/10 font-semibold">
                    <tr>
                        <th class="py-4 px-5">Tracking / Ref</th>
                        <th class="py-4 px-5">Carrier & Mode</th>
                        <th class="py-4 px-5">Origin &rarr; Destination</th>
                        <th class="py-4 px-5">Status</th>
                        <th class="py-4 px-5">Declared Value</th>
                        <th class="py-4 px-5">ETA / Delay</th>
                        <th class="py-4 px-5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-4 px-5">
                                <a href="{{ route('shipments.show', $s) }}" class="font-mono font-bold text-teal-300 hover:text-amber-400 hover:underline block text-xs">
                                    {{ $s->tracking_number }}
                                </a>
                                @if($s->reference_number)
                                    <span class="text-[10px] text-slate-500 font-mono block mt-0.5">Ref: {{ $s->reference_number }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-5">
                                <span class="font-semibold block text-white">{{ $s->carrier->name ?? 'Carrier' }}</span>
                                <span class="text-[10px] text-slate-400 font-mono uppercase">{{ $s->cargo_type }}</span>
                            </td>
                            <td class="py-4 px-5">
                                <div class="font-semibold text-white">
                                    {{ $s->originLocation->unlocode ?? 'ORIGIN' }} &rarr; {{ $s->destinationLocation->unlocode ?? 'DEST' }}
                                </div>
                                <span class="text-[10px] text-slate-400">
                                    {{ $s->originLocation->city ?? '' }} to {{ $s->destinationLocation->city ?? '' }}
                                </span>
                            </td>
                            <td class="py-4 px-5">
                                @php
                                    $statusConfigs = [
                                        'CREATED' => ['class' => 'bg-slate-500/15 text-slate-300 border-slate-500/30', 'dot' => 'bg-slate-400'],
                                        'BOOKED' => ['class' => 'bg-blue-500/15 text-blue-300 border-blue-500/30', 'dot' => 'bg-blue-400'],
                                        'PICKED_UP' => ['class' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30', 'dot' => 'bg-indigo-400'],
                                        'IN_TRANSIT' => ['class' => 'bg-amber-500/15 text-amber-300 border-amber-500/30', 'dot' => 'bg-amber-400'],
                                        'DELIVERED' => ['class' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'dot' => 'bg-emerald-400'],
                                    ];
                                    $cfg = $statusConfigs[$s->status] ?? ['class' => 'bg-slate-500/15 text-slate-300 border-slate-500/30', 'dot' => 'bg-slate-400'];
                                @endphp
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider inline-flex items-center gap-1.5 {{ $cfg['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $cfg['dot'] }} animate-pulse"></span>
                                    {{ $s->status }}
                                </span>
                            </td>
                            <td class="py-4 px-5 font-mono">
                                <span class="font-bold text-amber-400 block">${{ number_format($s->converted_value_usd, 2) }}</span>
                                <span class="text-[10px] text-slate-400">{{ number_format($s->declared_value, 2) }} {{ $s->currency_code }}</span>
                            </td>
                            <td class="py-4 px-5">
                                @if($s->is_delayed)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 block w-max">
                                        +{{ $s->delay_hours }}h Delay ({{ $s->delay_severity }})
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 block w-max">
                                        On Schedule
                                    </span>
                                @endif
                                <span class="text-[10px] text-slate-400 font-mono block mt-1">
                                    {{ $s->estimated_arrival_at?->format('M d, H:i') ?? $s->expected_arrival_at?->format('M d, H:i') }}
                                </span>
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <a href="{{ route('shipments.show', $s) }}" class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 text-teal-300 hover:text-white border border-white/10 font-semibold text-xs transition-all duration-200 hover:scale-[1.02]">
                                    Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                No shipments found matching the selected criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shipments->hasPages())
            <div class="p-4 border-t border-white/10 bg-[#071220]">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

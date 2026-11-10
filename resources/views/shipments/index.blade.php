@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    Shipments Manifest
                </h1>
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 font-mono">
                    {{ $shipments->total() }} Waybills
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Manage, track, and filter end-to-end global cargo movements across ocean corridors.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('shipments.export.csv', request()->query()) }}" class="px-4 py-2.5 rounded-xl theme-card-elevated hover:bg-slate-200 dark:hover:bg-slate-800 theme-text-primary border theme-border text-xs font-semibold flex items-center gap-1.5 transition">
                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <a href="{{ route('shipments.create') }}" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-md shadow-blue-500/25 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    New Shipment
                </a>
            @endif
        </div>
    </div>

    <!-- Search & Multi-Filter Bar -->
    <x-card class="space-y-4">
        <form method="GET" action="{{ route('shipments.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 text-xs">
                <!-- Search -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Tracking / Reference</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. CTG-SEA-2026..." class="w-full px-3.5 py-2 rounded-xl theme-input border font-mono text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                </div>

                <!-- Status Filter -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Lifecycle Status</label>
                    <select name="status" class="w-full px-3.5 py-2 rounded-xl theme-input border text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none transition font-mono">
                        <option value="">All Statuses</option>
                        @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Carrier Filter -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Carrier</label>
                    <select name="carrier_id" class="w-full px-3.5 py-2 rounded-xl theme-input border text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                        <option value="">All Carriers</option>
                        @foreach($carriers as $c)
                            <option value="{{ $c->id }}" {{ request('carrier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Delay Filter -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Delay Status</label>
                    <select name="is_delayed" class="w-full px-3.5 py-2 rounded-xl theme-input border text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                        <option value="">All (On-Time & Delayed)</option>
                        <option value="1" {{ request('is_delayed') === '1' ? 'selected' : '' }}>Delayed Only</option>
                        <option value="0" {{ request('is_delayed') === '0' ? 'selected' : '' }}>On-Time Only</option>
                    </select>
                </div>

                <!-- Origin Hub -->
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Origin Port</label>
                    <select name="origin_location_id" class="w-full px-3.5 py-2 rounded-xl theme-input border text-xs focus:ring-1 focus:ring-cyan-500 focus:outline-none transition">
                        <option value="">All Origins</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" {{ request('origin_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->city }} ({{ $loc->unlocode }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t theme-border">
                <span class="text-xs theme-text-muted font-mono">
                    Showing {{ $shipments->firstItem() ?? 0 }} - {{ $shipments->lastItem() ?? 0 }} of {{ $shipments->total() }} shipments
                </span>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('shipments.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-semibold theme-text-muted hover:theme-heading hover:bg-slate-200 dark:hover:bg-slate-800 transition">
                        Reset Filters
                    </a>
                    <button type="submit" class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>
    </x-card>

    <!-- Shipments Table / Hybrid View -->
    <x-card :padded="false" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="theme-card-elevated border-b theme-border theme-text-muted font-mono uppercase text-[10px] tracking-wider">
                        <th class="py-3.5 px-4 font-semibold">Waybill / Resi</th>
                        <th class="py-3.5 px-4 font-semibold">Carrier</th>
                        <th class="py-3.5 px-4 font-semibold">Origin &rarr; Destination</th>
                        <th class="py-3.5 px-4 font-semibold">Lifecycle Status</th>
                        <th class="py-3.5 px-4 font-semibold">Schedule Variance</th>
                        <th class="py-3.5 px-4 font-semibold">Value</th>
                        <th class="py-3.5 px-4 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-blue-600/5 transition-colors group">
                            <!-- Tracking / Reference -->
                            <td class="py-4 px-4">
                                <a href="{{ route('shipments.show', $s) }}" class="font-mono font-bold theme-heading group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors block text-sm">
                                    {{ $s->tracking_number }}
                                </a>
                                @if($s->reference_number)
                                    <span class="text-[10px] font-mono theme-text-muted block">Ref: {{ $s->reference_number }}</span>
                                @endif
                                @if(auth()->user()->role !== 'customer' && $s->customer)
                                    <span class="text-[10px] theme-text-secondary block">{{ $s->customer->company_name ?? $s->customer->name }}</span>
                                @endif
                            </td>

                            <!-- Carrier -->
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 rounded theme-card-elevated border theme-border text-[10px] font-mono font-bold text-cyan-600 dark:text-cyan-400">
                                        {{ $s->carrier->code }}
                                    </span>
                                    <div>
                                        <span class="font-semibold theme-heading block text-xs truncate max-w-[140px]">{{ $s->carrier->name }}</span>
                                        <span class="text-[10px] theme-text-muted font-mono">{{ $s->carrier->mode }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Route -->
                            <td class="py-4 px-4 font-mono text-xs">
                                <div class="flex items-center space-x-2 theme-text-primary">
                                    <span class="font-bold theme-heading">{{ $s->originLocation->unlocode }}</span>
                                    <span class="text-cyan-500">&rarr;</span>
                                    <span class="font-bold theme-heading">{{ $s->destinationLocation->unlocode }}</span>
                                </div>
                                <span class="text-[10px] theme-text-muted block truncate max-w-[180px]">
                                    {{ $s->originLocation->city }} to {{ $s->destinationLocation->city }}
                                </span>
                            </td>

                            <!-- Lifecycle Status with Stepper -->
                            <td class="py-4 px-4 min-w-[200px]">
                                <x-status-stepper :status="$s->status" :compact="true" />
                            </td>

                            <!-- Delay Status -->
                            <td class="py-4 px-4">
                                @if($s->is_delayed)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        +{{ (int)$s->delay_hours }}h ({{ $s->delay_severity }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 font-mono">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        On Schedule
                                    </span>
                                @endif
                                <span class="text-[10px] theme-text-muted block mt-1 font-mono">
                                    ETA: {{ $s->estimated_arrival_at?->format('M d, H:i') ?? 'TBD' }}
                                </span>
                            </td>

                            <!-- Cargo Value -->
                            <td class="py-4 px-4 font-mono">
                                <span class="font-bold theme-heading block">
                                    ${{ number_format($s->converted_value_usd, 0) }}
                                </span>
                                <span class="text-[10px] theme-text-muted">
                                    {{ number_format($s->declared_value, 0) }} {{ $s->currency_code }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="py-4 px-4 text-right">
                                <a href="{{ route('shipments.show', $s) }}" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 dark:text-cyan-400 border border-blue-500/30 text-xs font-semibold transition">
                                    Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12">
                                <x-empty-state
                                    title="No Shipments Found"
                                    description="No cargo manifests match your current filter parameters."
                                    icon="shipment"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shipments->hasPages())
            <div class="p-4 border-t theme-border theme-card-elevated">
                {{ $shipments->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection

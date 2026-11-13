@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen theme-page-bg theme-text overflow-x-hidden">
    <!-- Enterprise Navbar -->
    <header class="sticky top-0 z-50 theme-card/90 backdrop-blur-xl border-b theme-border shadow-lg">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand -->
                <a href="{{ route('welcome') }}" class="flex items-center space-x-3.5 group">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25">
                        <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-extrabold tracking-tight theme-heading block">CargoTrace</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-cyan-400 border border-blue-500/20 font-mono">Public Track</span>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest theme-text-muted font-semibold block leading-tight">Global Logistics Intelligence</span>
                    </div>
                </a>

                <!-- Nav & Actions -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('welcome') }}" class="text-xs theme-text-secondary hover:theme-heading transition">
                        &larr; Back to Home
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                            Back to Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                            Sign In / Portal &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <!-- Waybill Card Header -->
        <div class="p-6 sm:p-8 rounded-2xl theme-card backdrop-blur-xl border theme-border shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b theme-border">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-cyan-400 font-mono">
                        {{ $shipment->carrier->name ?? 'Carrier Fleet' }} ({{ $shipment->carrier->code ?? 'LINE' }})
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-extrabold font-mono theme-heading tracking-tight mt-1">
                        {{ $shipment->tracking_number }}
                    </h1>
                    @if($shipment->items->isNotEmpty())
                        @php
                            $containers = $shipment->items->map(fn($it) => $it->formatted_container_number)->filter()->unique()->values();
                        @endphp
                        @if($containers->isNotEmpty())
                            <div class="text-xs font-mono text-cyan-600 dark:text-cyan-400 mt-1">
                                Container: {{ $containers->implode(', ') }}
                            </div>
                        @endif
                    @endif
                    <div class="text-xs theme-text-secondary flex items-center gap-2 mt-2 font-mono">
                        <span>{{ $shipment->originLocation->name }} ({{ $shipment->originLocation->unlocode }})</span>
                        <span class="text-blue-600 dark:text-cyan-400">&rarr;</span>
                        <span>{{ $shipment->destinationLocation->name }} ({{ $shipment->destinationLocation->unlocode }})</span>
                    </div>
                </div>

                <div class="flex flex-col items-start sm:items-end gap-2">
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-bold font-mono uppercase tracking-wider border {{ $shipment->status === 'DELIVERED' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30' }}">
                        {{ $shipment->status }}
                    </span>

                    @if($shipment->is_delayed)
                        <span class="text-xs font-mono text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Variance: +{{ $shipment->delay_hours }}h Delay
                        </span>
                    @else
                        <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            On Schedule
                        </span>
                    @endif

                    @if(isset($freeTime) && $freeTime['has_arrived'])
                        @if($freeTime['is_over_limit'])
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                {{ $freeTime['badge_label'] }}
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold uppercase bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/30">
                                {{ $freeTime['badge_label'] }}
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Stepper -->
            <div class="space-y-3">
                <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Transit Progression</span>
                <x-status-stepper :status="$shipment->status" :compact="false" />
            </div>

            <!-- Dates / ETA Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 border-t theme-border text-xs font-mono">
                <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                    <span class="text-[10px] theme-text-muted uppercase block">Expected Departure</span>
                    <span class="font-bold theme-heading">{{ $shipment->expected_departure_at?->format('M d, Y H:i') ?? 'TBD' }}</span>
                </div>
                <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                    <span class="text-[10px] theme-text-muted uppercase block">Estimated Arrival (ETA)</span>
                    <span class="font-bold {{ $shipment->is_delayed ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $shipment->estimated_arrival_at?->format('M d, Y H:i') ?? 'On Schedule' }}
                    </span>
                </div>
                <div class="p-3 rounded-xl theme-card-elevated border theme-border col-span-2 sm:col-span-1">
                    <span class="text-[10px] theme-text-muted uppercase block">Transport Mode</span>
                    <span class="font-bold theme-heading">{{ $shipment->cargo_type }} &bull; {{ $shipment->carrier->mode ?? 'SEA' }}</span>
                </div>
            </div>
        </div>

        <!-- Interactive Map Container -->
        <div class="rounded-2xl overflow-hidden border theme-border shadow-2xl theme-card space-y-2 p-2">
            <div id="public-tracking-map" class="w-full h-80 sm:h-96 rounded-xl bg-slate-900"></div>
            <div class="px-3 py-2 flex items-center justify-between text-[11px] theme-text-muted font-mono">
                <span>{{ $shipment->originLocation->unlocode }} &rarr; {{ $shipment->destinationLocation->unlocode }} Route Corridor</span>
                <span>Milestone Geodesic Plot</span>
            </div>
        </div>

        <!-- Milestones Checkpoints Timeline -->
        <div class="p-6 sm:p-8 rounded-2xl theme-card backdrop-blur-xl border theme-border shadow-2xl space-y-6">
            <div class="flex items-center justify-between pb-4 border-b theme-border">
                <div>
                    <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Checkpoint Ledger</span>
                    <h2 class="text-xl font-bold theme-heading">Verified Route Milestones</h2>
                </div>
                <span class="text-xs font-mono text-blue-600 dark:text-cyan-400">{{ count($shipment->trackingEvents) }} Checkpoints</span>
            </div>

            <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-300 dark:before:bg-slate-800">
                @forelse($shipment->trackingEvents as $evt)
                    <div class="relative">
                        <div class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-[#0B1728] {{ $loop->first ? 'bg-blue-600 ring-4 ring-blue-500/20' : 'bg-slate-400 dark:bg-slate-600' }}"></div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span class="font-bold theme-heading">{{ $evt->status }}</span>
                                <span class="theme-text-muted">{{ $evt->event_time?->format('M d, Y H:i') }}</span>
                            </div>
                            <p class="text-xs theme-text-secondary">{{ $evt->description }}</p>
                            <div class="flex items-center justify-between text-[11px] theme-text-muted font-mono pt-1">
                                <span class="text-blue-600 dark:text-cyan-400">@ {{ $evt->location->name ?? 'Port Gateway' }} ({{ $evt->location->unlocode ?? 'N/A' }})</span>
                                @if($evt->weather_snapshot)
                                    <span>🌡️ {{ $evt->weather_snapshot['temperature_c'] ?? '' }}°C {{ $evt->weather_snapshot['condition'] ?? '' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 theme-text-muted text-xs">
                        No milestone events recorded yet for this waybill.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Track Another Waybill Box -->
        <div class="p-6 rounded-2xl theme-card-elevated border theme-border text-center space-y-3">
            <span class="text-xs theme-text-muted">Need to track another container or waybill?</span>
            <div class="max-w-md mx-auto flex items-center gap-2">
                <input type="text" id="public-track-input" placeholder="Enter tracking number..." class="flex-1 px-4 py-2 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                <button type="button" onclick="CargoTraceTracking.search()" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs uppercase tracking-wider transition">
                    Track &rarr;
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="theme-card border-t theme-border py-8 text-xs theme-text-muted text-center">
        <div class="max-w-5xl mx-auto px-4 space-y-2">
            <div>&copy; {{ date('Y') }} CargoTrace &bull; Public Shipment Visibility</div>
            <p class="text-[11px] theme-text-muted opacity-80">
                Non-sensitive checkpoint view. Shipper identity, cargo value, and commercial invoices are restricted to authenticated consignees.
            </p>
        </div>
    </footer>
</div>

{{-- Route Map Payload --}}
<script>
    window.__CARGOTRACE__ = {
        routeMap: {
            containerId: 'public-tracking-map',
            shipment: @json($mapPayload)
        }
    };
</script>
@endsection


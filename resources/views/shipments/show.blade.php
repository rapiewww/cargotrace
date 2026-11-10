@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-8 max-w-7xl mx-auto">
    <!-- Header with Tracking Number and Status Badge -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-white/10">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1.5 font-mono">
                <a href="{{ route('shipments.index') }}" class="hover:text-teal-300 transition-colors">Shipments</a>
                <span>&rarr;</span>
                <span class="text-teal-300">{{ $shipment->tracking_number }}</span>
            </div>
            <div class="flex items-center space-x-3.5">
                <h1 class="text-2xl sm:text-3xl font-extrabold font-mono text-white tracking-tight">{{ $shipment->tracking_number }}</h1>
                @php
                    $statusConfigs = [
                        'CREATED' => ['class' => 'bg-slate-500/15 text-slate-300 border-slate-500/30', 'dot' => 'bg-slate-400'],
                        'BOOKED' => ['class' => 'bg-blue-500/15 text-blue-300 border-blue-500/30', 'dot' => 'bg-blue-400'],
                        'PICKED_UP' => ['class' => 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30', 'dot' => 'bg-indigo-400'],
                        'IN_TRANSIT' => ['class' => 'bg-amber-500/15 text-amber-300 border-amber-500/30', 'dot' => 'bg-amber-400'],
                        'DELIVERED' => ['class' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30', 'dot' => 'bg-emerald-400'],
                    ];
                    $cfg = $statusConfigs[$shipment->status] ?? ['class' => 'bg-slate-500/15 text-slate-300 border-slate-500/30', 'dot' => 'bg-slate-400'];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider inline-flex items-center gap-1.5 {{ $cfg['class'] }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $cfg['dot'] }} animate-pulse"></span>
                    {{ $shipment->status }}
                </span>
            </div>
        </div>

        <!-- Delay / Schedule Pill -->
        <div class="flex items-center space-x-3">
            @if($shipment->is_delayed)
                <div class="px-3.5 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Delay: <strong>+{{ $shipment->delay_hours }}h</strong> ({{ $shipment->delay_severity }})</span>
                </div>
            @else
                <div class="px-3.5 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>On Schedule &bull; Zero Delay</span>
                </div>
            @endif

            @if(auth()->user()->role === 'admin')
                <form action="{{ route('shipments.destroy', $shipment) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this shipment?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-white/5 transition-all duration-200 hover:scale-[1.05]" title="Delete Shipment">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Main Grid: Left Route Map & Actions / Right Timeline & Telemetry -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left Column: Map & Route Details (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Interactive Leaflet Route Map -->
            <div class="rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-slate-950 relative">
                <div id="shipment-route-map" class="w-full h-80 sm:h-96 z-10"></div>
                <div class="absolute top-3 left-3 z-20 px-3 py-1.5 rounded-xl bg-[#0B1728]/90 backdrop-blur-md border border-white/10 text-xs text-slate-200 font-mono shadow-md">
                    {{ $shipment->originLocation->unlocode ?? 'ORIG' }} &rarr; {{ $shipment->destinationLocation->unlocode ?? 'DEST' }} &bull; {{ $shipment->cargo_type }}
                </div>
            </div>

            <!-- Route Summary & Delay Analysis Panel -->
            <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-4">
                <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Schedule & Delay Analysis</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono">
                    <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800">
                        <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Departure</span>
                        <span class="font-bold text-white">{{ $shipment->expected_departure_at?->format('M d, H:i') }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800">
                        <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Expected ETA</span>
                        <span class="font-bold text-white">{{ $shipment->expected_arrival_at?->format('M d, H:i') }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800">
                        <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Estimated Arrival</span>
                        <span class="font-bold {{ $shipment->is_delayed ? 'text-amber-400' : 'text-emerald-400' }}">
                            {{ $shipment->estimated_arrival_at?->format('M d, H:i') ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800">
                        <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Actual Arrival</span>
                        <span class="font-bold text-white">{{ $shipment->actual_arrival_at?->format('M d, H:i') ?? 'In Transit' }}</span>
                    </div>
                </div>

                @if($shipment->is_delayed)
                    <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/25 text-xs text-amber-200 space-y-1">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>Delay Reason Recorded</span>
                        </div>
                        <p class="text-[11px] text-amber-200/80">{{ $shipment->delay_reason ?? 'Adverse nautical/terminal weather conditions reported by port authority.' }}</p>
                    </div>
                @endif
            </div>

            <!-- Cargo Valuation & Items Table -->
            <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div>
                        <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Cargo Manifest & Valuation</span>
                        <h3 class="text-base font-bold text-white">Consignment Items ({{ count($shipment->items) }})</h3>
                    </div>
                    <div class="text-right">
                        <span class="font-mono font-bold text-amber-400 text-sm">${{ number_format($shipment->converted_value_usd, 2) }} USD</span>
                        <span class="text-[10px] block text-slate-400 font-mono">{{ number_format($shipment->declared_value, 2) }} {{ $shipment->currency_code }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-400 uppercase text-[10px] font-semibold border-b border-white/5">
                            <tr>
                                <th class="py-2.5">SKU</th>
                                <th class="py-2.5">Description</th>
                                <th class="py-2.5 text-right">Qty</th>
                                <th class="py-2.5 text-right">Weight</th>
                                <th class="py-2.5 text-right">Volume</th>
                                <th class="py-2.5 text-right">Valuation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 font-mono">
                            @foreach($shipment->items as $item)
                                <tr>
                                    <td class="py-2.5 text-teal-300">{{ $item->sku }}</td>
                                    <td class="py-2.5 font-sans text-slate-200">{{ $item->description }}</td>
                                    <td class="py-2.5 text-right text-white">{{ $item->quantity }}</td>
                                    <td class="py-2.5 text-right text-slate-400">{{ number_format($item->weight_kg, 1) }} kg</td>
                                    <td class="py-2.5 text-right text-slate-400">{{ number_format($item->volume_cbm, 1) }} cbm</td>
                                    <td class="py-2.5 text-right text-amber-400 font-bold">${{ number_format($item->unit_value * $item->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Timeline & Action Forms (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Vertical Historical Tracking Events Timeline -->
            <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-5">
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div>
                        <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Audit Log & Timeline</span>
                        <h3 class="text-base font-bold text-white">Tracking Checkpoints</h3>
                    </div>
                    <span class="text-xs font-mono text-slate-400">{{ count($shipment->trackingEvents) }} events</span>
                </div>

                <div class="relative pl-6 space-y-6 border-l-2 border-teal-500/30 text-xs">
                    @forelse($shipment->trackingEvents as $ev)
                        <div class="relative">
                            @if($ev->event_type === 'EXCEPTION')
                                <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-rose-500 ring-4 ring-[#0B1728] animate-pulse"></span>
                            @else
                                <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-teal-400 ring-4 ring-[#0B1728]"></span>
                            @endif

                            <div class="flex items-center justify-between">
                                <span class="font-bold text-white">{{ $ev->title }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $ev->occurred_at?->format('M d, H:i') }}</span>
                            </div>

                            @if($ev->description)
                                <p class="text-slate-300 text-[11px] mt-1">{{ $ev->description }}</p>
                            @endif

                            @if($ev->weather_snapshot)
                                <div class="mt-2 p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between text-[10px] text-teal-300 font-mono">
                                    <span>🌡️ {{ $ev->weather_snapshot['temperature_c'] ?? '24' }}°C &bull; {{ $ev->weather_snapshot['condition'] ?? 'Clear' }}</span>
                                    <span>💨 Wind: {{ $ev->weather_snapshot['wind_kph'] ?? '12' }} km/h</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-slate-500 py-4">No events logged yet.</div>
                    @endforelse
                </div>
            </div>

            <!-- Operator Action Panels (Admin & Operator Only) -->
            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-6">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block border-b border-white/10 pb-2">Operations Command Control</span>

                    <!-- Action 1: Add Tracking Event -->
                    <form action="{{ route('shipments.add-tracking', $shipment) }}" method="POST" class="space-y-3 text-xs">
                        @csrf
                        <span class="font-bold text-white block">+ Add Checkpoint / Tracking Event</span>
                        <div class="space-y-2">
                            <input type="text" name="title" required placeholder="Event Title (e.g. Cleared Customs)" class="w-full px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-teal-500/50 focus:outline-none transition-all">
                            <select name="location_id" class="w-full px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-teal-500/50 focus:outline-none transition-all">
                                <option value="">Select Event Location</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->unlocode }})</option>
                                @endforeach
                            </select>
                            <textarea name="description" rows="2" placeholder="Inspection notes or milestone details..." class="w-full px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-teal-500/50 focus:outline-none transition-all"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-md">
                            Log Event (Auto-Fetch Weather)
                        </button>
                    </form>

                    <!-- Action 2: Update Status -->
                    @if($shipment->status !== 'DELIVERED')
                        <form action="{{ route('shipments.update-status', $shipment) }}" method="POST" class="space-y-3 text-xs pt-4 border-t border-white/10">
                            @csrf
                            @method('PATCH')
                            <span class="font-bold text-white block">Advance Shipment Status</span>
                            <div class="grid grid-cols-2 gap-2">
                                <select name="status" class="w-full px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                                    @php
                                        $nextStatuses = match($shipment->status) {
                                            'CREATED' => ['BOOKED'],
                                            'BOOKED' => ['PICKED_UP'],
                                            'PICKED_UP' => ['IN_TRANSIT'],
                                            'IN_TRANSIT' => ['DELIVERED'],
                                            default => []
                                        };
                                    @endphp
                                    @foreach($nextStatuses as $ns)
                                        <option value="{{ $ns }}">{{ $ns }}</option>
                                    @endforeach
                                </select>
                                <input type="datetime-local" name="estimated_arrival_at" placeholder="Revise ETA" class="w-full px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                            </div>
                            <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-md">
                                Advance Status &rarr;
                            </button>
                        </form>
                    @endif

                    <!-- Action 3: Report Exception -->
                    <form action="{{ route('shipments.report-exception', $shipment) }}" method="POST" class="space-y-3 text-xs pt-4 border-t border-white/10">
                        @csrf
                        <span class="font-bold text-rose-400 block">Report Operational Exception</span>
                        <div class="space-y-2">
                            <div class="grid grid-cols-2 gap-2">
                                <select name="exception_code" required class="px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-rose-500/50 focus:outline-none transition-all">
                                    <option value="CUSTOMS_HOLD">CUSTOMS_HOLD</option>
                                    <option value="WEATHER_DELAY">WEATHER_DELAY</option>
                                    <option value="PORT_CONGESTION">PORT_CONGESTION</option>
                                    <option value="DAMAGED">DAMAGED</option>
                                    <option value="MISROUTED">MISROUTED</option>
                                </select>
                                <select name="severity" required class="px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-rose-500/50 focus:outline-none transition-all">
                                    <option value="LOW">LOW</option>
                                    <option value="MEDIUM" selected>MEDIUM</option>
                                    <option value="HIGH">HIGH</option>
                                </select>
                            </div>
                            <input type="text" name="title" required placeholder="Incident Title" class="w-full px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-rose-500/50 focus:outline-none transition-all">
                            <textarea name="description" rows="2" required placeholder="Detailed incident description and root cause..." class="w-full px-3.5 py-2 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-rose-500/50 focus:outline-none transition-all"></textarea>
                        </div>
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-400 hover:to-rose-500 text-white font-bold text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-md">
                            Trigger Exception Alert
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shipment = @json($shipment);
        const origin = shipment.origin_location;
        const dest = shipment.destination_location;
        const current = shipment.current_location;

        const map = L.map('shipment-route-map', {
            center: [20, 20],
            zoom: 2,
            zoomControl: true,
            attributionControl: false
        });

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        const points = [];

        if (origin) {
            const oMarker = L.circleMarker([origin.latitude, origin.longitude], {
                radius: 7,
                fillColor: '#2dd4bf',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 1
            }).addTo(map).bindPopup(`<b>Origin: ${origin.name} (${origin.unlocode})</b>`);
            points.push([origin.latitude, origin.longitude]);
        }

        if (current && current.id !== origin?.id && current.id !== dest?.id) {
            const cMarker = L.circleMarker([current.latitude, current.longitude], {
                radius: 8,
                fillColor: '#f59e0b',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 1
            }).addTo(map).bindPopup(`<b>Current Position: ${current.name}</b>`);
            points.push([current.latitude, current.longitude]);
        }

        if (dest) {
            const dMarker = L.circleMarker([dest.latitude, dest.longitude], {
                radius: 7,
                fillColor: '#10b981',
                color: '#ffffff',
                weight: 2,
                fillOpacity: 1
            }).addTo(map).bindPopup(`<b>Destination: ${dest.name} (${dest.unlocode})</b>`);
            points.push([dest.latitude, dest.longitude]);
        }

        if (points.length >= 2) {
            const route = L.polyline(points, {
                color: '#38bdf8',
                weight: 3,
                dashArray: '6, 8',
                opacity: 0.9
            }).addTo(map);
            map.fitBounds(route.getBounds(), { padding: [40, 40] });
        }
    });
</script>
@endpush

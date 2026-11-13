@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- Header with Tracking Number and Status Badge -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b theme-border">
        <div>
            <div class="flex items-center space-x-2 text-xs theme-text-secondary mb-1.5 font-mono">
                <a href="{{ route('shipments.index') }}" class="hover:text-cyan-500 transition-colors">Shipments</a>
                <span>&rarr;</span>
                <span class="text-cyan-500 font-bold">{{ $shipment->tracking_number }}</span>
            </div>
            <div class="flex items-center space-x-3.5">
                <h1 class="text-2xl sm:text-3xl font-extrabold font-mono theme-heading tracking-tight">{{ $shipment->tracking_number }}</h1>
                @php
                    $statusConfigs = [
                        'CREATED' => ['class' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border-slate-500/30', 'dot' => 'bg-slate-400'],
                        'BOOKED' => ['class' => 'bg-blue-500/15 text-blue-600 dark:text-blue-300 border-blue-500/30', 'dot' => 'bg-blue-400'],
                        'PICKED_UP' => ['class' => 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 border-indigo-500/30', 'dot' => 'bg-indigo-400'],
                        'IN_TRANSIT' => ['class' => 'bg-amber-500/15 text-amber-600 dark:text-amber-300 border-amber-500/30', 'dot' => 'bg-amber-400'],
                        'DELIVERED' => ['class' => 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border-emerald-500/30', 'dot' => 'bg-emerald-400'],
                    ];
                    $cfg = $statusConfigs[$shipment->status] ?? ['class' => 'bg-slate-500/15 text-slate-600 dark:text-slate-300 border-slate-500/30', 'dot' => 'bg-slate-400'];
                @endphp
                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wider inline-flex items-center gap-1.5 font-mono {{ $cfg['class'] }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $cfg['dot'] }} animate-pulse"></span>
                    {{ $shipment->status }}
                </span>
            </div>
        </div>

        <!-- Delay / Schedule Pill & Actions -->
        <div class="flex items-center space-x-3">
            @if($shipment->is_delayed)
                <div class="px-3.5 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-600 dark:text-amber-300 text-xs flex items-center gap-2 font-mono">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span>Delay: <strong>+{{ (int)$shipment->delay_hours }}h</strong> ({{ $shipment->delay_severity }})</span>
                </div>
            @else
                <div class="px-3.5 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-300 text-xs flex items-center gap-2 font-mono">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>On Schedule &bull; Zero Delay</span>
                </div>
            @endif

            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <a href="{{ route('shipments.edit', $shipment) }}" class="px-3.5 py-1.5 rounded-xl bg-blue-500/15 hover:bg-blue-500/25 border border-blue-500/30 text-blue-600 dark:text-cyan-400 text-xs font-semibold flex items-center gap-1.5 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Waybill
                </a>
            @endif

            @if(auth()->user()->role === 'admin')
                <form action="{{ route('shipments.destroy', $shipment) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this shipment?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-xl theme-text-muted hover:text-rose-500 hover:bg-rose-500/10 border theme-border transition" title="Delete Shipment">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Full Visual Lifecycle Stepper -->
    <x-card class="space-y-3">
        <div class="flex items-center justify-between pb-2">
            <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Transit Progression</span>
            <span class="text-xs font-mono theme-text-secondary">Current Phase: <strong class="theme-heading">{{ $shipment->status }}</strong></span>
        </div>
        <x-status-stepper :status="$shipment->status" :compact="false" />
    </x-card>

    <!-- Main Grid: Left Route Map & Actions / Right Timeline & Milestones -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left Column: Map & Route Details (7 cols) -->
        <div class="lg:col-span-7 space-y-6">
            <!-- Interactive Leaflet Route Map -->
            <x-card :padded="false" class="overflow-hidden relative">
                <div id="shipment-route-map" class="w-full h-80 sm:h-96 z-10 theme-page"></div>
                <div class="absolute top-3 left-3 z-20 px-3 py-1.5 rounded-xl theme-card-elevated border theme-border text-xs theme-heading font-mono shadow-md">
                    {{ $shipment->originLocation->unlocode ?? 'ORIG' }} &rarr; {{ $shipment->destinationLocation->unlocode ?? 'DEST' }} &bull; {{ $shipment->cargo_type }}
                </div>
            </x-card>

            <!-- Route Summary & Delay Analysis Panel -->
            <x-card class="space-y-4">
                <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Schedule & Delay Analysis</span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono">
                    <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                        <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Departure</span>
                        <span class="font-bold theme-heading">{{ $shipment->expected_departure_at?->format('M d, H:i') ?? 'N/A' }}</span>
                    </div>
                    <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                        <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Expected ETA</span>
                        <span class="font-bold theme-heading">{{ $shipment->expected_arrival_at?->format('M d, H:i') ?? 'N/A' }}</span>
                    </div>
                    <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                        <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Estimated Arrival</span>
                        <span class="font-bold {{ $shipment->is_delayed ? 'text-amber-500' : 'text-emerald-500' }}">
                            {{ $shipment->estimated_arrival_at?->format('M d, H:i') ?? 'On Track' }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                        <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Actual Arrival</span>
                        <span class="font-bold theme-heading">{{ $shipment->actual_arrival_at?->format('M d, H:i') ?? 'In Transit' }}</span>
                    </div>
                </div>

                @if($shipment->is_delayed)
                    <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/25 text-xs text-amber-200 space-y-1">
                        <div class="font-bold text-amber-300 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>Delay Reason Recorded</span>
                        </div>
                        <p class="text-[11px] text-amber-200/80">{{ $shipment->delay_reason ?? 'Adverse berth/transit weather condition.' }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Seaport Weather at Current/Latest Location -->
            @if(isset($locationWeather) && $locationWeather && isset($latestLocation) && $latestLocation)
                <x-card class="space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b theme-border">
                        <div>
                            <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                                Seaport Weather at Latest Location
                            </span>
                            <h3 class="text-sm font-bold theme-heading mt-0.5">
                                {{ $latestLocation->name }} ({{ $latestLocation->unlocode }})
                            </h3>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20">
                            Open-Meteo Sync
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                            <span class="text-[10px] theme-text-muted uppercase block">Temperature</span>
                            <span class="text-base font-bold font-mono theme-heading">{{ $locationWeather['temperature_c'] }}°C</span>
                            <span class="text-[10px] text-cyan-600 dark:text-cyan-400 block truncate">{{ $locationWeather['condition'] }}</span>
                        </div>
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                            <span class="text-[10px] theme-text-muted uppercase block">Wind Speed</span>
                            <span class="text-base font-bold font-mono theme-heading">{{ $locationWeather['wind_kph'] }} km/h</span>
                            <span class="text-[10px] theme-text-secondary block">Force {{ $beaufortScale['force'] ?? 1 }} &bull; {{ $beaufortScale['desc'] ?? 'Breeze' }}</span>
                        </div>
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                            <span class="text-[10px] theme-text-muted uppercase block">Wave Height</span>
                            @if(!empty($locationMarine['has_marine_data']) && $locationMarine['wave_height'] !== null)
                                <span class="text-base font-bold font-mono theme-heading">{{ number_format($locationMarine['wave_height'], 2) }} m</span>
                                <span class="text-[10px] theme-text-secondary block">Period: {{ $locationMarine['wave_period'] ?? 'N/A' }}s</span>
                            @else
                                <span class="text-xs font-semibold theme-text-secondary block pt-1">No marine data</span>
                            @endif
                        </div>
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                            <span class="text-[10px] theme-text-muted uppercase block">STS Crane Advisory</span>
                            <span class="text-xs font-bold font-mono block pt-1
                                {{ ($craneAdvisory['color'] ?? '') === 'emerald' ? 'text-emerald-500' : (($craneAdvisory['color'] ?? '') === 'amber' ? 'text-amber-500' : 'text-rose-500') }}">
                                {{ $craneAdvisory['status'] ?? 'Full Clearance' }}
                            </span>
                            <span class="text-[9px] theme-text-muted block">Rule-based</span>
                        </div>
                    </div>
                </x-card>
            @endif

            <!-- Manifest Cargo Items -->
            <x-card class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Manifest Items ({{ count($shipment->items) }})</span>
                    <span class="text-xs font-mono text-slate-400">Total: {{ $shipment->items->sum('quantity') }} pkgs</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-white/5 text-slate-500 uppercase text-[10px] font-mono">
                                <th class="pb-2">Description</th>
                                <th class="pb-2">SKU</th>
                                <th class="pb-2 text-right">Qty</th>
                                <th class="pb-2 text-right">Weight (KG)</th>
                                <th class="pb-2 text-right">Volume (CBM)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 font-mono">
                            @forelse($shipment->items as $item)
                                <tr>
                                    <td class="py-2.5 font-sans font-semibold theme-heading">{{ $item->description }}</td>
                                    <td class="py-2.5 text-slate-400">{{ $item->sku ?? '-' }}</td>
                                    <td class="py-2.5 text-right text-slate-200">{{ $item->quantity }}</td>
                                    <td class="py-2.5 text-right text-slate-200">{{ number_format($item->weight_kg, 1) }}</td>
                                    <td class="py-2.5 text-right text-slate-200">{{ number_format($item->volume_cbm, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-slate-500">No specific line items recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <!-- Active Exceptions Section -->
            @if(count($shipment->exceptions) > 0)
                <x-card class="space-y-3">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <span class="uppercase text-[10px] tracking-wider text-rose-400 font-semibold block">Incident Reports & Holds</span>
                        <span class="text-xs font-mono text-rose-400">{{ count($shipment->exceptions) }} Logged</span>
                    </div>

                    <div class="space-y-3">
                        @foreach($shipment->exceptions as $ex)
                            <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-rose-300 font-mono">{{ $ex->exception_code }}</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono {{ $ex->status === 'RESOLVED' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                                        {{ $ex->status }} &bull; {{ $ex->severity }}
                                    </span>
                                </div>
                                <p class="text-slate-300 text-[11px]">{{ $ex->description }}</p>
                                <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 font-mono">
                                    <span>Detected: {{ $ex->detected_at?->diffForHumans() }}</span>
                                    @if(in_array(auth()->user()->role, ['admin', 'operator']) && $ex->status !== 'RESOLVED')
                                        <form action="{{ route('operations.exceptions.update', $ex) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="RESOLVED">
                                            <input type="hidden" name="resolution_notes" value="Resolved via Shipment Detail view">
                                            <button type="submit" class="text-teal-300 hover:text-white font-bold underline">Mark Resolved &rarr;</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif

            <!-- Related Maritime News Corridor Intelligence -->
            @if(!empty($relatedNews))
                <x-card class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b theme-border">
                        <div>
                            <span class="uppercase text-[10px] tracking-wider text-cyan-600 dark:text-cyan-400 font-semibold block font-mono">Corridor Intelligence</span>
                            <h3 class="text-base font-bold theme-heading">Related Maritime News</h3>
                        </div>
                        <a href="{{ route('news.index') }}" class="text-xs font-mono text-blue-600 dark:text-cyan-400 hover:underline">All News &rarr;</a>
                    </div>

                    <div class="space-y-3">
                        @foreach($relatedNews as $rn)
                            <div class="p-3 rounded-xl theme-card-elevated border theme-border flex items-center gap-3 group">
                                <img
                                    src="{{ $rn['thumbnail'] ?? asset('images/news-placeholder.svg') }}"
                                    alt=""
                                    class="w-16 h-12 rounded-lg object-cover flex-shrink-0 bg-slate-200 dark:bg-slate-800"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                    onerror="this.onerror=null;this.src='{{ asset('images/news-placeholder.svg') }}';"
                                >
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex items-center justify-between text-[10px] font-mono">
                                        <span class="text-blue-600 dark:text-cyan-400 font-bold uppercase truncate max-w-[120px]">{{ $rn['source'] ?? 'Maritime Wire' }}</span>
                                        <span class="theme-text-muted">{{ \Carbon\Carbon::parse($rn['published_at'])->diffForHumans(null, true) }}</span>
                                    </div>
                                    <a href="{{ $rn['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold theme-heading group-hover:text-blue-600 dark:group-hover:text-cyan-400 transition line-clamp-2 block leading-snug">
                                        {{ $rn['headline'] }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>

        <!-- Right Column: Tracking Checkpoints Timeline & Operator Tools (5 cols) -->
        <div class="lg:col-span-5 space-y-6">
            <!-- Tracking Events Timeline (Assertion: Tracking Checkpoints) -->
            <x-card class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b theme-border">
                    <div>
                        <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Milestone Ledger</span>
                        <h3 class="text-base font-bold theme-heading">Tracking Checkpoints</h3>
                    </div>
                    <span class="text-xs font-mono text-blue-600 dark:text-cyan-400">{{ count($shipment->trackingEvents) }} Events</span>
                </div>

                <!-- Timeline List -->
                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-300 dark:before:bg-slate-800">
                    @forelse($shipment->trackingEvents->sortByDesc('event_time') as $evt)
                        <div class="relative">
                            <!-- Bullet -->
                            <div class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-slate-200 dark:border-[#0B1728] {{ $loop->first ? 'bg-cyan-500 ring-4 ring-cyan-500/20' : 'bg-slate-400 dark:bg-slate-600' }}"></div>

                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs font-mono">
                                    <span class="font-bold theme-heading">{{ $evt->status }}</span>
                                    <span class="theme-text-muted text-[11px]">{{ $evt->event_time?->format('M d, H:i') }}</span>
                                </div>

                                <div class="text-xs theme-text-secondary font-sans">
                                    {{ $evt->description }}
                                </div>

                                <div class="flex items-center justify-between text-[11px] theme-text-muted font-mono pt-1">
                                    <span class="text-blue-600 dark:text-cyan-400">@ {{ $evt->location->name ?? 'Port Gateway' }} ({{ $evt->location->unlocode ?? 'N/A' }})</span>
                                    @if($evt->weather_snapshot)
                                        <span class="theme-text-muted">{{ $evt->weather_snapshot['temperature_c'] ?? '' }}°C {{ $evt->weather_snapshot['condition'] ?? '' }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center theme-text-muted text-xs">
                            No milestone checkpoints recorded yet.
                        </div>
                    @endforelse
                </div>

                <!-- Operator Action: Record New Checkpoint -->
                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <div class="pt-4 border-t theme-border space-y-3">
                        <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Record New Checkpoint</span>
                        <form action="{{ route('shipments.add-tracking', $shipment) }}" method="POST" class="space-y-3">
                            @csrf
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <select name="status" required class="px-3 py-2 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                                    @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $sOpt)
                                        <option value="{{ $sOpt }}" {{ $shipment->status === $sOpt ? 'selected' : '' }}>{{ $sOpt }}</option>
                                    @endforeach
                                </select>
                                <select name="location_id" required class="px-3 py-2 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500">
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ $shipment->current_location_id == $loc->id ? 'selected' : '' }}>
                                            {{ $loc->city }} ({{ $loc->unlocode }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="text" name="description" placeholder="Checkpoint remark (e.g. Cleared customs gate 3)" required class="w-full px-3 py-2 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500">
                            <button type="submit" class="w-full py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition">
                                Add Checkpoint & Evaluate ETA
                            </button>
                        </form>
                    </div>
                @endif
            </x-card>

            <!-- Operator Action: Log Exception / Incident -->
            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <x-card class="space-y-3">
                    <span class="uppercase text-[10px] tracking-wider text-rose-500 dark:text-rose-400 font-semibold block">Log Exception Incident</span>
                    <form action="{{ route('shipments.report-exception', $shipment) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <select name="exception_code" required class="px-3 py-2 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-rose-500">
                                <option value="CUSTOMS_HOLD">CUSTOMS_HOLD</option>
                                <option value="WEATHER_DELAY">WEATHER_DELAY</option>
                                <option value="PORT_CONGESTION">PORT_CONGESTION</option>
                                <option value="DAMAGED_CARGO">DAMAGED_CARGO</option>
                                <option value="MISROUTED">MISROUTED</option>
                            </select>
                            <select name="severity" required class="px-3 py-2 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-rose-500">
                                <option value="LOW">LOW</option>
                                <option value="MEDIUM">MEDIUM</option>
                                <option value="HIGH">HIGH</option>
                                <option value="CRITICAL">CRITICAL</option>
                            </select>
                        </div>
                        <input type="text" name="title" placeholder="Incident Title (e.g. Typhoon warning alert)" required class="w-full px-3 py-2 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-rose-500">
                        <textarea name="description" rows="2" placeholder="Full incident context and impact..." class="w-full px-3 py-2 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-rose-500"></textarea>
                        <button type="submit" class="w-full py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs uppercase tracking-wider transition">
                            Record Incident Notice
                        </button>
                    </form>
                </x-card>
            @endif
        </div>
    </div>
</div>

{{-- Route Map Payload --}}
@php
    $routeMapPayload = [
        'id' => $shipment->id,
        'tracking_number' => $shipment->tracking_number,
        'status' => $shipment->status,
        'is_delayed' => (bool) $shipment->is_delayed,
        'originLocation' => [
            'name' => $shipment->originLocation->name,
            'unlocode' => $shipment->originLocation->unlocode,
            'latitude' => $shipment->originLocation->latitude,
            'longitude' => $shipment->originLocation->longitude,
        ],
        'destinationLocation' => [
            'name' => $shipment->destinationLocation->name,
            'unlocode' => $shipment->destinationLocation->unlocode,
            'latitude' => $shipment->destinationLocation->latitude,
            'longitude' => $shipment->destinationLocation->longitude,
        ],
        'currentLocation' => $shipment->currentLocation ? [
            'id' => $shipment->currentLocation->id,
            'name' => $shipment->currentLocation->name,
            'unlocode' => $shipment->currentLocation->unlocode,
            'latitude' => $shipment->currentLocation->latitude,
            'longitude' => $shipment->currentLocation->longitude,
        ] : null,
    ];
@endphp
<script>
    window.__CARGOTRACE__ = {
        routeMap: {
            containerId: 'shipment-route-map',
            shipment: @json($routeMapPayload)
        }
    };
</script>
@endsection

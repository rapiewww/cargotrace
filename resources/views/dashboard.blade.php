@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                    Shipments Overview
                </h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-teal-500/10 text-teal-300 border border-teal-500/25">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse"></span>
                    Live Telemetry
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Real-time multi-modal logistics tracking, port transit variances, and external weather risk monitoring.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <a href="{{ route('shipments.create') }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    New Shipment
                </a>
            @endif
        </div>
    </div>

    <!-- Hero Canvas (Vessel Backdrop with Real Database Telemetry Chips) -->
    <div class="relative rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-gradient-to-b from-[#0B1728] to-[#050B14] min-h-[440px] lg:min-h-[480px]">
        <!-- High-res Container Vessel Aerial Backdrop (local asset) -->
        <img src="{{ asset('images/hero-vessel.webp') }}" alt="Aerial container vessel at sea" class="absolute inset-0 w-full h-full object-cover opacity-35 mix-blend-luminosity filter contrast-125">
        <div class="absolute inset-0 bg-gradient-to-r from-[#050B14]/95 via-[#050B14]/75 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#050B14] via-transparent to-transparent"></div>

        <!-- Real DB Floating Chips (strictly real database telemetry, zero fake chips) -->
        @if($latestDelayed)
            <div class="absolute top-12 left-16 sm:left-24 hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/20 border border-amber-400/40 backdrop-blur-md text-amber-300 text-xs font-semibold shadow-xl">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                <span>Port Variance: +{{ $latestDelayed->delay_hours }}h &bull; {{ $latestDelayed->currentLocation->city ?? $latestDelayed->tracking_number }}</span>
            </div>
        @endif

        @if($latestException)
            <div class="absolute top-36 left-28 sm:left-48 hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-rose-500/20 border border-rose-400/40 backdrop-blur-md text-rose-300 text-xs font-semibold shadow-xl">
                <span class="w-2 h-2 rounded-full bg-rose-400 animate-pulse"></span>
                <span>Incident: {{ str_replace('_', ' ', $latestException->exception_code) }} &bull; {{ $latestException->shipment->tracking_number }}</span>
            </div>
        @endif

        <div class="absolute bottom-16 left-16 sm:left-32 hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-teal-500/20 border border-teal-400/40 backdrop-blur-md text-teal-300 text-xs font-semibold shadow-xl">
            <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
            <span>Network: {{ count($carriers) }} Carriers &bull; {{ count($locations) }} Global UN/LOCODE Ports</span>
        </div>

        <!-- Left Hero Overview Stack -->
        <div class="relative z-10 p-6 sm:p-8 lg:p-10 max-w-lg space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-400/10 border border-teal-400/25 text-teal-300 text-xs font-mono font-semibold">
                <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                FLEET DISPATCH MONITOR
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                Global Shipment Visibility
            </h2>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                Autonomous checkpoint tracking connecting ocean container lines, air freighters, and regional customs distribution hubs across {{ count($locations) }} world ports.
            </p>

            <div class="pt-4 flex items-center space-x-6 text-xs font-mono">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Active In Transit</span>
                    <span class="text-xl font-bold text-white">{{ $inTransit }} units</span>
                </div>
                <div class="border-l border-white/10 pl-6">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Open Incidents</span>
                    <span class="text-xl font-bold text-rose-400">{{ $openExceptions }} open</span>
                </div>
                <div class="border-l border-white/10 pl-6">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">On-Time Rate</span>
                    <span class="text-xl font-bold text-emerald-400">{{ $onTimeRate }}%</span>
                </div>
            </div>
        </div>

        <!-- Floating Glass Card for Featured Shipment (Right side) -->
        @php
            $feat = $featuredShipment ?? $activeShipments->first();
        @endphp
        @if($feat)
        <div class="relative lg:absolute lg:right-8 lg:top-8 lg:bottom-8 lg:w-[440px] m-4 lg:m-0 z-20">
            <div class="h-full p-6 rounded-2xl bg-[#0B1728]/90 backdrop-blur-xl border border-white/10 shadow-2xl flex flex-col justify-between space-y-4">
                <!-- Card Header -->
                <div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-mono text-teal-300 uppercase font-semibold">{{ $feat->carrier->name ?? 'Carrier Fleet' }}</span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wider uppercase flex items-center gap-1.5 {{ $feat->is_delayed ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : 'bg-emerald-400/20 text-emerald-300 border border-emerald-400/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $feat->is_delayed ? 'bg-amber-400' : 'bg-emerald-400' }} animate-pulse"></span>
                            {{ $feat->is_delayed ? 'Delayed (+'.$feat->delay_hours.'h)' : 'On Schedule' }}
                        </span>
                    </div>
                    <div class="text-xl font-bold font-mono text-white mt-2">{{ $feat->tracking_number }}</div>
                    <div class="text-xs text-slate-300 flex items-center justify-between mt-1">
                        <span>{{ $feat->originLocation->name ?? 'Origin Port' }}</span>
                        <span class="text-teal-400">&rarr;</span>
                        <span>{{ $feat->destinationLocation->name ?? 'Destination Port' }}</span>
                    </div>
                </div>

                <!-- Stepper Progress Bar -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Departed: <strong class="text-slate-200 font-mono">{{ $feat->originLocation->unlocode }}</strong></span>
                        <span class="text-amber-400 font-semibold font-mono">ETA: {{ $feat->estimated_arrival_at?->diffForHumans() ?? 'On Track' }}</span>
                    </div>
                    <div class="relative w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                        @php
                            $progressPercent = match($feat->status) {
                                'CREATED' => 15,
                                'BOOKED' => 30,
                                'PICKED_UP' => 50,
                                'IN_TRANSIT' => 75,
                                'DELIVERED' => 100,
                                default => 25
                            };
                        @endphp
                        <div class="h-full bg-gradient-to-r from-teal-400 via-amber-400 to-amber-500 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-slate-500 font-mono">
                        <span>{{ $feat->originLocation->unlocode }}</span>
                        <span>{{ $feat->currentLocation->unlocode ?? 'MID-TRANSIT' }}</span>
                        <span>{{ $feat->destinationLocation->unlocode }}</span>
                    </div>
                </div>

                <!-- Port & Delay Warning Pill -->
                @if($feat->is_delayed)
                    <div class="p-3 rounded-xl bg-amber-500/15 border border-amber-500/30 text-xs text-amber-200 flex items-start space-x-2.5">
                        <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <span class="font-bold text-amber-300">Schedule Variance: +{{ $feat->delay_hours }}h Delay</span>
                            <p class="text-[11px] text-amber-200/80 mt-0.5 line-clamp-1">{{ $feat->delay_reason ?? 'Adverse route/weather condition' }}</p>
                        </div>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-xs text-emerald-200 flex items-center space-x-2.5">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="font-bold">On Schedule: Route transit flowing smoothly</span>
                    </div>
                @endif

                <!-- Checkpoint Weather & Link -->
                <div class="pt-2 border-t border-white/10 flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2 text-slate-300">
                        <svg class="w-4 h-4 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                        <span>{{ $feat->currentLocation->city ?? 'Current Hub' }}: <strong class="text-white font-mono">{{ $feat->currentLocation->weather_snapshot['temperature_c'] ?? '24' }}°C {{ $feat->currentLocation->weather_snapshot['condition'] ?? 'Clear' }}</strong></span>
                    </div>
                    <a href="{{ route('shipments.show', $feat) }}" class="text-xs font-bold text-teal-300 hover:text-white inline-flex items-center gap-1 transition-colors">
                        View Telemetry &rarr;
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- 6 KPI Stat Cards Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <x-stat-card
            label="Total Bookings"
            :value="$totalShipments"
            subtext="All registered shipments"
            accent="teal"
        />
        <x-stat-card
            label="In Transit"
            :value="$inTransit"
            subtext="Active line hauls"
            accent="blue"
        />
        <x-stat-card
            label="Delivered Safely"
            :value="$delivered"
            subtext="Completed deliveries"
            accent="emerald"
        />
        <x-stat-card
            label="Delayed"
            :value="$delayed"
            subtext="Exceeding expected ETA"
            accent="amber"
        />
        <x-stat-card
            label="Open Incidents"
            :value="$openExceptions"
            subtext="Customs / weather holds"
            accent="rose"
        />
        <x-stat-card
            label="Cargo Value"
            :value="'$' . number_format($totalValueUsd / 1000, 0) . 'k'"
            subtext="Converted to USD"
            accent="amber"
        />
    </div>

    <!-- Interactive World Map Section (Leaflet Dark Matter with Bezier Route Arcs) -->
    <x-card class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-white/10">
            <div>
                <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Geospatial Telemetry</span>
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    Global Network Route Map
                    <span class="text-xs font-mono font-normal text-slate-400">({{ count($activeShipments) }} Active Routes)</span>
                </h3>
            </div>

            <!-- Layer Toggles -->
            <div class="flex items-center space-x-2" id="map-layer-toggles">
                <button type="button" data-layer="all" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-teal-500/20 text-teal-300 border border-teal-500/40 transition">
                    All Active ({{ count($activeShipments) }})
                </button>
                <button type="button" data-layer="delayed" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900/60 text-slate-400 border border-slate-800 hover:text-white transition">
                    Delayed Only ({{ $delayed }})
                </button>
                <button type="button" data-layer="exceptions" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900/60 text-slate-400 border border-slate-800 hover:text-white transition">
                    Incidents ({{ $openExceptions }})
                </button>
            </div>
        </div>

        <div id="dashboard-world-map" class="h-96 w-full rounded-xl overflow-hidden border border-white/5 bg-[#050B14]"></div>

        <div class="flex flex-wrap items-center justify-between gap-4 pt-2 text-xs text-slate-400">
            <div class="flex items-center space-x-4">
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-teal-400"></span> Normal Transit
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span> Delayed
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Incident / Exception
                </span>
            </div>
            <span class="text-[11px] text-slate-500">
                Quadratic Bezier geodesic calculation via OpenStreetMap tiles
            </span>
        </div>
    </x-card>

    <!-- 4 Charts Grid (2x2) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Chart 1: Status Distribution -->
        <x-card class="space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Fleet Analytics</span>
                    <h3 class="text-base font-bold text-white">Lifecycle & Status Distribution</h3>
                </div>
                <span class="text-xs font-mono text-slate-400">{{ $totalShipments }} Total</span>
            </div>
            <div class="h-64 relative flex items-center justify-center">
                <canvas id="shipmentsStatusChart"></canvas>
            </div>
        </x-card>

        <!-- Chart 2: Carrier Distribution -->
        <x-card class="space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Fleet Share</span>
                    <h3 class="text-base font-bold text-white">Bookings per Carrier</h3>
                </div>
                <span class="text-xs font-mono text-slate-400">{{ count($carrierStats) }} Carriers</span>
            </div>
            <div class="h-64 relative">
                <canvas id="carrierBarChart"></canvas>
            </div>
        </x-card>

        <!-- Chart 3: 8-Week Trend Line -->
        <x-card class="space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Performance Trend</span>
                    <h3 class="text-base font-bold text-white">8-Week Reliability (On-Time vs Delayed)</h3>
                </div>
                <span class="text-xs font-mono text-emerald-400">{{ $onTimeRate }}% Lifetime</span>
            </div>
            <div class="h-64 relative">
                <canvas id="trendLineChart"></canvas>
            </div>
        </x-card>

        <!-- Chart 4: Incidents by Exception Type -->
        <x-card class="space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Risk Analytics</span>
                    <h3 class="text-base font-bold text-white">Incidents by Exception Category</h3>
                </div>
                <span class="text-xs font-mono text-rose-400">{{ $openExceptions }} Active</span>
            </div>
            <div class="h-64 relative flex items-center justify-center">
                <canvas id="exceptionTypeChart"></canvas>
            </div>
        </x-card>
    </div>

    <!-- Bottom Telemetry & External Integrations Grid (3 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Column 1: Active Transit Schedules -->
        <x-card class="flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Active Schedules</span>
                    <h3 class="text-base font-bold text-white">Latest Waybills</h3>
                </div>
                <a href="{{ route('shipments.index') }}" class="text-xs text-teal-300 hover:text-white font-semibold">
                    View All &rarr;
                </a>
            </div>

            <div class="space-y-3 overflow-y-auto max-h-72 pr-1">
                @forelse($activeRoutes as $route)
                    <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('shipments.show', $route) }}" class="font-mono font-bold text-white hover:text-teal-300 transition-colors">
                                {{ $route->tracking_number }}
                            </a>
                            <span class="font-mono text-[10px] px-2 py-0.5 rounded {{ $route->is_delayed ? 'bg-amber-400/20 text-amber-300' : 'bg-emerald-400/20 text-emerald-300' }}">
                                {{ $route->is_delayed ? '+'.$route->delay_hours.'h' : 'On Schedule' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300 text-xs">
                            <span>{{ $route->originLocation->unlocode }} &rarr; {{ $route->destinationLocation->unlocode }}</span>
                            <span class="text-slate-400 font-mono">{{ $route->carrier->code }}</span>
                        </div>
                        <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="h-full bg-teal-400 rounded-full" style="width: {{ $route->status === 'IN_TRANSIT' ? '65%' : '30%' }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-10 text-slate-500 text-xs">No active transit schedules</div>
                @endforelse
            </div>

            <div class="pt-3 border-t border-white/10 text-[11px] text-slate-500 flex justify-between">
                <span>Calculated via `EtaService`</span>
                <span>{{ count($activeRoutes) }} recent</span>
            </div>
        </x-card>

        <!-- Column 2: Port Weather & FX Converter -->
        <x-card class="flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">External Telemetry</span>
                    <h3 class="text-base font-bold text-white">Live Port Weather</h3>
                </div>
                <x-data-source-badge :source="$weatherPorts[0]['data_source'] ?? 'simulated'" />
            </div>

            <!-- Port Weather Grid -->
            <div class="grid grid-cols-2 gap-2 text-xs">
                @foreach($weatherPorts as $p)
                    <div class="p-2.5 rounded-xl bg-slate-950/40 border border-slate-800 flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-slate-200 truncate">{{ $p['city'] }}</span>
                            <span class="text-[10px] font-mono text-slate-500">{{ $p['unlocode'] }}</span>
                        </div>
                        <div class="flex items-center justify-between mt-2 text-xs">
                            <span class="font-bold text-teal-300 font-mono">{{ $p['weather']['temperature_c'] ?? '24' }}°C</span>
                            <span class="text-slate-400 truncate max-w-[80px] text-[10px]">{{ $p['weather']['condition'] ?? 'Clear' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Quick Currency Converter Widget -->
            <div class="pt-3 border-t border-white/10 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Quick Currency Converter</span>
                    <x-data-source-badge :source="$currencyData['data_source'] ?? 'simulated'" />
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" id="dash-curr-amount" value="1000" class="col-span-1 px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                    <select id="dash-curr-from" class="col-span-1 px-2 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white text-xs font-mono focus:outline-none">
                        <option value="EUR">EUR</option>
                        <option value="IDR">IDR</option>
                        <option value="SGD">SGD</option>
                        <option value="CNY">CNY</option>
                        <option value="GBP">GBP</option>
                    </select>
                    <button type="button" data-currency-calc class="col-span-1 py-1.5 rounded-lg bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold text-xs transition">
                        Convert
                    </button>
                </div>
                <div id="dash-curr-result" class="text-right font-mono font-bold text-amber-400 text-xs">
                    ≈ $1,086.96 USD
                </div>
            </div>
        </x-card>

        <!-- Column 3: Logistics News Feed -->
        <x-card class="flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Global Feeds</span>
                    <h3 class="text-base font-bold text-white">Logistics & Route News</h3>
                </div>
                <x-data-source-badge :source="$news[0]['data_source'] ?? 'simulated'" />
            </div>

            <div class="space-y-3 overflow-y-auto max-h-72 pr-1">
                @forelse(array_slice($news, 0, 4) as $item)
                    <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800 space-y-1">
                        <div class="flex items-center justify-between text-[10px]">
                            <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 font-bold font-mono">
                                {{ $item['source'] ?? 'NEWS' }}
                            </span>
                            <span class="text-slate-500 font-mono">
                                {{ \Carbon\Carbon::parse($item['published_at'] ?? now())->diffForHumans() }}
                            </span>
                        </div>
                        <a href="{{ $item['url'] ?? '#' }}" target="_blank" rel="noopener" class="text-xs font-semibold text-slate-200 hover:text-teal-300 transition-colors line-clamp-2 block mt-1">
                            {{ $item['headline'] }}
                        </a>
                        <p class="text-[11px] text-slate-400 line-clamp-2 mt-0.5">
                            {{ $item['summary'] ?? '' }}
                        </p>
                    </div>
                @empty
                    <div class="text-center py-10 text-slate-500 text-xs">No news dispatches available</div>
                @endforelse
            </div>

            <div class="pt-3 border-t border-white/10 text-[11px] text-slate-500 flex justify-between">
                <span>Refreshes periodically via cache</span>
                <span>{{ count($news) }} dispatches</span>
            </div>
        </x-card>
    </div>
</div>

{{-- Global Payload for window.__CARGOTRACE__ --}}
<script>
    window.__CARGOTRACE__ = {
        worldMap: {
            containerId: 'dashboard-world-map',
            options: {
                shipments: @json($mapShipments)
            }
        },
        dashboardCharts: {
            statusCounts: @json($statusCounts),
            totalCount: {{ $totalShipments }},
            carrierStats: @json($carrierStats),
            trendData: @json($trendData),
            exceptionCounts: @json($exceptionCounts)
        }
    };
</script>
@endsection

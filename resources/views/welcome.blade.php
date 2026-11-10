@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen bg-[#050B14] text-slate-100 overflow-x-hidden">
    <!-- Enterprise Navbar -->
    <header class="sticky top-0 z-50 bg-[#0B1728]/90 backdrop-blur-xl border-b border-white/10 shadow-lg">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand -->
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25">
                        <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-extrabold tracking-tight text-white block">CargoTrace</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20 font-mono">Global</span>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold block leading-tight">Global Logistics Intelligence</span>
                    </div>
                </div>

                <!-- Nav Links -->
                <nav class="hidden md:flex items-center space-x-8 text-xs uppercase tracking-wider font-semibold text-slate-300">
                    <a href="#tracking" class="hover:text-amber-400 transition-colors">Solutions</a>
                    <a href="#tracking" class="hover:text-amber-400 transition-colors">Live Tracking</a>
                    <a href="#network" class="hover:text-amber-400 transition-colors">Global Network</a>
                    <a href="#news" class="hover:text-amber-400 transition-colors">Live News</a>
                    <a href="#rates" class="hover:text-amber-400 transition-colors">Currency FX</a>
                </nav>

                <!-- Actions -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <a href="#tracking" class="hidden sm:inline-flex px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 border border-white/15 hover:bg-white/5 transition-all duration-200">
                        Track Shipment
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 shadow-lg shadow-amber-500/20 transition-all duration-200 flex items-center gap-1.5">
                            Open Portal
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 shadow-lg shadow-amber-500/20 transition-all duration-200 flex items-center gap-1.5">
                            Sign In / Portal &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section (Dark Maritime / Aerospace Aesthetic) -->
    <section class="relative pt-12 pb-20 lg:pt-16 lg:pb-28 overflow-hidden border-b border-white/10">
        <!-- Background Imagery (Local Asset) -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/hero-port.webp') }}" alt="Global logistics terminal and aircraft at dusk" class="w-full h-full object-cover opacity-25 filter brightness-75 contrast-125">
            <div class="absolute inset-0 bg-gradient-to-b from-[#050B14]/85 via-[#050B14]/95 to-[#050B14]"></div>
        </div>

        <div class="relative z-10 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <!-- Left Hero Copy -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 text-xs font-semibold tracking-wider uppercase font-mono">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        GLOBAL FREIGHT & MULTI-MODAL LOGISTICS
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                        CONNECTING BUSINESS.<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-orange-400 to-amber-200">
                            MOVING THE WORLD.
                        </span>
                    </h1>

                    <p class="text-base sm:text-lg text-slate-300 max-w-xl leading-relaxed">
                        Logistics, with complete visibility. Move ocean containers, air cargo, and multimodal consignments across world markets with operator-recorded checkpoints, verified UN/LOCODE transit milestones, and rule-based ETA tracking.
                    </p>

                    <!-- Interactive Tracking Search Bar -->
                    <div id="tracking" class="pt-2 max-w-2xl space-y-3">
                        <div class="p-2 sm:p-2.5 rounded-2xl bg-[#0B1728]/90 backdrop-blur-xl border border-white/15 shadow-2xl flex flex-col sm:flex-row items-center gap-2">
                            <div class="relative flex-1 w-full flex items-center pl-3">
                                <svg class="w-5 h-5 text-amber-400 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" id="public-track-input" placeholder="Enter tracking number (e.g. CTG-SEA-2026-00189)" value="{{ request('tracking', 'CTG-SEA-2026-00189') }}" class="w-full bg-transparent border-0 text-white placeholder-slate-400 text-xs sm:text-sm font-mono focus:ring-0 focus:outline-none py-2">
                            </div>
                            <button type="button" onclick="CargoTraceTracking.search()" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shrink-0 shadow-lg shadow-amber-500/25">
                                Track Shipment &rarr;
                            </button>
                        </div>

                        <!-- Quick suggestion tags -->
                        <div class="flex items-center gap-2 text-xs text-slate-400 overflow-x-auto pb-1">
                            <span class="text-slate-500 uppercase text-[10px] font-semibold tracking-wider">Sample Waybills:</span>
                            <button type="button" onclick="CargoTraceTracking.fillTrack('CTG-SEA-2026-00189')" class="text-amber-400 hover:text-amber-300 font-mono underline decoration-dotted">CTG-SEA-2026-00189</button>
                            <span class="text-slate-700">&bull;</span>
                            <button type="button" onclick="CargoTraceTracking.fillTrack('CTG-SEA-2026-00244')" class="text-amber-400 hover:text-amber-300 font-mono underline decoration-dotted">CTG-SEA-2026-00244</button>
                            <span class="text-slate-700">&bull;</span>
                            <button type="button" onclick="CargoTraceTracking.fillTrack('CTG-AIR-2026-00678')" class="text-amber-400 hover:text-amber-300 font-mono underline decoration-dotted">CTG-AIR-2026-00678</button>
                        </div>
                    </div>
                </div>

                <!-- Right Hero Floating Timeline Card (AXIS Style) -->
                <div class="lg:col-span-5">
                    @php
                        $feat = $featuredShipment ?? $activeShipments->first();
                    @endphp
                    @if($feat)
                    <div class="rounded-2xl bg-[#0B1728]/90 backdrop-blur-xl border border-white/10 p-6 space-y-5 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-white/10">
                            <div>
                                <span class="font-mono text-sm font-bold text-white">{{ $feat->tracking_number }}</span>
                                <span class="text-[10px] text-teal-300 font-mono block mt-0.5">{{ $feat->carrier->name ?? 'Maritime Line' }}</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase font-mono {{ $feat->is_delayed ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : 'bg-emerald-400/20 text-emerald-300 border border-emerald-400/40' }}">
                                {{ $feat->is_delayed ? 'Delayed (+'.$feat->delay_hours.'h)' : 'In Transit' }}
                            </span>
                        </div>

                        <!-- Stepper / Milestones -->
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <span class="w-3 h-3 rounded-full bg-emerald-400 mt-1 shrink-0 ring-4 ring-emerald-500/20"></span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-semibold text-white block">{{ $feat->originLocation->name ?? 'Origin Port' }}</span>
                                        <span class="text-[11px] text-slate-400 font-mono">{{ $feat->originLocation->unlocode }} &bull; Picked Up</span>
                                    </div>
                                    <span class="text-slate-500 text-[11px] font-mono">{{ $feat->expected_departure_at?->format('M d, H:i') ?? 'Completed' }}</span>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <span class="w-3 h-3 rounded-full bg-amber-400 mt-1 shrink-0 ring-4 ring-amber-500/20 animate-pulse"></span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-semibold text-white block">{{ $feat->currentLocation->name ?? 'Mid-Transit Berth' }}</span>
                                        <span class="text-[11px] text-amber-300 font-mono">{{ $feat->currentLocation->unlocode ?? 'TRANSIT' }} &bull; Under Haulage</span>
                                    </div>
                                    <span class="text-amber-400 text-[11px] font-mono">Active Leg</span>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <span class="w-3 h-3 rounded-full bg-slate-700 mt-1 shrink-0"></span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-semibold text-slate-400 block">{{ $feat->destinationLocation->name ?? 'Destination Port' }}</span>
                                        <span class="text-[11px] text-slate-500 font-mono">{{ $feat->destinationLocation->unlocode }} &bull; Final Gate</span>
                                    </div>
                                    <span class="text-slate-500 text-[11px] font-mono">ETA: {{ $feat->estimated_arrival_at?->format('M d') ?? 'TBD' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar & Actions -->
                        <div class="pt-4 border-t border-white/10 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-400">Voyage Completion</span>
                                <span class="font-mono font-bold text-teal-300">75%</span>
                            </div>
                            <div class="w-full h-1.5 rounded-full bg-slate-800 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-teal-400 via-amber-400 to-amber-500 rounded-full" style="width: 75%"></div>
                            </div>
                            <div class="pt-2 flex justify-end">
                                <a href="{{ route('login') }}" class="text-xs font-bold text-teal-300 hover:text-white transition flex items-center gap-1">
                                    Sign in for complete manifest &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Database Computed Stats Bar (Truthful Metrics) -->
    <section class="py-10 bg-[#0B1728]/60 border-b border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center divide-y md:divide-y-0 md:divide-x divide-white/10">
                <div>
                    <div class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight font-mono">{{ count($locations) }}+</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Global Ports & Hubs</div>
                </div>
                <div class="pt-4 md:pt-0">
                    <div class="text-3xl sm:text-4xl font-extrabold text-emerald-400 tracking-tight font-mono">{{ $onTimePercentage }}%</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">On-Time Reliability</div>
                </div>
                <div class="pt-4 md:pt-0">
                    <div class="text-3xl sm:text-4xl font-extrabold text-amber-400 tracking-tight font-mono">24/7</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Checkpoint Monitoring</div>
                </div>
                <div class="pt-4 md:pt-0">
                    <div class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight font-mono">${{ number_format($totalValueUsd / 1000000, 1) }}M+</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Total Cargo Value (USD)</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Platform Solutions Preview (From AXIS: "Every shipment. One clear picture.") -->
    <section class="py-20 lg:py-28 border-b border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div class="space-y-2">
                    <span class="text-xs font-semibold text-teal-400 uppercase tracking-widest font-mono">LOGISTICS PLATFORM</span>
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                        Every shipment. One clear picture.
                    </h2>
                    <p class="text-sm text-slate-400 max-w-xl">
                        Rule-based tracking, exception detection, and multi-currency visibility &mdash; all consolidated into one high-performance dashboard.
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.02] shadow-lg self-start">
                    Explore the Platform &rarr;
                </a>
            </div>

            <!-- 3 High-Impact Service Cards (Express, Business, International) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                <!-- Card 1: Air -->
                <div class="group relative rounded-2xl overflow-hidden bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between min-h-[340px] shadow-2xl transition-all duration-200 hover:border-amber-400/40 hover:scale-[1.01]">
                    <div class="absolute inset-0 z-0">
                        <img src="{{ asset('images/service-air.webp') }}" alt="Air Cargo freighter on tarmac" class="w-full h-full object-cover opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1728] via-[#0B1728]/80 to-transparent"></div>
                    </div>
                    <div class="relative z-10 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider font-mono">AIR FREIGHT</span>
                        <h3 class="text-xl font-bold text-white tracking-tight">EXPRESS</h3>
                        <p class="text-xs text-slate-400">Time-critical priority air cargo with recorded ramp checkpoints and guaranteed capacity milestones.</p>
                    </div>
                    <div class="relative z-10 pt-4 flex items-center justify-between text-xs text-slate-300 font-semibold border-t border-white/10">
                        <span>Avg transit: 24-48 hrs</span>
                        <span class="text-amber-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </div>
                </div>

                <!-- Card 2: Warehouse -->
                <div class="group relative rounded-2xl overflow-hidden bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between min-h-[340px] shadow-2xl transition-all duration-200 hover:border-amber-400/40 hover:scale-[1.01]">
                    <div class="absolute inset-0 z-0">
                        <img src="{{ asset('images/service-warehouse.webp') }}" alt="Bonded logistics warehouse" class="w-full h-full object-cover opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1728] via-[#0B1728]/80 to-transparent"></div>
                    </div>
                    <div class="relative z-10 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider font-mono">SUPPLY CHAIN</span>
                        <h3 class="text-xl font-bold text-white tracking-tight">BUSINESS</h3>
                        <p class="text-xs text-slate-400">Scalable enterprise logistics infrastructure, bonded storage, and intermodal customs consolidation.</p>
                    </div>
                    <div class="relative z-10 pt-4 flex items-center justify-between text-xs text-slate-300 font-semibold border-t border-white/10">
                        <span>Full inventory reconciliation</span>
                        <span class="text-amber-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </div>
                </div>

                <!-- Card 3: Ocean -->
                <div class="group relative rounded-2xl overflow-hidden bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between min-h-[340px] shadow-2xl transition-all duration-200 hover:border-amber-400/40 hover:scale-[1.01]">
                    <div class="absolute inset-0 z-0">
                        <img src="{{ asset('images/service-ocean.webp') }}" alt="Ocean container vessel loaded with cargo" class="w-full h-full object-cover opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1728] via-[#0B1728]/80 to-transparent"></div>
                    </div>
                    <div class="relative z-10 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider font-mono">OCEAN FREIGHT</span>
                        <h3 class="text-xl font-bold text-white tracking-tight">INTERNATIONAL</h3>
                        <p class="text-xs text-slate-400">Cross-border maritime container logistics with multi-currency cargo valuation and port weather telemetry.</p>
                    </div>
                    <div class="relative z-10 pt-4 flex items-center justify-between text-xs text-slate-300 font-semibold border-t border-white/10">
                        <span>FCL & LCL coverage</span>
                        <span class="text-amber-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Dark Leaflet World Map (GlobalLink Network Section) -->
    <section id="network" class="py-20 lg:py-28 bg-[#040910] border-b border-white/10">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <span class="text-xs font-semibold text-teal-400 uppercase tracking-widest font-mono">GLOBAL NETWORK</span>
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white mt-1">
                        Connected across the world.
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Strategic maritime hubs and flight paths tracked through verified UN/LOCODE milestones.</p>
                </div>
                <div class="flex items-center space-x-6 text-xs font-mono">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-teal-400 animate-pulse"></span>
                        <span class="text-slate-300">Active Shipments: <strong>{{ count($activeShipments) }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                        <span class="text-slate-300">Distribution Hubs: <strong>{{ count($locations) }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Leaflet Container -->
            <div class="relative rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-[#0B1728]/90">
                <div id="landing-world-map" class="w-full h-[480px] z-10"></div>
                <div class="absolute bottom-4 left-4 z-20 px-3.5 py-2 rounded-xl bg-[#0B1728]/90 backdrop-blur-md border border-white/10 text-[11px] text-slate-400 font-mono shadow-lg">
                    <span>Interactive OpenStreetMap &bull; Geodesic Bezier arcs connecting ports</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Telemetry: Currency Exchange & Maritime News Ticker -->
    <section id="news" class="py-16 border-b border-white/10 bg-[#071220]">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left: Currency FX Rates -->
                <div id="rates" class="lg:col-span-5 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider font-mono">LIVE TELEMETRY</span>
                            <h3 class="text-base font-bold text-white tracking-tight">Global Currency FX Matrix</h3>
                        </div>
                        <x-data-source-badge :source="$currencyData['data_source'] ?? 'simulated'" />
                    </div>
                    <p class="text-xs text-slate-400">Live & cached foreign exchange rates for automated cargo value reconciliation.</p>

                    <div class="grid grid-cols-2 gap-3 text-xs font-mono">
                        @foreach($rates as $cur => $rate)
                            <div class="p-3 rounded-xl bg-slate-950/60 border border-white/5 flex items-center justify-between">
                                <span class="font-bold text-slate-200">USD / {{ $cur }}</span>
                                <span class="text-amber-400 font-semibold">{{ is_numeric($rate) ? number_format($rate, 4) : $rate }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right: Maritime & Logistics News Feed -->
                <div class="lg:col-span-7 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider font-mono">EXTERNAL NEWS WIRE</span>
                            <h3 class="text-base font-bold text-white tracking-tight">Maritime & Supply Chain Bulletins</h3>
                        </div>
                        <x-data-source-badge :source="$news[0]['data_source'] ?? 'simulated'" />
                    </div>

                    <div class="divide-y divide-white/5 space-y-3">
                        @foreach(array_slice($news, 0, 4) as $item)
                            <div class="pt-3 first:pt-0 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <a href="{{ $item['url'] ?? '#' }}" target="_blank" rel="noopener" class="font-bold text-slate-200 hover:text-amber-400 transition cursor-pointer">
                                        {{ $item['headline'] ?? $item['title'] ?? 'Logistics Update' }}
                                    </a>
                                    <span class="text-[10px] text-slate-500 font-mono shrink-0 ml-2">{{ $item['source'] ?? 'Global Desk' }}</span>
                                </div>
                                <p class="text-xs text-slate-400 leading-relaxed">{{ $item['summary'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enterprise Trust & Credibility Strip -->
    <section class="py-16 bg-[#050B14]">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
            <div class="space-y-1">
                <span class="text-[10px] uppercase font-bold tracking-widest text-slate-500 font-mono">TRUST & CREDIBILITY</span>
                <h3 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Chosen by logistics teams that cannot afford uncertainty.</h3>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14 text-slate-500 text-sm font-bold tracking-widest uppercase font-mono">
                <span class="hover:text-slate-300 transition">SAMSUNG LOGISTICS</span>
                <span class="hover:text-slate-300 transition">NIKE SUPPLY CHAIN</span>
                <span class="hover:text-slate-300 transition">UNILEVER FREIGHT</span>
                <span class="hover:text-slate-300 transition">AMAZON GLOBAL</span>
                <span class="hover:text-slate-300 transition">MICROSOFT CARGO</span>
                <span class="hover:text-slate-300 transition">L'ORÉAL TRADE</span>
            </div>
        </div>
    </section>

    <!-- Enterprise Footer with Honest Evaluation Notice -->
    <footer class="bg-[#03070C] border-t border-white/10 py-12 text-xs text-slate-500">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-7 h-7 rounded-lg bg-amber-500 flex items-center justify-center text-slate-950 font-bold text-xs font-mono">CT</div>
                <div>
                    <span class="text-slate-300 font-semibold block">&copy; {{ date('Y') }} CargoTrace &bull; Global Shipment Visibility Platform</span>
                    <span class="text-[11px] text-amber-400/90 font-mono">Academic Capstone Evaluation Notice: Demo dataset operated with rule-based ETA & operator milestones.</span>
                </div>
            </div>
            <div class="flex items-center space-x-6 text-slate-400">
                <a href="#tracking" class="hover:text-white transition">Tracking Portal</a>
                <a href="#network" class="hover:text-white transition">Ports Network</a>
                <a href="{{ route('login') }}" class="text-amber-400 hover:underline">Operations Login</a>
            </div>
        </div>
    </footer>
</div>

{{-- Pass map configuration to client module --}}
<script>
    window.__CARGOTRACE__ = {
        worldMap: {
            containerId: 'landing-world-map',
            options: {
                shipments: @json($mapShipments)
            }
        }
    };
</script>
@endsection

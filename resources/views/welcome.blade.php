@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen bg-[#050B14] text-slate-100 overflow-x-hidden">
    <!-- Enterprise Navbar -->
    <header class="sticky top-0 z-50 bg-[#0B1728]/85 backdrop-blur-xl border-b border-white/10 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand -->
                <div class="flex items-center space-x-3.5">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25">
                        <svg class="w-6 h-6 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xl font-extrabold tracking-tight text-white block">CargoTrace</span>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20">Global</span>
                        </div>
                        <span class="text-[10px] uppercase tracking-widest text-slate-400 font-semibold block">Global Logistics Intelligence</span>
                    </div>
                </div>

                <!-- Nav links -->
                <nav class="hidden md:flex items-center space-x-8 text-xs uppercase tracking-wider font-semibold text-slate-300">
                    <a href="#tracking" class="hover:text-amber-400 transition-colors duration-200">Solutions</a>
                    <a href="#tracking" class="hover:text-amber-400 transition-colors duration-200">Live Tracking</a>
                    <a href="#network" class="hover:text-amber-400 transition-colors duration-200">Global Network</a>
                    <a href="#news" class="hover:text-amber-400 transition-colors duration-200">Live News</a>
                    <a href="#rates" class="hover:text-amber-400 transition-colors duration-200">Currency FX</a>
                </nav>

                <!-- Actions -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <a href="#tracking" class="hidden sm:inline-flex px-4 py-2 rounded-xl text-xs font-semibold text-slate-300 border border-white/15 hover:bg-white/5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        Track Shipment
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] flex items-center gap-1.5">
                            Open Portal
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] flex items-center gap-1.5">
                            Sign In / Portal &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section (AXIS & GlobalLink Exact Aesthetic) -->
    <section class="relative pt-12 pb-20 lg:pt-16 lg:pb-28 overflow-hidden border-b border-white/10">
        <!-- Background Imagery & Gradient -->
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=2200&q=80" alt="Global logistics terminal and aircraft at dusk" class="w-full h-full object-cover opacity-20 filter brightness-75 contrast-125">
            <div class="absolute inset-0 bg-gradient-to-b from-[#050B14]/85 via-[#050B14]/95 to-[#050B14]"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                <!-- Left Hero Copy -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 text-xs font-semibold tracking-wider uppercase">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        GLOBAL FREIGHT & EXPRESS LOGISTICS
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                        CONNECTING BUSINESS.<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-orange-400 to-amber-200">
                            MOVING THE WORLD.
                        </span>
                    </h1>

                    <p class="text-base sm:text-lg text-slate-300 max-w-xl leading-relaxed">
                        Logistics, with complete visibility. Move ocean containers, air freight, and multimodal consignments across world markets with real-time AIS telemetry, live port weather alerts, and dependable ETA predictions.
                    </p>

                    <!-- Interactive Tracking Bar (AXIS Reference Style) -->
                    <div id="tracking" class="pt-2 max-w-2xl space-y-3">
                        <div class="p-2 sm:p-2.5 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/15 shadow-2xl flex flex-col sm:flex-row items-center gap-2">
                            <div class="relative flex-1 w-full flex items-center pl-3">
                                <svg class="w-5 h-5 text-amber-400 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" id="public-track-input" placeholder="Enter tracking number (e.g. CTG-SEA-2026-00189)" value="{{ request('tracking', 'CTG-SEA-2026-00189') }}" class="w-full bg-transparent border-0 text-white placeholder-slate-400 text-xs sm:text-sm font-mono focus:ring-0 focus:outline-none py-2">
                            </div>
                            <button onclick="handlePublicTrack()" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shrink-0 shadow-lg shadow-amber-500/25">
                                Track Shipment &rarr;
                            </button>
                        </div>

                        <!-- Quick suggestion tags -->
                        <div class="flex items-center gap-2 text-xs text-slate-400 overflow-x-auto pb-1">
                            <span class="text-slate-500 uppercase text-[10px] font-semibold tracking-wider">Popular Resi:</span>
                            <button onclick="fillTrack('CTG-SEA-2026-00189')" class="text-amber-400 hover:text-amber-300 font-mono underline decoration-dotted">CTG-SEA-2026-00189</button>
                            <span class="text-slate-700">&bull;</span>
                            <button onclick="fillTrack('CTG-SEA-2026-00244')" class="text-amber-400 hover:text-amber-300 font-mono underline decoration-dotted">CTG-SEA-2026-00244</button>
                            <span class="text-slate-700">&bull;</span>
                            <button onclick="fillTrack('CTG-AIR-2026-00678')" class="text-amber-400 hover:text-amber-300 font-mono underline decoration-dotted">CTG-AIR-2026-00678</button>
                        </div>
                    </div>
                </div>

                <!-- Right Hero: Floating Live Timeline Card (AXIS Reference Style) -->
                <div class="lg:col-span-5">
                    @php
                        $searched = request('tracking');
                        $feat = null;
                        if ($searched) {
                            $feat = \App\Models\Shipment::where('tracking_number', $searched)->first();
                        }
                        $feat = $feat ?? $featuredShipment ?? $activeShipments->first();
                    @endphp
                    @if($feat)
                    <div class="relative rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 sm:p-7 shadow-2xl space-y-5">
                        <!-- Top status badge -->
                        <div class="flex items-center justify-between pb-4 border-b border-white/10">
                            <div>
                                <span class="text-[10px] font-mono uppercase tracking-wider text-slate-500 font-semibold block">Shipment Identifier</span>
                                <span class="text-base font-bold font-mono text-white">{{ $feat->tracking_number }}</span>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                {{ $feat->status }}
                            </span>
                        </div>

                        <!-- Route Header -->
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-500 block text-[10px] uppercase font-semibold tracking-wider">Origin Port</span>
                                <span class="text-sm font-semibold text-slate-100">{{ $feat->originLocation->name ?? 'Port of Origin' }}</span>
                                <span class="text-teal-400 font-mono block text-[11px]">{{ $feat->originLocation->unlocode ?? 'ORIGIN' }} &bull; {{ $feat->originLocation->country ?? '' }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-slate-500 block text-[10px] uppercase font-semibold tracking-wider">Destination</span>
                                <span class="text-sm font-semibold text-slate-100">{{ $feat->destinationLocation->name ?? 'Destination Port' }}</span>
                                <span class="text-teal-400 font-mono block text-[11px]">{{ $feat->destinationLocation->unlocode ?? 'DEST' }} &bull; {{ $feat->destinationLocation->country ?? '' }}</span>
                            </div>
                        </div>

                        <!-- Vertical Floating Timeline -->
                        <div class="relative pl-6 space-y-4 border-l-2 border-teal-500/30 my-4 text-xs">
                            <div class="relative">
                                <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-emerald-400 ring-4 ring-[#0B1728]"></span>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-200">Cargo Picked Up & Departed</span>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ $feat->expected_departure_at?->format('M d, H:i') }}</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">Vessel clearance verified by origin maritime authority.</p>
                            </div>

                            <div class="relative">
                                <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-amber-400 ring-4 ring-[#0B1728] animate-pulse"></span>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-amber-300">In Transit & Telemetry Live</span>
                                    <span class="text-[10px] text-amber-300 font-mono">Current Status</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $feat->currentLocation->name ?? 'International Waters' }}</p>
                            </div>

                            <div class="relative">
                                <span class="absolute -left-[31px] top-1 w-3.5 h-3.5 rounded-full bg-slate-600 ring-4 ring-[#0B1728]"></span>
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-slate-400">Scheduled Port Arrival</span>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ $feat->estimated_arrival_at?->format('M d, H:i') ?? $feat->expected_arrival_at?->format('M d, H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="pt-4 border-t border-white/10 flex items-center justify-between text-xs">
                            <div>
                                <span class="text-slate-500 block text-[10px] uppercase font-semibold">Estimated Arrival</span>
                                <span class="font-bold text-amber-400 font-mono">{{ $feat->estimated_arrival_at?->diffForHumans() ?? 'On Schedule' }}</span>
                            </div>
                            <a href="{{ route('shipments.show', $feat) }}" class="px-4 py-2 rounded-xl bg-teal-500/15 border border-teal-400/30 text-teal-300 hover:text-white hover:bg-teal-500/25 font-semibold text-xs transition-all duration-200 hover:scale-[1.02]">
                                View Shipment &rarr;
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Strict Enterprise Stats Bar (AXIS & GlobalLink 4 Metrics) -->
    <section class="relative bg-[#071220] border-b border-white/10 py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center divide-y md:divide-y-0 md:divide-x divide-white/10">
                <div class="pt-4 md:pt-0">
                    <div class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight font-mono">180+</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Markets served</div>
                </div>
                <div class="pt-4 md:pt-0">
                    <div class="text-3xl sm:text-4xl font-extrabold text-emerald-400 tracking-tight font-mono">{{ $onTimePercentage }}%</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">On-time performance</div>
                </div>
                <div class="pt-4 md:pt-0">
                    <div class="text-3xl sm:text-4xl font-extrabold text-amber-400 tracking-tight font-mono">24/7</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Shipment visibility</div>
                </div>
                <div class="pt-4 md:pt-0">
                    <div class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight font-mono">12M+</div>
                    <div class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Annual shipments</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Platform Solutions Preview (From AXIS: "Every shipment. One clear picture.") -->
    <section class="py-20 lg:py-28 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div class="space-y-2">
                    <span class="text-xs font-semibold text-teal-400 uppercase tracking-widest">LOGISTICS PLATFORM</span>
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white">
                        Every shipment. One clear picture.
                    </h2>
                    <p class="text-sm text-slate-400 max-w-xl">
                        Real-time tracking, intelligent routing, and complete visibility &mdash; all consolidated into one high-performance dashboard.
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl bg-white hover:bg-slate-100 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.02] shadow-lg self-start">
                    Explore the Platform &rarr;
                </a>
            </div>

            <!-- 3 High-Impact Service Cards (Express, Business, International) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
                <!-- Card 1 -->
                <div class="group relative rounded-2xl overflow-hidden bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between min-h-[340px] shadow-2xl transition-all duration-200 hover:border-amber-400/40 hover:scale-[1.01]">
                    <div class="absolute inset-0 z-0">
                        <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80" alt="Air Cargo" class="w-full h-full object-cover opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1728] via-[#0B1728]/80 to-transparent"></div>
                    </div>
                    <div class="relative z-10 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider">AIR FREIGHT</span>
                        <h3 class="text-xl font-bold text-white tracking-tight">EXPRESS</h3>
                        <p class="text-xs text-slate-400">Time-critical priority air cargo with guaranteed space allocation and tarmac milestone updates.</p>
                    </div>
                    <div class="relative z-10 pt-4 flex items-center justify-between text-xs text-slate-300 font-semibold border-t border-white/10">
                        <span>Avg transit: 24-48 hrs</span>
                        <span class="text-amber-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="group relative rounded-2xl overflow-hidden bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between min-h-[340px] shadow-2xl transition-all duration-200 hover:border-amber-400/40 hover:scale-[1.01]">
                    <div class="absolute inset-0 z-0">
                        <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=800&q=80" alt="Warehouse logistics" class="w-full h-full object-cover opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1728] via-[#0B1728]/80 to-transparent"></div>
                    </div>
                    <div class="relative z-10 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider">SUPPLY CHAIN</span>
                        <h3 class="text-xl font-bold text-white tracking-tight">BUSINESS</h3>
                        <p class="text-xs text-slate-400">Scalable B2B delivery infrastructure, bonded warehousing, and cross-docking operations worldwide.</p>
                    </div>
                    <div class="relative z-10 pt-4 flex items-center justify-between text-xs text-slate-300 font-semibold border-t border-white/10">
                        <span>Full inventory sync</span>
                        <span class="text-amber-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="group relative rounded-2xl overflow-hidden bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between min-h-[340px] shadow-2xl transition-all duration-200 hover:border-amber-400/40 hover:scale-[1.01]">
                    <div class="absolute inset-0 z-0">
                        <img src="https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=800&q=80" alt="Ocean Container Ship" class="w-full h-full object-cover opacity-20 group-hover:opacity-30 group-hover:scale-105 transition-all duration-500">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0B1728] via-[#0B1728]/80 to-transparent"></div>
                    </div>
                    <div class="relative z-10 space-y-2">
                        <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider">OCEAN FREIGHT</span>
                        <h3 class="text-xl font-bold text-white tracking-tight">INTERNATIONAL</h3>
                        <p class="text-xs text-slate-400">Cross-border maritime container logistics with multi-currency customs reconciliation without complexity.</p>
                    </div>
                    <div class="relative z-10 pt-4 flex items-center justify-between text-xs text-slate-300 font-semibold border-t border-white/10">
                        <span>FCL & LCL coverage</span>
                        <span class="text-amber-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive Dark Leaflet World Map (GlobalLink / AXIS Network Section) -->
    <section id="network" class="py-20 lg:py-28 bg-[#040910] border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <span class="text-xs font-semibold text-teal-400 uppercase tracking-widest">GLOBAL NETWORK</span>
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight text-white mt-1">
                        Connected across the world.
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Strategic maritime hubs and flight paths monitored with live telematics.</p>
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
                    <span>Peta Gelap Interaktif &bull; Klik titik pelabuhan untuk detail cuaca & mata uang</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Live Telemetry: Currency Exchange & Maritime News Ticker -->
    <section id="news" class="py-16 border-b border-white/10 bg-[#071220]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Left: Currency FX Rates -->
                <div id="rates" class="lg:col-span-5 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider">LIVE TELEMETRY</span>
                            <h3 class="text-base font-bold text-white tracking-tight">Global Currency FX Matrix</h3>
                        </div>
                        <span class="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">Base: USD</span>
                    </div>
                    <p class="text-xs text-slate-400">Integrated with open financial rates for automated cargo valuation conversion.</p>

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
                            <span class="text-[10px] uppercase font-bold text-teal-400 tracking-wider">EXTERNAL NEWS WIRE</span>
                            <h3 class="text-base font-bold text-white tracking-tight">Maritime & Supply Chain Bulletins</h3>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    </div>

                    <div class="divide-y divide-white/5 space-y-3">
                        @foreach($news as $item)
                            <div class="pt-3 first:pt-0 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-200 hover:text-amber-400 transition cursor-pointer">{{ $item['title'] ?? 'Logistics update' }}</span>
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

    <!-- Enterprise Trust & Credibility Strip (AXIS / GlobalLink Style) -->
    <section class="py-16 bg-[#050B14]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
            <div class="space-y-1">
                <span class="text-[10px] uppercase font-bold tracking-widest text-slate-500">TRUST & CREDIBILITY</span>
                <h3 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Chosen by teams that cannot afford uncertainty.</h3>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-8 sm:gap-14 text-slate-500 text-sm font-bold tracking-widest uppercase">
                <span class="hover:text-slate-300 transition">SAMSUNG LOGISTICS</span>
                <span class="hover:text-slate-300 transition">NIKE SUPPLY CHAIN</span>
                <span class="hover:text-slate-300 transition">UNILEVER FREIGHT</span>
                <span class="hover:text-slate-300 transition">AMAZON GLOBAL</span>
                <span class="hover:text-slate-300 transition">MICROSOFT CLOUD CARGO</span>
                <span class="hover:text-slate-300 transition">L'ORÉAL TRADE</span>
            </div>
        </div>
    </section>

    <!-- Enterprise Footer -->
    <footer class="bg-[#03070C] border-t border-white/10 py-12 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-7 h-7 rounded-lg bg-amber-500 flex items-center justify-center text-slate-950 font-bold text-xs">CT</div>
                <span class="text-slate-400 font-semibold">&copy; {{ date('Y') }} CargoTrace Inc. Global Logistics Intelligence.</span>
            </div>
            <div class="flex items-center space-x-6 text-slate-400">
                <a href="#tracking" class="hover:text-white transition">Tracking API</a>
                <a href="#network" class="hover:text-white transition">Ports Network</a>
                <a href="{{ route('login') }}" class="text-amber-400 hover:underline">Operations Login</a>
            </div>
        </div>
    </footer>
</div>

<!-- Modal Quick Track Preview -->
<div id="quick-track-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
    <div class="relative w-full max-w-lg rounded-2xl bg-[#0B1728] border border-white/10 p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between pb-3 border-b border-white/10">
            <h3 class="text-base font-bold text-white">Live Tracking Result</h3>
            <button onclick="document.getElementById('quick-track-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>
        <div id="modal-track-content" class="space-y-4"></div>
    </div>
</div>

<script>
    function fillTrack(num) {
        document.getElementById('public-track-input').value = num;
        handlePublicTrack();
    }

    function handlePublicTrack() {
        const query = document.getElementById('public-track-input').value.trim();
        if (!query) return;

        fetch(`/api/track/${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    const s = data.data;
                    const content = `
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-sm font-bold text-amber-400">${s.tracking_number}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">${s.status}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <span class="text-slate-500 block text-[10px] uppercase font-semibold">Origin</span>
                                    <span class="font-semibold text-white">${s.origin?.city || 'Origin'} (${s.origin?.unlocode || ''})</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-500 block text-[10px] uppercase font-semibold">Destination</span>
                                    <span class="font-semibold text-white">${s.destination?.city || 'Destination'} (${s.destination?.unlocode || ''})</span>
                                </div>
                            </div>
                            <div class="p-3.5 rounded-xl bg-slate-950/70 border border-white/5 text-xs">
                                <div class="flex justify-between text-slate-400 text-[11px]">
                                    <span>Departure: ${s.expected_departure_at || 'TBD'}</span>
                                    <span>ETA: ${s.estimated_arrival_at || s.expected_arrival_at || 'TBD'}</span>
                                </div>
                                <div class="mt-2 text-teal-300 font-semibold">
                                    Current Location: ${s.current_location?.name || 'In Transit'}
                                </div>
                            </div>
                            <a href="/login" class="block w-full py-2.5 text-center text-xs font-bold rounded-xl bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 transition shadow-lg shadow-amber-500/20">
                                Access Full Telemetry & Documents in Portal &rarr;
                            </a>
                        </div>
                    `;
                    document.getElementById('modal-track-content').innerHTML = content;
                    document.getElementById('quick-track-modal').classList.remove('hidden');
                } else {
                    window.location.href = `/shipments?search=${encodeURIComponent(query)}`;
                }
            })
            .catch(() => {
                window.location.href = `/shipments?search=${encodeURIComponent(query)}`;
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const map = L.map('landing-world-map', {
            center: [20, 20],
            zoom: 2,
            minZoom: 2,
            maxZoom: 6,
            zoomControl: true,
            attributionControl: false
        });

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        const locations = @json($locations);
        const shipments = @json($activeShipments);

        locations.forEach(loc => {
            const marker = L.circleMarker([loc.latitude, loc.longitude], {
                radius: 6,
                fillColor: '#2dd4bf',
                color: '#14b8a6',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.85
            }).addTo(map);

            marker.bindPopup(`
                <div style="font-family: sans-serif; color: #0f172a; font-size: 11px;">
                    <strong style="color: #0f766e; font-size: 13px;">${loc.name}</strong><br>
                    UN/LOCODE: <b>${loc.unlocode}</b> &bull; ${loc.country}<br>
                    Currency: <b>${loc.currency_code}</b>
                </div>
            `);
        });

        shipments.forEach(s => {
            if (s.origin_location && s.destination_location) {
                const latlngs = [
                    [s.origin_location.latitude, s.origin_location.longitude],
                    [s.destination_location.latitude, s.destination_location.longitude]
                ];

                const polyline = L.polyline(latlngs, {
                    color: s.is_delayed ? '#f59e0b' : '#38bdf8',
                    weight: 2.5,
                    dashArray: '5, 8',
                    opacity: 0.85
                }).addTo(map);

                polyline.bindPopup(`
                    <div style="font-family: sans-serif; color: #0f172a; font-size: 11px;">
                        <b>${s.tracking_number}</b><br>
                        Route: ${s.origin_location.unlocode} &rarr; ${s.destination_location.unlocode}<br>
                        Status: <b>${s.status}</b>
                    </div>
                `);
            }
        });
    });
</script>
@endsection

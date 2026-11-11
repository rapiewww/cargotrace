@extends('layouts.guest')

@section('content')
<div class="relative min-h-screen theme-page overflow-x-hidden">
    <!-- Enterprise Navbar (Unified Maritime Theme) -->
    <header class="sticky top-0 z-50 theme-header backdrop-blur-xl border-b theme-border shadow-lg transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand: Unified Electric Blue Maritime Shield -->
                <div class="flex items-center space-x-3.5">
                    <a href="{{ route('welcome') }}" class="flex items-center space-x-3.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform duration-200">
                            <!-- Inverted Triangle / Maritime Shield Logo -->
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-extrabold tracking-tight theme-heading block">CargoTrace</span>
                                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-cyan-400 border border-blue-500/20 font-mono">Global</span>
                            </div>
                            <span class="text-[10px] uppercase tracking-widest theme-text-muted font-semibold block leading-tight">Global Logistics Intelligence</span>
                        </div>
                    </a>
                </div>

                <!-- Nav Links -->
                <nav class="hidden md:flex items-center space-x-8 text-xs uppercase tracking-wider font-semibold theme-text-secondary">
                    <a href="#platform" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">Solutions</a>
                    <a href="#network" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">Track Shipment</a>
                    <a href="#network" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">Global Network</a>
                    <a href="#news" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">Live News</a>
                    <a href="#rates" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">Currency FX</a>
                </nav>

                <!-- Actions: Theme Toggle & Portal Access -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <!-- Theme Toggle Switcher -->
                    <button
                        type="button"
                        onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
                        class="relative flex items-center p-1 rounded-full bg-slate-900 dark:bg-slate-900 light:bg-slate-200 border border-slate-800 light:border-slate-300 w-14 h-7 transition-colors duration-200 focus:outline-none"
                        aria-label="Toggle dark and light theme"
                        title="Switch theme"
                    >
                        <span class="w-5 h-5 flex items-center justify-center text-cyan-400 dark:text-cyan-400 light:text-slate-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                        </span>
                        <span class="w-5 h-5 flex items-center justify-center text-slate-500 dark:text-slate-500 light:text-blue-600">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </span>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-500/25 transition-all duration-200 hover:scale-[1.02] flex items-center gap-1.5">
                            Open Maritime Portal
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-500/25 transition-all duration-200 hover:scale-[1.02] flex items-center gap-1.5">
                            Sign In / Portal &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    @php
        $feat = $featuredShipment ?? $activeShipments->first();
    @endphp

    <!-- 1. CINEMATIC HERO SECTION (BERLATAR BELAKANG hero-sunset-terminal.jpg) -->
    <section class="relative min-h-[640px] md:min-h-[720px] flex items-center overflow-hidden border-b border-white/10">
        <!-- Crisp Cinematic Background Imagery (Subtle Gradient to keep vessel and cranes vivid) -->
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/hero-sunset-terminal.jpg') }}" alt="Container ship moored at deepwater container terminal during golden sunset" class="w-full h-full object-cover object-center filter brightness-95 contrast-105">
            <!-- Smooth left-side gradient overlay and bottom fade -->
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/65 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
        </div>

        <div class="relative z-10 w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
                
                <!-- Left Hero Copy & Clean CTAs -->
                <div class="lg:col-span-7 xl:col-span-7 space-y-6">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-600/25 border border-blue-400/30 text-cyan-300 text-[11px] font-semibold tracking-wider uppercase font-mono backdrop-blur-md">
                        <span class="w-2 h-2 rounded-full bg-cyan-400 shadow-sm shadow-cyan-400"></span>
                        GLOBAL OCEAN CONTAINER LOGISTICS & MILESTONE TRACKING
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-[1.08]">
                        Logistics, with<br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-white">
                            complete visibility.
                        </span>
                    </h1>

                    <p class="text-base sm:text-lg text-slate-200 max-w-xl leading-relaxed">
                        Move ocean container consignments across markets with operator-recorded checkpoints, verified UN/LOCODE seaport milestones, container shipping lines, and rule-based ETA tracking.
                    </p>

                    <!-- Dual Clean CTAs (Unified Blue & Glass) -->
                    <div class="flex flex-wrap items-center gap-4 pt-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="px-6 py-3.5 rounded-xl font-bold text-xs uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-500 shadow-xl shadow-blue-500/25 transition-all duration-200 hover:scale-[1.02] flex items-center gap-2">
                                <span>Open Maritime Portal</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="px-6 py-3.5 rounded-xl font-bold text-xs uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-500 shadow-xl shadow-blue-500/25 transition-all duration-200 hover:scale-[1.02] flex items-center gap-2">
                                <span>Open Maritime Portal</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @endauth

                        <a href="#network" class="px-6 py-3.5 rounded-xl font-semibold text-xs uppercase tracking-wider text-white bg-slate-900/60 hover:bg-slate-900/90 border border-white/20 hover:border-cyan-400/50 backdrop-blur-md transition-all duration-200">
                            Explore Global Network
                        </a>
                    </div>
                </div>

                <!-- Right Hero: Live Vessel Telemetry Card (Dashboard Global View Style) -->
                <div class="lg:col-span-5 xl:col-span-5">
                    @if($feat)
                    <div class="rounded-2xl bg-[#09182A]/85 backdrop-blur-xl border border-blue-400/20 p-6 space-y-6 shadow-2xl text-white max-w-md mx-auto lg:ml-auto">
                        <!-- Card Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-white/10">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-white tracking-wide block">{{ $feat->tracking_number }}</span>
                                    <span class="text-[9px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-500/20 text-cyan-300 font-mono">Recorded Milestone</span>
                                </div>
                                <span class="text-[11px] text-slate-300 font-mono block mt-1">{{ $feat->carrier->name ?? 'Mediterranean Shipping Company (MSC)' }}</span>
                            </div>
                            @if($feat->is_delayed && $feat->status === 'DELIVERED')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase font-mono bg-amber-400/20 text-amber-400 border border-amber-400/40">
                                    &bull; Delivered late (+{{ (int)$feat->delay_hours }}h)
                                </span>
                            @elseif($feat->is_delayed)
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase font-mono bg-amber-400/20 text-amber-400 border border-amber-400/40">
                                    &bull; Delayed (+{{ (int)$feat->delay_hours }}h)
                                </span>
                            @elseif($feat->status === 'DELIVERED')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase font-mono bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                                    &bull; Delivered
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase font-mono bg-blue-500/20 text-cyan-300 border border-blue-400/30">
                                    &bull; In Transit
                                </span>
                            @endif
                        </div>

                        <!-- Vertical Milestone Timeline (Dashboard Palette: Blue & Emerald) -->
                        <div class="space-y-5 relative">
                            <!-- Vertical Connecting Line -->
                            <div class="absolute left-[7px] top-3 bottom-3 w-[2px] bg-slate-700"></div>

                            <!-- Milestone 1: Origin Port -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full bg-blue-500 mt-0.5 shrink-0 ring-4 ring-blue-500/25 z-10 flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                </span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-bold text-white block">Origin Port &bull; Loaded</span>
                                        <span class="text-[11px] text-slate-300 font-mono">{{ $feat->originLocation->name ?? 'Port of Singapore (SGSIN)' }}</span>
                                    </div>
                                    <span class="text-slate-400 text-[11px] font-mono">{{ $feat->expected_departure_at?->format('M d, H:i') ?? 'Oct 25, 15:57' }}</span>
                                </div>
                            </div>

                            <!-- Milestone 2: Active Sea Leg -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full bg-cyan-400 mt-0.5 shrink-0 ring-4 ring-cyan-400/30 z-10 animate-pulse flex items-center justify-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-950"></span>
                                </span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-bold text-cyan-300 block">Current Location</span>
                                        <span class="text-[11px] text-slate-200 font-mono">{{ $feat->currentLocation->name ?? 'Malacca Strait Sea Lane' }}</span>
                                    </div>
                                    <span class="text-cyan-400 text-[11px] font-mono font-semibold">Under Voyage</span>
                                </div>
                            </div>

                            <!-- Milestone 3: Destination Port -->
                            <div class="relative flex items-start space-x-3.5">
                                <span class="w-4 h-4 rounded-full border-2 border-slate-500 bg-slate-900 mt-0.5 shrink-0 z-10"></span>
                                <div class="flex-1 flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-semibold text-slate-300 block">Destination Port</span>
                                        <span class="text-[11px] text-slate-400 font-mono">{{ $feat->destinationLocation->name ?? 'Port of Rotterdam (NLRTM)' }}</span>
                                    </div>
                                    <span class="text-slate-400 text-[11px] font-mono">{{ $feat->expected_arrival_at?->format('M d, H:i') ?? 'Nov 08, 15:57' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer: ETA & Vessel Coordinates -->
                        <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                            <div class="flex items-center space-x-2 text-xs">
                                <svg class="w-4 h-4 text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                </svg>
                                <span class="text-slate-300 font-medium">Estimated Arrival</span>
                            </div>
                            <span class="font-mono font-bold text-xs text-cyan-300">
                                {{ $feat->estimated_arrival_at?->format('M d, H:i') ?? 'Nov 11, 15:57' }}
                            </span>
                        </div>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </section>

    <!-- 2. GLOBAL METRICS STRIP (Synchronized with Dashboard Theme) -->
    <section class="theme-card-elevated border-b theme-border py-8 transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-y md:divide-y-0 md:divide-x theme-border">
                <div class="px-4">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold theme-heading tracking-tight font-mono">{{ $locations->count() }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 uppercase tracking-wider font-semibold">Container Seaports</div>
                </div>
                <div class="px-4 pt-6 md:pt-0">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-blue-600 dark:text-cyan-400 tracking-tight font-mono">{{ $onTimePercentage }}%</div>
                    <div class="text-xs theme-text-muted mt-1.5 uppercase tracking-wider font-semibold">On-Time Performance</div>
                </div>
                <div class="px-4 pt-6 md:pt-0">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold theme-heading tracking-tight font-mono">{{ $carriers->count() }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 uppercase tracking-wider font-semibold">Shipping Lines</div>
                </div>
                <div class="px-4 pt-6 md:pt-0">
                    <div class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-blue-600 dark:text-cyan-400 tracking-tight font-mono">{{ number_format($totalShipments) }}</div>
                    <div class="text-xs theme-text-muted mt-1.5 uppercase tracking-wider font-semibold">Tracked Consignments</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. SECTION LAYANAN & ESTIMASI ("Every shipment. One clear picture.") -->
    <section id="platform" class="py-20 lg:py-28 border-b theme-border theme-page transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
            
            <!-- Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div class="space-y-2">
                    <span class="text-xs font-semibold text-blue-600 dark:text-cyan-400 uppercase tracking-widest font-mono">LOGISTICS PLATFORM</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight theme-heading">
                        Every shipment. One clear picture.
                    </h2>
                    <p class="text-sm theme-text-secondary max-w-xl">
                        Milestone tracking, operator-verified checkpoints, and container visibility consolidated into one operations dashboard.
                    </p>
                </div>
                <a href="{{ route('login') }}" class="px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-500/20 transition-all duration-200 hover:scale-[1.02] self-start">
                    Explore the Platform &rarr;
                </a>
            </div>

            <!-- Grid Layout (Col 7: Platform Mock Preview, Col 5: 3 Photography Feature Cards) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                
                <!-- Left: Multi-Tab Platform Dashboard Mock (Global View Palette) -->
                <div class="lg:col-span-7 rounded-2xl theme-card border theme-border p-6 shadow-2xl flex flex-col justify-between">
                    <div class="space-y-5">
                        <!-- Top Pill Navigation Bar (Overview, Shipments, Alerts, Reports, Settings) -->
                        <div class="flex items-center justify-between pb-4 border-b theme-border overflow-x-auto">
                            <div class="flex items-center space-x-2 text-xs font-semibold">
                                <span class="px-3 py-1.5 rounded-lg bg-blue-600 text-white font-bold">Overview</span>
                                <span class="px-3 py-1.5 rounded-lg theme-card-elevated theme-text-secondary">Shipments</span>
                                <span class="px-3 py-1.5 rounded-lg theme-card-elevated theme-text-secondary">Alerts</span>
                                <span class="px-3 py-1.5 rounded-lg theme-card-elevated theme-text-secondary">Reports</span>
                                <span class="px-3 py-1.5 rounded-lg theme-card-elevated theme-text-secondary">Settings</span>
                            </div>
                            <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 hidden sm:inline-block">OPERATOR CHECKPOINTS</span>
                        </div>

                        <!-- Current Waybill Snapshot -->
                        <div class="p-4 rounded-xl theme-card-elevated border theme-border flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-cyan-400 tracking-wider font-mono">FEATURED WAYBILL</span>
                                <h4 class="text-base font-bold theme-heading font-mono">{{ $feat->tracking_number ?? 'CTG-SEA-2026-00189' }}</h4>
                                <span class="text-xs theme-text-muted">Origin: {{ $feat->originLocation->name ?? 'Singapore' }} &rarr; {{ $feat->destinationLocation->name ?? 'Rotterdam' }}</span>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase font-mono bg-emerald-500/20 text-emerald-500 border border-emerald-500/30">
                                &bull; In Transit
                            </span>
                        </div>

                        <!-- Active Routes Table Preview -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs font-mono">
                                <thead>
                                    <tr class="theme-text-muted border-b theme-border text-[11px] uppercase">
                                        <th class="pb-2">Waybill</th>
                                        <th class="pb-2">Delivery Status</th>
                                        <th class="pb-2 text-right">ETA</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y theme-border theme-text-secondary">
                                    @foreach($activeShipments->take(4) as $s)
                                    <tr>
                                        <td class="py-2.5 font-bold theme-heading">{{ $s->tracking_number }}</td>
                                        <td class="py-2.5">
                                            @if($s->is_delayed)
                                                <span class="text-amber-500 font-semibold">&bull; Delayed (+{{ (int) $s->delay_hours }}h)</span>
                                            @elseif($s->status === 'DELIVERED')
                                                <span class="text-emerald-500 font-semibold">&bull; Arrived</span>
                                            @else
                                                <span class="text-blue-600 dark:text-cyan-400 font-semibold">&bull; In Transit</span>
                                            @endif
                                        </td>
                                        <td class="py-2.5 text-right theme-text-muted">{{ $s->estimated_arrival_at ? $s->estimated_arrival_at->format('M d, H:i') : 'Tomorrow, 14:35' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="pt-4 border-t theme-border flex items-center justify-between text-xs theme-text-muted">
                        <span>Milestones updated upon operator submission</span>
                        <a href="{{ route('login') }}" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Access full operations console &rarr;</a>
                    </div>
                </div>

                <!-- Right: 3 Photography Feature Cards (Ocean Freight, Seaports Network, News Wire) -->
                <div class="lg:col-span-5 space-y-4 flex flex-col justify-between">
                    
                    <!-- Card 1: Ocean Freight -->
                    <div class="group relative rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 p-5 flex flex-col justify-between min-h-[160px] shadow-xl hover:border-blue-400/50 transition-all duration-300">
                        <div class="absolute inset-0 z-0">
                            <img src="{{ asset('images/hero-vessel.webp') }}" alt="Ocean container vessel loaded with cargo" class="w-full h-full object-cover opacity-35 group-hover:opacity-45 group-hover:scale-105 transition-all duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/20"></div>
                        </div>
                        <div class="relative z-10 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-cyan-400 tracking-wider font-mono">OCEAN FREIGHT</span>
                            <h3 class="text-base font-bold text-white tracking-tight">Time-Critical Container Shipping</h3>
                            <p class="text-xs text-slate-300">Global container sailings with Maersk, MSC, CMA CGM, COSCO, and recorded port milestones.</p>
                        </div>
                        <div class="relative z-10 pt-2 flex items-center justify-between text-xs text-slate-300 font-semibold">
                            <span>Maersk &bull; MSC &bull; CMA CGM</span>
                            <span class="text-cyan-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </div>

                    <!-- Card 2: Seaports Network -->
                    <div class="group relative rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 p-5 flex flex-col justify-between min-h-[160px] shadow-xl hover:border-blue-400/50 transition-all duration-300">
                        <div class="absolute inset-0 z-0">
                            <img src="{{ asset('images/hero-port.webp') }}" alt="Deepwater container terminal and gantry cranes" class="w-full h-full object-cover opacity-35 group-hover:opacity-45 group-hover:scale-105 transition-all duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/20"></div>
                        </div>
                        <div class="relative z-10 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-blue-400 tracking-wider font-mono">SEAPORTS NETWORK</span>
                            <h3 class="text-base font-bold text-white tracking-tight">Deepwater Berths & Terminals</h3>
                            <p class="text-xs text-slate-300">Verified UN/LOCODE seaport milestones, quay crane gantry metrics, and gate timestamps.</p>
                        </div>
                        <div class="relative z-10 pt-2 flex items-center justify-between text-xs text-slate-300 font-semibold">
                            <span>CNSHA &bull; NLRTM &bull; SGSIN</span>
                            <span class="text-blue-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </div>

                    <!-- Card 3: Maritime News Wire -->
                    <div class="group relative rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 p-5 flex flex-col justify-between min-h-[160px] shadow-xl hover:border-blue-400/50 transition-all duration-300">
                        <div class="absolute inset-0 z-0">
                            <img src="{{ asset('images/service-ocean.webp') }}" alt="Maritime container transport logistics" class="w-full h-full object-cover opacity-35 group-hover:opacity-45 group-hover:scale-105 transition-all duration-500">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/70 to-slate-950/20"></div>
                        </div>
                        <div class="relative z-10 space-y-1">
                            <span class="text-[10px] uppercase font-bold text-emerald-400 tracking-wider font-mono">MARITIME NEWS WIRE</span>
                            <h3 class="text-base font-bold text-white tracking-tight">Global Trade Intelligence</h3>
                            <p class="text-xs text-slate-300">Live port congestion alerts, weather advisories, and foreign currency FX conversion.</p>
                        </div>
                        <div class="relative z-10 pt-2 flex items-center justify-between text-xs text-slate-300 font-semibold">
                            <span>OpenWeather &bull; OpenExchange &bull; NewsData</span>
                            <span class="text-emerald-400 group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    <!-- 4. TRUST & CREDIBILITY SECTION -->
    <section class="py-16 theme-page border-b theme-border transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div class="lg:col-span-5 space-y-2">
                    <span class="text-[10px] uppercase font-bold tracking-widest text-blue-600 dark:text-cyan-400 font-mono">TRUST & CREDIBILITY</span>
                    <h3 class="text-2xl sm:text-3xl font-bold theme-heading tracking-tight leading-snug">
                        Chosen by teams that cannot afford uncertainty.
                    </h3>
                </div>
                
                <!-- 4 Trust Pillars -->
                <div class="lg:col-span-7 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs font-mono">
                    <div class="p-3.5 rounded-xl theme-card border theme-border">
                        <span class="text-lg font-bold text-emerald-500 block">{{ $onTimePercentage }}%</span>
                        <span class="theme-text-muted text-[11px]">On-time delivery</span>
                    </div>
                    <div class="p-3.5 rounded-xl theme-card border theme-border">
                        <span class="text-lg font-bold text-blue-600 dark:text-cyan-400 block">{{ count($activeShipments) }}</span>
                        <span class="theme-text-muted text-[11px]">Active consignments</span>
                    </div>
                    <div class="p-3.5 rounded-xl theme-card border theme-border">
                        <span class="text-lg font-bold text-cyan-500 block">{{ count($locations) }}</span>
                        <span class="theme-text-muted text-[11px]">Container seaports</span>
                    </div>
                    <div class="p-3.5 rounded-xl theme-card border theme-border">
                        <span class="text-lg font-bold theme-heading block">{{ count($carriers) }}</span>
                        <span class="theme-text-muted text-[11px]">Registered carriers</span>
                    </div>
                </div>
            </div>

            <!-- Registered Shipping Lines -->
            <div class="pt-6 border-t theme-border">
                <div class="flex flex-wrap items-center justify-between gap-6 opacity-60 hover:opacity-100 transition-opacity text-xs font-bold tracking-widest uppercase font-mono theme-text-muted">
                    @foreach($carriers->take(6) as $c)
                        <span class="hover:theme-heading transition cursor-default">{{ $c->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- 5. GLOBAL NETWORK & LEAFLET MAP SECTION -->
    <section id="network" class="py-20 lg:py-28 theme-page border-b theme-border transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <span class="text-xs font-semibold text-blue-600 dark:text-cyan-400 uppercase tracking-widest font-mono">GLOBAL NETWORK</span>
                    <h2 class="text-3xl sm:text-4xl font-bold tracking-tight theme-heading mt-1">
                        Connected across the world.
                    </h2>
                    <p class="text-xs sm:text-sm theme-text-secondary mt-1">Strategic deepwater seaports and oceanic shipping lanes tracked through verified UN/LOCODE milestones.</p>
                </div>
                <div class="flex items-center space-x-6 text-xs font-mono">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-pulse"></span>
                        <span class="theme-text-secondary">Active Shipments: <strong>{{ count($activeShipments) }}</strong></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span class="theme-text-secondary">Distribution Hubs: <strong>{{ count($locations) }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Leaflet Container with OpenStreetMap tiles -->
            <div class="relative rounded-2xl overflow-hidden border theme-border shadow-2xl theme-card">
                <div id="landing-world-map" class="w-full h-[480px] z-10"></div>
                <div class="absolute bottom-4 left-4 z-20 px-3.5 py-2 rounded-xl theme-card-elevated border theme-border text-[11px] theme-text-muted font-mono shadow-lg">
                    <span>Interactive OpenStreetMap &bull; Geodesic Bezier arcs connecting ports</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. MARITIME INTELLIGENCE: CURRENCY FX & NEWS WIRE -->
    <section id="news" class="py-16 border-b theme-border theme-card-elevated transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Currency FX Rates -->
                <div id="rates" class="lg:col-span-5 rounded-2xl theme-card border theme-border p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b theme-border">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-cyan-400 tracking-wider font-mono">EXCHANGE RATES</span>
                            <h3 class="text-base font-bold theme-heading tracking-tight">Global Currency FX Matrix</h3>
                        </div>
                        <x-data-source-badge :source="$currencyData['data_source'] ?? 'simulated'" />
                    </div>
                    <p class="text-xs theme-text-secondary">Live & cached foreign exchange rates for automated cargo value reconciliation.</p>

                    <div class="grid grid-cols-2 gap-3 text-xs font-mono">
                        @php
                            $majorCurrencies = ['EUR', 'CNY', 'SGD', 'GBP', 'JPY', 'IDR', 'AUD', 'CAD', 'CHF', 'AED'];
                            $filteredRates = !empty($rates) ? array_intersect_key($rates, array_flip($majorCurrencies)) : [];
                            if (empty($filteredRates)) {
                                $filteredRates = array_slice($rates, 0, 10, true);
                            }
                        @endphp
                        @foreach($filteredRates as $cur => $rate)
                            <div class="p-3 rounded-xl theme-card-elevated border theme-border flex items-center justify-between">
                                <span class="font-bold theme-heading">USD / {{ $cur }}</span>
                                <span class="text-blue-600 dark:text-cyan-400 font-semibold">{{ is_numeric($rate) ? number_format($rate, 4) : $rate }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right: Maritime & Logistics News Feed -->
                <div class="lg:col-span-7 rounded-2xl theme-card border theme-border p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b theme-border">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-cyan-400 tracking-wider font-mono">EXTERNAL NEWS WIRE</span>
                            <h3 class="text-base font-bold theme-heading tracking-tight">Maritime & Supply Chain Bulletins</h3>
                        </div>
                        <x-data-source-badge :source="$news[0]['data_source'] ?? 'simulated'" />
                    </div>

                    <div class="divide-y theme-border space-y-3">
                        @foreach(array_slice($news, 0, 4) as $item)
                            <div class="pt-3 first:pt-0 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <a href="{{ $item['url'] ?? '#' }}" target="_blank" rel="noopener" class="font-bold theme-heading hover:text-blue-500 dark:hover:text-cyan-400 transition cursor-pointer">
                                        {{ $item['headline'] ?? $item['title'] ?? 'Logistics Update' }}
                                    </a>
                                    <span class="text-[10px] theme-text-muted font-mono shrink-0 ml-2">{{ $item['source'] ?? 'Global Desk' }}</span>
                                </div>
                                <p class="text-xs theme-text-secondary leading-relaxed">{{ $item['summary'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 7. ENTERPRISE FOOTER -->
    <footer class="theme-sidebar border-t theme-border py-12 text-xs theme-text-muted transition-colors duration-200">
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 flex items-center justify-center text-white font-bold text-xs shadow-md shadow-blue-500/25">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/>
                    </svg>
                </div>
                <div>
                    <span class="theme-heading font-semibold block">&copy; {{ date('Y') }} CargoTrace &bull; Global Shipment Visibility Platform</span>
                    <span class="text-[11px] text-blue-600 dark:text-cyan-400 font-mono">Academic Capstone Evaluation Notice: Pure Maritime Container Tracking.</span>
                </div>
            </div>
            <div class="flex items-center space-x-6 theme-text-secondary">
                <a href="#network" class="hover:theme-heading transition">Ports Network</a>
                <a href="#platform" class="hover:theme-heading transition">Platform Overview</a>
                <a href="{{ route('login') }}" class="text-blue-600 dark:text-cyan-400 hover:underline font-semibold">Operations Login</a>
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

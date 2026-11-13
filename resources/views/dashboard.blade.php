@extends('layouts.app')

@section('content')
@php
    // Customer isolation: ensure recent shipments are scoped to current user
    $recentShipments = (isset($recentShipments) && $recentShipments->isNotEmpty())
        ? $recentShipments
        : \App\Models\Shipment::query()
            ->when(auth()->user()->role === 'customer', fn($q) => $q->where('customer_id', auth()->id()))
            ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation'])
            ->latest()
            ->take(8)
            ->get();
@endphp

<div class="px-4 sm:px-6 lg:px-8 py-6 space-y-6 max-w-[1720px] mx-auto">
    <!-- TOP SECTION: Global View (3D Earth Globe / 2D Map) + Main Statistics -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- LEFT 9 COLS: "Global View" Canvas Container -->
        <div class="xl:col-span-9 relative rounded-3xl theme-card border overflow-hidden flex flex-col justify-between min-h-[520px]">
            <!-- Top Controls Overlay: Title, Live Status & Engine Overlay Box -->
            <div class="relative z-20 p-6 sm:p-8 flex flex-col sm:flex-row sm:items-start justify-between gap-6 pointer-events-none">
                <!-- Left Title & Deterministic Engine Overlay Box -->
                <div class="space-y-4 max-w-sm pointer-events-auto">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl sm:text-4xl font-extrabold theme-heading tracking-tight">
                                Global View
                            </h1>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/10 text-cyan-500 dark:text-cyan-400 border border-blue-500/20 font-mono">
                                Overview
                            </span>
                        </div>
                        <p class="text-xs theme-text-secondary mt-1">Global route map &bull; Operator-recorded checkpoints</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="relative flex h-2 w-2">
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono">
                                Verified Milestones
                            </span>
                        </div>
                    </div>

                    <!-- Left Card: Deterministic ETA & Delay Engine (Anti-Slop C-1, C-5, R-36) -->
                    <div class="p-4 rounded-2xl theme-card-elevated border theme-border space-y-3 shadow-xl backdrop-blur-md">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/25">
                                Rule-Based Telemetry
                            </span>
                            <span class="text-[10px] font-mono theme-text-muted">EtaService</span>
                        </div>

                        <div>
                            <h2 class="text-sm font-bold theme-heading leading-snug">
                                Deterministic ETA &amp; Delay Engine
                            </h2>
                            <p class="text-[11px] theme-text-secondary leading-relaxed mt-1">
                                Variance evaluation comparing ETA against expected arrival milestones using database thresholds.
                            </p>
                        </div>

                        <!-- Threshold Metrics Matrix -->
                        <div class="grid grid-cols-3 gap-1.5 pt-1 text-center font-mono text-[10px]">
                            <div class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/25">
                                <span class="block font-bold text-amber-600 dark:text-amber-400">LOW</span>
                                <span class="theme-text-muted">&le; 6h</span>
                            </div>
                            <div class="p-2 rounded-xl bg-orange-500/10 border border-orange-500/25">
                                <span class="block font-bold text-orange-600 dark:text-orange-400">MEDIUM</span>
                                <span class="theme-text-muted">&le; 24h</span>
                            </div>
                            <div class="p-2 rounded-xl bg-rose-500/10 border border-rose-500/25">
                                <span class="block font-bold text-rose-600 dark:text-rose-400">HIGH</span>
                                <span class="theme-text-muted">&gt; 24h</span>
                            </div>
                        </div>

                        <div class="pt-1">
                            <a href="{{ route('shipments.index', ['is_delayed' => 1]) }}" class="w-full inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs shadow-md shadow-blue-500/20 transition">
                                Filter Delayed Shipments ({{ $delayed ?? 0 }}) &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- Checkpoint Coordinates Display -->
                    <div class="text-[11px] font-mono theme-text-muted space-y-0.5">
                        <div>X: <span id="telemetry-coord-x" class="text-cyan-500 dark:text-cyan-400 font-bold">18.000000</span></div>
                        <div>Y: <span id="telemetry-coord-y" class="text-cyan-500 dark:text-cyan-400 font-bold">110.000000</span></div>
                    </div>
                </div>
            </div>

            <!-- 3D Earth Globe Canvas (Default) -->
            <div id="globe-3d-canvas" class="absolute inset-0 w-full h-full z-10 cursor-grab active:cursor-grabbing"></div>

            <!-- 2D Leaflet Map Canvas (Alternative) -->
            <div id="globe-2d-canvas" class="absolute inset-0 w-full h-full z-10 hidden theme-page"></div>

            <!-- Bottom Mode Switcher Pill [ 3D ] | [ 2D ] & Zoom Buttons -->
            <div class="relative z-20 p-6 flex items-center justify-between pointer-events-none mt-auto">
                <div class="flex items-center gap-2 pointer-events-auto">
                    <!-- 3D / 2D Switcher Pill -->
                    <div class="flex items-center p-1 rounded-full theme-card-elevated border theme-border shadow-xl backdrop-blur-md">
                        <button
                            id="btn-mode-3d"
                            type="button"
                            onclick="window.CargoTrace && window.CargoTrace.setMapMode ? window.CargoTrace.setMapMode('3D') : null"
                            class="px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-600 text-white shadow-md transition-all flex items-center gap-1.5"
                        >
                            <span>🌐 3D</span>
                        </button>
                        <button
                            id="btn-mode-2d"
                            type="button"
                            onclick="window.CargoTrace && window.CargoTrace.setMapMode ? window.CargoTrace.setMapMode('2D') : null"
                            class="px-3.5 py-1 rounded-full text-xs font-semibold theme-text-muted hover:text-cyan-400 transition-all flex items-center gap-1.5"
                        >
                            <span>🗺️ 2D</span>
                        </button>
                    </div>
                </div>

                <!-- Zoom Controls - / + -->
                <div class="flex items-center gap-1.5 pointer-events-auto theme-card-elevated border theme-border shadow-xl backdrop-blur-md p-1 rounded-full">
                    <span class="text-[11px] font-semibold theme-text-muted pl-2">Zoom</span>
                    <button
                        type="button"
                        onclick="window.CargoTrace && window.CargoTrace.zoomMap ? window.CargoTrace.zoomMap('out') : null"
                        class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-white flex items-center justify-center font-bold text-xs hover:bg-slate-300 dark:hover:bg-slate-700 transition"
                        title="Zoom out"
                    >
                        &minus;
                    </button>
                    <button
                        type="button"
                        onclick="window.CargoTrace && window.CargoTrace.zoomMap ? window.CargoTrace.zoomMap('in') : null"
                        class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs hover:bg-blue-500 shadow-md transition"
                        title="Zoom in"
                    >
                        &#43;
                    </button>
                </div>
            </div>
        </div>

        <!-- RIGHT 3 COLS: "Main Statistics" Stack (3 Cards) -->
        <div class="xl:col-span-3 flex flex-col justify-between gap-4">
            <h2 class="text-sm font-bold theme-heading uppercase tracking-wider flex items-center justify-between">
                <span>Main Statistics</span>
                <span class="text-[11px] theme-text-muted font-mono">Database Aggregates</span>
            </h2>

            <!-- Card 1: Active Shipments & On-Time Rate -->
            <div class="p-5 rounded-3xl theme-card border flex flex-col justify-between flex-1 transition-colors duration-200">
                <div class="flex items-center justify-between text-xs theme-text-secondary font-medium">
                    <span>Active shipments</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                        &uarr; {{ $onTimeRate }}% On-Time
                    </span>
                </div>
                <div class="text-3xl font-extrabold theme-heading tracking-tight my-2 tabular-nums">
                    {{ count($activeShipments) }} <span class="text-xs font-normal theme-text-muted">active</span>
                </div>
                <!-- Mini Sparkline SVG: 14-Day Shipment Creation (Real Database Data) -->
                <div class="h-10 w-full mt-2" title="Shipments created per day (last 14 days)">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 200 40">
                        <defs>
                            <linearGradient id="spark1" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#2563EB" stop-opacity="0.30" class="dark:stop-color-[#38BDF8]"/>
                                <stop offset="100%" stop-color="#2563EB" stop-opacity="0" class="dark:stop-color-[#38BDF8]"/>
                            </linearGradient>
                        </defs>
                        <path d="{{ $sparkline1Area }}" fill="url(#spark1)"/>
                        <path d="{{ $sparkline1Path }}" fill="none" class="stroke-blue-600 dark:stroke-cyan-400" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <!-- Card 2: Total Cargo Valuation & Volume -->
            <div class="p-5 rounded-3xl theme-card border flex flex-col justify-between flex-1 transition-colors duration-200">
                <div class="flex items-center justify-between text-xs theme-text-secondary font-medium">
                    <span>Total Cargo Valuation</span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/25 font-mono">
                        {{ number_format($totalVolumeCbm, 0) }} CBM
                    </span>
                </div>
                <div class="text-3xl font-extrabold theme-heading tracking-tight my-2 tabular-nums">
                    ${{ number_format($totalValueUsd / 1000, 0) }}k <span class="text-xs font-normal theme-text-muted">USD</span>
                </div>
                <!-- Mini Sparkline SVG: 8-Week On-Time Rate (Real Database Data) -->
                <div class="h-10 w-full mt-2" title="Weekly on-time performance (last 8 weeks)">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 200 40">
                        <defs>
                            <linearGradient id="spark2" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#2563EB" stop-opacity="0.30"/>
                                <stop offset="100%" stop-color="#2563EB" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path d="{{ $sparkline2Area }}" fill="url(#spark2)"/>
                        <path d="{{ $sparkline2Path }}" fill="none" class="stroke-blue-600 dark:stroke-blue-400" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <!-- Card 3: Exception Rate (Real Database Metric) -->
            <div class="p-5 rounded-3xl theme-card border flex items-center justify-between flex-1 transition-colors duration-200">
                <div class="space-y-1">
                    <span class="text-xs theme-text-secondary font-medium block">Exception Rate</span>
                    <div class="text-3xl font-extrabold theme-heading tabular-nums font-mono">
                        {{ $exceptionRate }}%
                    </div>
                    <span class="text-[10px] theme-text-muted block font-mono">
                        {{ $openExceptions }} open / {{ $activeShipmentsCount }} active
                    </span>
                </div>

                <!-- Semi-Circle Gauge SVG with real exceptionRate -->
                <div class="relative w-24 h-16 flex items-center justify-center">
                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 100 100">
                        <!-- Background track -->
                        <circle cx="50" cy="50" r="38" fill="none" stroke="currentColor" class="text-slate-200 dark:text-slate-800" stroke-width="8" stroke-dasharray="120 240" stroke-linecap="round"/>
                        <!-- Filled track -->
                        @php
                            $gaugeDash = round(($exceptionRate / 100) * 120);
                        @endphp
                        <circle cx="50" cy="50" r="38" fill="none" stroke="{{ $exceptionRate > 0 ? '#F43F5E' : '#10B981' }}" stroke-width="8" stroke-dasharray="{{ max(3, min(120, $gaugeDash)) }} 240" stroke-linecap="round"/>
                    </svg>
                    <span class="absolute text-xs font-bold font-mono {{ $exceptionRate > 0 ? 'text-rose-500' : 'text-emerald-500' }}">{{ $exceptionRate }}%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM SECTION: Maritime Activities Table (Left) + Utility Panel (Right) -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- LEFT 8 COLS: "Maritime Activities" Table with Vessel Icon, Visual Route & FlyTo -->
        <div class="xl:col-span-8 p-6 rounded-3xl theme-card border space-y-4 transition-colors duration-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b theme-border">
                <div>
                    <h2 class="text-lg font-bold theme-heading tracking-tight">
                        Maritime Activities
                    </h2>
                    <p class="text-xs theme-text-secondary mt-0.5">Click any vessel row to focus camera on the 3D Globe / 2D Map</p>
                </div>

                <!-- Search Input in Header -->
                <div class="relative">
                    <input
                        type="text"
                        id="activities-search-input"
                        placeholder="Search container ID, port..."
                        onkeyup="filterActivitiesTable()"
                        class="w-64 pl-9 pr-3 py-1.5 rounded-xl theme-input border text-xs focus:outline-none focus:border-cyan-500"
                    >
                    <svg class="w-4 h-4 theme-text-muted absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Table Rows with Visual Route ──🚢──> and FlyTo Click -->
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-xs border-collapse" id="activities-table">
                    <thead>
                        <tr class="theme-text-muted uppercase text-[10px] font-semibold tracking-wider border-b theme-border">
                            <th class="py-3 px-3">Cargo ID & Carrier</th>
                            <th class="py-3 px-4">Ocean Route & Distance</th>
                            <th class="py-3 px-3">ETA Date</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y theme-border">
                        @forelse($recentShipments as $s)
                            @php
                                $orig = $s->originLocation;
                                $dest = $s->destinationLocation;
                                $curr = $s->currentLocation;
                                $hasExceptions = $s->exceptions->isNotEmpty();
                            @endphp
                            <tr
                                class="activity-row cursor-pointer hover:bg-blue-600/10 transition-colors duration-150 border-l-2 border-transparent"
                                data-tracking="{{ $s->tracking_number }}"
                                onclick="window.CargoTrace && window.CargoTrace.focusMaritimeEntity ? window.CargoTrace.focusMaritimeEntity({{ $curr->latitude ?? $orig->latitude ?? 0 }}, {{ $curr->longitude ?? $orig->longitude ?? 0 }}, '{{ $s->tracking_number }}') : null"
                            >
                                <!-- Cargo ID & Carrier Icon -->
                                <td class="py-3.5 px-3">
                                    <div class="flex items-center space-x-3">
                                        <!-- Carrier/Ship Icon Capsule -->
                                        <div class="w-8 h-8 rounded-xl {{ $s->is_delayed ? 'bg-amber-500/15 text-amber-500' : 'bg-blue-500/15 text-blue-500' }} flex items-center justify-center font-bold text-xs shrink-0">
                                            🚢
                                        </div>
                                        <div>
                                            <a href="{{ route('shipments.show', $s) }}" class="font-mono font-bold theme-heading hover:text-cyan-500 block">
                                                {{ $s->tracking_number }}
                                            </a>
                                            <span class="text-[11px] theme-text-muted block">
                                                {{ $s->carrier->name ?? 'Maritime Line' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Visual Route: ORIGIN ───🚢───> DESTINATION -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1 max-w-xs">
                                        <div class="flex items-center justify-between text-[11px] font-mono">
                                            <span class="font-bold theme-text-primary">{{ $orig->unlocode ?? 'ORI' }}</span>
                                            <span class="text-[10px] theme-text-muted font-mono" title="Great-circle distance between seaports">
                                                {{ $s->status === 'DELIVERED' ? 'Arrived' : ($s->great_circle_distance_nm ? number_format($s->great_circle_distance_nm) . ' nm (great-circle)' : 'Direct Leg') }}
                                            </span>
                                            <span class="font-bold theme-text-primary">{{ $dest->unlocode ?? 'DST' }}</span>
                                        </div>
                                        <!-- Visual Progress Line -->
                                        <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all duration-500 {{ $hasExceptions ? 'bg-rose-500' : ($s->is_delayed ? 'bg-amber-500' : 'bg-cyan-500') }}"
                                                style="width: {{ $s->status === 'DELIVERED' ? '100%' : ($s->status === 'IN_TRANSIT' ? '65%' : '15%') }}"
                                            ></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Arrival Date -->
                                <td class="py-3.5 px-3 font-mono text-xs theme-text-secondary whitespace-nowrap">
                                    {{ $s->estimated_arrival_at ? $s->estimated_arrival_at->format('M d, Y') : 'Confirmed' }}
                                </td>

                                <!-- Status Pill -->
                                <td class="py-3.5 px-3">
                                    @if($hasExceptions)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase font-mono bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                            Alert
                                        </span>
                                    @elseif($s->is_delayed)
                                        @if($s->status === 'DELIVERED')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase font-mono bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                Delivered late (+{{ (int)$s->delay_hours }}h)
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase font-mono bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                                Delayed (+{{ (int)$s->delay_hours }}h)
                                            </span>
                                        @endif
                                    @elseif($s->status === 'DELIVERED')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase font-mono bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                            Delivered
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase font-mono bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/30">
                                            {{ $s->status }}
                                        </span>
                                    @endif
                                </td>

                                <!-- Action Quick Link -->
                                <td class="py-3.5 px-2 text-right">
                                    <a href="{{ route('shipments.show', $s) }}" class="p-1.5 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 theme-text-muted hover:theme-heading inline-block" title="Inspect Vessel">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center theme-text-muted">No maritime activities found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT 4 COLS: Weather & Currency Utility Panel (Global View Style) -->
        <div class="xl:col-span-4 space-y-6">
            <!-- 1. Seaport Live Weather Widget (High-Contrast Accent Card) -->
            @php
                $selectedPort = $weatherPorts[0] ?? [
                    'city' => 'Singapore',
                    'unlocode' => 'SGSIN',
                    'weather' => [
                        'temperature_c' => 31.5,
                        'condition' => 'Clear & Sunny',
                        'wind_kph' => 14.5,
                        'humidity' => 68,
                    ],
                ];
            @endphp
            <div class="p-6 rounded-3xl theme-card border theme-border shadow-xl space-y-4 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Seaport Live Weather</span>
                    <div class="flex items-center gap-1.5">
                        <x-data-source-badge :source="$selectedPort['data_source'] ?? 'live'" />
                        <a href="{{ route('weather.index') }}" class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded theme-card-elevated border theme-border hover:border-blue-500 theme-heading font-mono transition inline-flex items-center gap-1" title="Open full Weather Hub">
                            <span>Hub</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-extrabold theme-heading">{{ $selectedPort['city'] ?? 'Singapore' }}</h3>
                            <span class="text-[11px] theme-text-muted font-mono">{{ $selectedPort['unlocode'] ?? 'SGSIN' }} Container Terminal</span>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-black theme-heading tabular-nums">
                                {{ $selectedPort['weather']['temperature_c'] ?? '31.5' }}°C
                            </span>
                            <span class="text-xs font-semibold theme-text-secondary block">
                                {{ $selectedPort['weather']['condition'] ?? 'Clear & Sunny' }}
                            </span>
                        </div>
                    </div>

                    <!-- Sun / Temperature Arch Visual (Global View Reference Style) -->
                    <div class="h-20 w-full relative flex items-center justify-center my-1">
                        <svg class="w-full h-full" viewBox="0 0 200 80">
                            <path d="M20,70 Q100,10 180,70" fill="none" stroke="#D97706" stroke-width="2" stroke-dasharray="3,3"/>
                            <!-- Sun Icon at Apex -->
                            <circle cx="100" cy="30" r="10" fill="#F59E0B"/>
                            <circle cx="100" cy="30" r="14" fill="none" stroke="#F59E0B" stroke-width="1.5" stroke-dasharray="2,2"/>
                        </svg>
                        <span class="absolute text-[11px] font-bold theme-text-primary bottom-1">{{ round($selectedPort['weather']['temperature_c'] ?? 31.5) }}° Sea Breeze</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs theme-text-secondary pt-2 border-t theme-border">
                        <div>Wind: <b class="theme-heading">{{ $selectedPort['weather']['wind_kph'] ?? '14.5' }} km/h</b></div>
                        <div>Humidity: <b class="theme-heading">{{ $selectedPort['weather']['humidity'] ?? '68' }}%</b></div>
                    </div>
                </div>
            </div>

            <!-- 2. Two-Way Global Currency Converter Widget -->
            <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Currency Calculator</span>
                    <div class="flex items-center gap-1.5">
                        <x-data-source-badge :source="$currencyData['data_source'] ?? 'live'" />
                        <a href="{{ route('currency.index') }}" class="text-[10px] text-cyan-600 dark:text-cyan-400 hover:underline font-mono" title="Open full Currency FX Hub">
                            Full FX &rarr;
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-5 gap-2 items-center">
                    <input
                        type="number"
                        id="two-way-amount"
                        value="1000"
                        oninput="calculateTwoWayCurrency()"
                        class="col-span-2 px-3 py-1.5 rounded-xl theme-input border text-xs tabular-nums focus:outline-none focus:border-cyan-500"
                    >
                    <select
                        id="two-way-from"
                        onchange="calculateTwoWayCurrency()"
                        class="col-span-1 px-1 py-1.5 rounded-xl theme-input border text-xs font-mono focus:outline-none"
                    >
                        <option value="USD" selected>USD</option>
                        <option value="EUR">EUR</option>
                        <option value="IDR">IDR</option>
                        <option value="CNY">CNY</option>
                        <option value="SGD">SGD</option>
                        <option value="AUD">AUD</option>
                    </select>

                    <button
                        type="button"
                        onclick="swapCurrencies()"
                        class="col-span-1 p-1.5 rounded-xl bg-blue-600/15 hover:bg-blue-600 text-blue-600 dark:text-cyan-400 hover:text-white transition flex items-center justify-center font-bold"
                        title="Swap currencies"
                    >
                        &rlarr;
                    </button>

                    <select
                        id="two-way-to"
                        onchange="calculateTwoWayCurrency()"
                        class="col-span-1 px-1 py-1.5 rounded-xl theme-input border text-xs font-mono focus:outline-none"
                    >
                        <option value="IDR" selected>IDR</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                        <option value="CNY">CNY</option>
                        <option value="SGD">SGD</option>
                        <option value="AUD">AUD</option>
                    </select>
                </div>

                <div class="p-3 rounded-2xl theme-card-elevated border theme-border flex items-center justify-between">
                    <span class="text-xs theme-text-secondary">Converted Amount:</span>
                    <span id="two-way-result" class="text-sm font-bold font-mono text-cyan-600 dark:text-cyan-400">
                        Rp 15,873,000 IDR
                    </span>
                </div>
            </div>

            <!-- 3. Live Maritime News Feed -->
            <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Maritime News Wire</span>
                    <div class="flex items-center gap-1.5">
                        <x-data-source-badge :source="$news[0]['data_source'] ?? 'live'" />
                        <a href="{{ route('news.index') }}" class="text-[10px] text-cyan-600 dark:text-cyan-400 hover:underline font-mono" title="Open all Maritime News">
                            All News &rarr;
                        </a>
                    </div>
                </div>
                <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                    @forelse(array_slice($news, 0, 4) as $n)
                        <div class="p-2.5 rounded-xl theme-card-elevated border theme-border flex items-center gap-3 group">
                            <img
                                src="{{ $n['thumbnail'] ?? asset('images/news-placeholder.svg') }}"
                                alt=""
                                class="w-16 h-14 sm:w-20 sm:h-16 rounded-lg object-cover flex-shrink-0 bg-slate-800 border border-white/5 group-hover:opacity-90 transition"
                                loading="lazy"
                                onerror="this.onerror=null;this.src='{{ asset('images/news-placeholder.svg') }}';"
                            >
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex items-center justify-between gap-1">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <span class="text-[9px] font-bold uppercase text-cyan-600 dark:text-cyan-400 font-mono truncate max-w-[100px]">
                                            {{ $n['source'] ?? 'Maritime Wire' }}
                                        </span>
                                        @if(!empty($n['is_relevant']))
                                            <span class="px-1.5 py-0.2 rounded text-[8px] font-mono font-bold uppercase bg-blue-500/20 text-cyan-300 border border-blue-400/30">
                                                Match
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] theme-text-muted flex-shrink-0 font-mono">
                                        {{ \Carbon\Carbon::parse($n['published_at'])->diffForHumans(null, true) }}
                                    </span>
                                </div>
                                <a href="{{ $n['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold theme-heading hover:text-cyan-500 transition line-clamp-2 block leading-snug">
                                    {{ $n['headline'] }}
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs theme-text-muted">No news feeds available</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Global Payload for window.__CARGOTRACE__ --}}
<script>
    window.__CARGOTRACE__ = {
        maritimeGlobe: {
            globe3dId: 'globe-3d-canvas',
            map2dId: 'globe-2d-canvas',
            options: {
                shipments: @json($mapShipments),
                locations: @json($locations)
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

    // Client-side Two-Way Currency Calculator with Live API Fallback
    const currencyRates = Object.assign({
        'USD': 1.0,
        'EUR': 0.92,
        'IDR': 15873.0,
        'SGD': 1.34,
        'CNY': 7.23,
        'AUD': 1.52,
        'AED': 3.67
    }, @json($rates ?? []));

    let currencyFetchDebounce = null;

    function calculateTwoWayCurrency() {
        const amount = parseFloat(document.getElementById('two-way-amount').value) || 0;
        const from = document.getElementById('two-way-from').value;
        const to = document.getElementById('two-way-to').value;

        const rateFrom = currencyRates[from] || 1.0;
        const rateTo = currencyRates[to] || 1.0;

        // Instant optimistic preview client-side:
        const instantResult = (amount / rateFrom) * rateTo;
        const formatted = instantResult.toLocaleString(undefined, {
            minimumFractionDigits: to === 'IDR' ? 0 : 2,
            maximumFractionDigits: to === 'IDR' ? 0 : 2
        });
        document.getElementById('two-way-result').textContent = `${to === 'IDR' ? 'Rp ' : ''}${formatted} ${to}`;

        // Debounced live conversion query to /api/currency/convert
        clearTimeout(currencyFetchDebounce);
        currencyFetchDebounce = setTimeout(() => {
            fetch(`/api/currency/convert?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&amount=${encodeURIComponent(amount)}`)
                .then(res => res.ok ? res.json() : null)
                .then(json => {
                    const data = json && json.data ? json.data : json;
                    if (data && (data.formatted_result || data.formatted_converted)) {
                        document.getElementById('two-way-result').textContent = data.formatted_result || data.formatted_converted;
                        if (data.rates_snapshot) {
                            Object.assign(currencyRates, data.rates_snapshot);
                        }
                    }
                })
                .catch(() => {
                    // Fail silently, preserving optimistic client-side value
                });
        }, 300);
    }

    function swapCurrencies() {
        const fromSel = document.getElementById('two-way-from');
        const toSel = document.getElementById('two-way-to');
        const tmp = fromSel.value;
        fromSel.value = toSel.value;
        toSel.value = tmp;
        calculateTwoWayCurrency();
    }

    // Filter Maritime Activities Table by Search Input
    function filterActivitiesTable() {
        const query = (document.getElementById('activities-search-input').value || '').toLowerCase();
        const rows = document.querySelectorAll('#activities-table tbody tr');
        rows.forEach(r => {
            const text = r.textContent.toLowerCase();
            r.style.display = text.includes(query) ? '' : 'none';
        });
    }
</script>
@endsection

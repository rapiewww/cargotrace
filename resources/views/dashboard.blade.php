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
    <!-- Top Row: Active Shipments Per Mode Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Sea Mode Card -->
        <a href="{{ route('shipments.index', ['mode' => 'SEA']) }}" class="p-4 rounded-2xl theme-card border hover:border-blue-500/50 transition-all flex items-center justify-between group">
            <div class="space-y-1">
                <span class="text-xs font-semibold theme-text-secondary flex items-center gap-1.5">
                    <span>🚢</span> Ocean freight (Sea)
                </span>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ $activeModesCount['SEA'] ?? 0 }} <span class="text-xs font-normal theme-text-muted font-sans">active</span>
                </div>
                <span class="text-[10px] theme-text-muted block">Solid bezier maritime arcs</span>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/25 group-hover:scale-105 transition-transform">
                Sea &rarr;
            </span>
        </a>

        <!-- Air Mode Card -->
        <a href="{{ route('shipments.index', ['mode' => 'AIR']) }}" class="p-4 rounded-2xl theme-card border hover:border-sky-500/50 transition-all flex items-center justify-between group">
            <div class="space-y-1">
                <span class="text-xs font-semibold theme-text-secondary flex items-center gap-1.5">
                    <span>✈️</span> Air freight (Air)
                </span>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ $activeModesCount['AIR'] ?? 0 }} <span class="text-xs font-normal theme-text-muted font-sans">active</span>
                </div>
                <span class="text-[10px] theme-text-muted block">Dashed high-altitude flight curves</span>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-sky-500/15 text-sky-600 dark:text-sky-400 border border-sky-500/25 group-hover:scale-105 transition-transform">
                Air &rarr;
            </span>
        </a>

        <!-- Road Mode Card -->
        <a href="{{ route('shipments.index', ['mode' => 'ROAD']) }}" class="p-4 rounded-2xl theme-card border hover:border-emerald-500/50 transition-all flex items-center justify-between group">
            <div class="space-y-1">
                <span class="text-xs font-semibold theme-text-secondary flex items-center gap-1.5">
                    <span>🚚</span> Road freight (Road)
                </span>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ $activeModesCount['ROAD'] ?? 0 }} <span class="text-xs font-normal theme-text-muted font-sans">active</span>
                </div>
                <span class="text-[10px] theme-text-muted block">Direct overland haul lines</span>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold font-mono bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25 group-hover:scale-105 transition-transform">
                Road &rarr;
            </span>
        </a>
    </div>

    <!-- Row 2: Compact Role-Scoped KPI Row -->
    <section aria-label="Key shipment metrics">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- KPI 1: Total Shipments -->
            <a href="{{ route('shipments.index') }}" data-kpi="total" class="p-4 rounded-2xl theme-card border hover:border-blue-500/50 transition-colors flex flex-col justify-between gap-1.5 min-h-[44px] cursor-pointer">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium theme-text-secondary">Total shipments</span>
                </div>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ number_format($totalShipments) }}
                </div>
                <p class="text-[11px] theme-text-muted truncate">
                    {{ $totalShipments > 0 ? $activeShipmentsCount.' active across all modes' : 'No consignments recorded yet' }}
                </p>
            </a>

            <!-- KPI 2: In Transit -->
            <a href="{{ route('shipments.index', ['status' => 'IN_TRANSIT']) }}" data-kpi="in_transit" class="p-4 rounded-2xl theme-card border hover:border-blue-500/50 transition-colors flex flex-col justify-between gap-1.5 min-h-[44px] cursor-pointer">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium theme-text-secondary">In transit</span>
                </div>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ number_format($inTransit) }}
                </div>
                <p class="text-[11px] theme-text-muted truncate">
                    Shipments currently moving on route
                </p>
            </a>

            <!-- KPI 3: Delivered -->
            <a href="{{ route('shipments.index', ['status' => 'DELIVERED']) }}" data-kpi="delivered" class="p-4 rounded-2xl theme-card border hover:border-blue-500/50 transition-colors flex flex-col justify-between gap-1.5 min-h-[44px] cursor-pointer">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium theme-text-secondary">Delivered</span>
                </div>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ number_format($delivered) }}
                </div>
                <p class="text-[11px] theme-text-muted truncate">
                    {{ $delivered > 0 ? number_format($onTimeRate, 1).'% delivered on schedule' : 'Completed consignments' }}
                </p>
            </a>

            <!-- KPI 4: Delayed + Delay Rate -->
            <a href="{{ route('shipments.index', ['is_delayed' => '1']) }}" data-kpi="delayed" class="p-4 rounded-2xl theme-card border hover:border-amber-500/50 transition-colors flex flex-col justify-between gap-1.5 min-h-[44px] cursor-pointer">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium theme-text-secondary">Delayed</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold font-mono tabular-nums {{ $delayRate > 0 ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' }}">
                        {{ number_format($delayRate, 1) }}%
                    </span>
                </div>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ number_format($delayed) }}
                </div>
                <p class="text-[11px] theme-text-muted truncate">
                    Delay rate: {{ number_format($delayRate, 1) }}% of {{ $activeShipmentsCount }} active
                </p>
            </a>

            <!-- KPI 5: Open Exceptions + Exception Rate -->
            <a href="{{ route('shipments.index', ['has_open_exceptions' => '1']) }}" data-kpi="open_exceptions" class="p-4 rounded-2xl theme-card border hover:border-rose-500/50 transition-colors flex flex-col justify-between gap-1.5 min-h-[44px] cursor-pointer">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium theme-text-secondary">Open exceptions</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold font-mono tabular-nums {{ $exceptionRate > 0 ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/25' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' }}">
                        {{ number_format($exceptionRate, 1) }}%
                    </span>
                </div>
                <div class="text-2xl font-extrabold theme-heading font-mono tabular-nums">
                    {{ number_format($openExceptions) }}
                </div>
                <p class="text-[11px] theme-text-muted truncate">
                    Exception rate: {{ number_format($exceptionRate, 1) }}% of {{ $activeShipmentsCount }} active
                </p>
            </a>
        </div>
    </section>

    <!-- TOP SECTION: Global View (3D Earth Globe / 2D Map) + Main Statistics -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <!-- LEFT 9 COLS: "Global View" Canvas Container -->
        <div id="dashboard-map-shell" data-map-data-url="{{ route('dashboard.map-data') }}" class="xl:col-span-9 relative rounded-3xl theme-card border overflow-hidden flex flex-col justify-between min-h-[520px]">
            <!-- Top Controls Overlay: Title, Status, Delay Popover, Coordinates -->
            <div class="relative z-20 p-6 sm:p-8 flex flex-col sm:flex-row sm:items-start justify-between gap-6 pointer-events-none">
                <!-- Left Title & Compact Delay Popover (Globe is the single focal point) -->
                <div class="space-y-2 max-w-sm pointer-events-auto font-sans">
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-3xl sm:text-4xl font-extrabold theme-heading tracking-tight font-sans">
                                Global view
                            </h1>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-blue-500/10 text-cyan-600 dark:text-cyan-400 border border-blue-500/20 font-sans">
                                Overview
                            </span>
                        </div>
                        <p class="text-xs theme-text-secondary mt-1">Global route map &bull; Operator-recorded checkpoints</p>
                        <div class="flex items-center gap-3 mt-2">
                            <div class="flex items-center gap-1.5">
                                <span class="relative flex h-2 w-2">
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 font-sans">
                                    Verified milestones
                                </span>
                            </div>

                            <!-- "How delays are calculated" small popover -->
                            <details class="relative inline-block text-xs">
                                <summary class="cursor-pointer text-blue-600 dark:text-cyan-400 hover:underline font-medium inline-flex items-center gap-1 focus:outline-none">
                                    <span>How delays are calculated</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </summary>
                                <div class="absolute left-0 mt-2 w-72 p-3.5 rounded-2xl theme-card-elevated border theme-border shadow-2xl space-y-2.5 z-30 font-sans">
                                    <div class="flex items-center justify-between pb-1.5 border-b theme-border">
                                        <span class="font-bold theme-heading text-xs">Delay evaluation rules</span>
                                        <span class="text-[10px] theme-text-muted">Rule engine</span>
                                    </div>
                                    <p class="text-[11px] theme-text-secondary leading-relaxed">
                                        Variance evaluation comparing ETA against expected arrival milestones using database thresholds.
                                    </p>
                                    <div class="grid grid-cols-3 gap-1.5 text-center text-[10px] font-mono">
                                        <div class="p-1.5 rounded-xl bg-amber-500/10 border border-amber-500/25">
                                            <span class="block font-bold text-amber-600 dark:text-amber-400">Low</span>
                                            <span class="theme-text-muted">&le; {{ (int) $delayLowHours }}h</span>
                                        </div>
                                        <div class="p-1.5 rounded-xl bg-orange-500/10 border border-orange-500/25">
                                            <span class="block font-bold text-orange-600 dark:text-orange-400">Medium</span>
                                            <span class="theme-text-muted">&le; {{ (int) $delayMediumHours }}h</span>
                                        </div>
                                        <div class="p-1.5 rounded-xl bg-rose-500/10 border border-rose-500/25">
                                            <span class="block font-bold text-rose-600 dark:text-rose-400">High</span>
                                            <span class="theme-text-muted">&gt; {{ (int) $delayMediumHours }}h</span>
                                        </div>
                                    </div>
                                    <div class="pt-1">
                                        <a href="{{ route('shipments.index', ['is_delayed' => 1]) }}" class="w-full inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-[11px] shadow-sm transition">
                                            Filter delayed shipments ({{ $delayed ?? 0 }}) &rarr;
                                        </a>
                                    </div>
                                </div>
                            </details>
                        </div>

                        <!-- Camera Center Coordinates Display: Relabeled honestly as Map center (lat, lng) -->
                        <div class="text-[11px] theme-text-muted pt-2 font-sans">
                            Map center (lat, lng): <span id="map-coord-lat" class="font-mono text-cyan-600 dark:text-cyan-400 font-semibold">18.000000</span>, <span id="map-coord-lng" class="font-mono text-cyan-600 dark:text-cyan-400 font-semibold">110.000000</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3D Earth Globe Canvas (Default) -->
            <div id="globe-3d-canvas" class="absolute inset-0 w-full h-full z-10 cursor-grab active:cursor-grabbing"></div>

            <!-- 2D Leaflet Map Canvas (Alternative) -->
            <div id="globe-2d-canvas" data-map-data-url="{{ route('dashboard.map-data') }}" role="region" aria-label="2D Leaflet shipment route map" class="absolute inset-0 w-full h-full z-10 hidden theme-page"></div>

            <!-- Empty / Loading / Error States Inside Map -->
            <div id="map-empty-state" role="status" aria-live="polite" class="{{ count($mapData['shipments'] ?? []) === 0 ? '' : 'hidden ' }}absolute inset-0 z-20 flex items-center justify-center p-6 pointer-events-none">
                <div class="px-5 py-4 rounded-2xl theme-card-elevated border theme-border shadow-xl backdrop-blur-md text-center max-w-xs space-y-1 pointer-events-auto">
                    <p class="text-sm font-semibold theme-heading">No routes match this filter</p>
                    <p class="text-xs theme-text-secondary">Select another mode or clear the active filter.</p>
                </div>
            </div>

            <div id="map-loading-state" aria-live="polite" class="hidden absolute bottom-20 left-6 z-20 pointer-events-none">
                <div class="px-3 py-1.5 rounded-xl theme-card-elevated border theme-border shadow-lg backdrop-blur-md text-[11px] font-medium theme-text-secondary">
                    Updating routes...
                </div>
            </div>

            <div id="map-error-state" role="alert" class="hidden absolute bottom-20 right-6 z-20 pointer-events-none">
                <div class="px-3 py-1.5 rounded-xl theme-card-elevated border border-rose-500/40 shadow-lg backdrop-blur-md text-[11px] font-medium text-rose-500">
                    Could not refresh live map routes. Showing cached routes.
                </div>
            </div>

            <!-- Map Mode & Filter Tabs Overlay (Top Right of Map) -->
            <div class="absolute top-6 right-6 z-20 pointer-events-auto flex flex-col items-end gap-2 max-w-[calc(100%-3rem)]">
                <!-- Mode & Filter Tabs -->
                <div class="flex items-center p-1 rounded-2xl theme-card-elevated border theme-border shadow-xl backdrop-blur-md overflow-x-auto max-w-full text-xs">
                    <button
                        type="button"
                        onclick="setMapFilter('ALL')"
                        id="tab-filter-all"
                        aria-pressed="true"
                        class="px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab bg-blue-600 text-white cursor-pointer"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        onclick="setMapFilter('SEA')"
                        id="tab-filter-sea"
                        aria-pressed="false"
                        class="px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab theme-text-muted hover:theme-heading cursor-pointer"
                    >
                        🚢 Sea
                    </button>
                    <button
                        type="button"
                        onclick="setMapFilter('AIR')"
                        id="tab-filter-air"
                        aria-pressed="false"
                        class="px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab theme-text-muted hover:theme-heading cursor-pointer"
                    >
                        ✈️ Air
                    </button>
                    <button
                        type="button"
                        onclick="setMapFilter('ROAD')"
                        id="tab-filter-road"
                        aria-pressed="false"
                        class="px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab theme-text-muted hover:theme-heading cursor-pointer"
                    >
                        🚚 Road
                    </button>
                    <span class="w-px h-3.5 bg-slate-300 dark:bg-slate-700 mx-1"></span>
                    <button
                        type="button"
                        onclick="setMapFilter('DELAYED')"
                        id="tab-filter-delayed"
                        aria-pressed="false"
                        class="px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab theme-text-muted hover:text-amber-500 cursor-pointer"
                    >
                        Delayed
                    </button>
                    <button
                        type="button"
                        onclick="setMapFilter('EXCEPTIONS')"
                        id="tab-filter-exceptions"
                        aria-pressed="false"
                        class="px-2.5 py-1 rounded-xl font-semibold transition map-filter-tab theme-text-muted hover:text-rose-500 cursor-pointer"
                    >
                        Exceptions
                    </button>
                </div>

                <!-- Legend Pill (Mode geometry + Delay severity + Markers) -->
                <div class="px-3 py-1.5 rounded-xl theme-card-elevated border theme-border shadow-lg backdrop-blur-md text-[10px] flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-3 h-0.5 bg-blue-500 rounded inline-block"></span> 🚢 Sea (Solid arc)
                    </span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-3 h-0.5 border-b-2 border-dashed border-sky-400 inline-block"></span> ✈️ Air (Dashed high arc)
                    </span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-3 h-0.5 bg-emerald-500 rounded inline-block"></span> 🚚 Road (Straight line)
                    </span>
                    <span class="w-px h-3 bg-slate-300 dark:bg-slate-700 hidden sm:inline-block"></span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> On time
                    </span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span> Low
                    </span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-2 h-2 rounded-full bg-orange-500 inline-block"></span> Medium
                    </span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span> High / Exception
                    </span>
                    <span class="w-px h-3 bg-slate-300 dark:bg-slate-700 hidden sm:inline-block"></span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-2 h-2 rounded-full bg-blue-600 inline-block"></span> Origin
                    </span>
                    <span class="flex items-center gap-1 theme-text-secondary">
                        <span class="w-2 h-2 rounded-full bg-cyan-600 inline-block"></span> Destination
                    </span>
                </div>
            </div>

            <!-- Bottom Mode Switcher Pill [ 3D ] | [ 2D ] & Zoom Buttons -->
            <div class="relative z-20 p-6 flex items-center justify-between pointer-events-none mt-auto">
                <div class="flex items-center gap-2 pointer-events-auto">
                    <!-- 3D / 2D Switcher Pill -->
                    <div class="flex items-center p-1 rounded-full theme-card-elevated border theme-border shadow-xl backdrop-blur-md">
                        <button
                            id="btn-mode-3d"
                            type="button"
                            aria-pressed="true"
                            onclick="window.CargoTrace && window.CargoTrace.setMapMode ? window.CargoTrace.setMapMode('3D') : null"
                            class="px-3.5 py-1 rounded-full text-xs font-semibold bg-blue-600 text-white shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
                        >
                            <span>🌐 3D</span>
                        </button>
                        <button
                            id="btn-mode-2d"
                            type="button"
                            aria-pressed="false"
                            onclick="window.CargoTrace && window.CargoTrace.setMapMode ? window.CargoTrace.setMapMode('2D') : null"
                            class="px-3.5 py-1 rounded-full text-xs font-semibold theme-text-muted hover:text-cyan-400 transition-all flex items-center gap-1.5 cursor-pointer"
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
                        aria-label="Zoom out"
                        onclick="window.CargoTrace && window.CargoTrace.zoomMap ? window.CargoTrace.zoomMap('out') : null"
                        class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-white flex items-center justify-center font-bold text-xs hover:bg-slate-300 dark:hover:bg-slate-700 transition cursor-pointer"
                        title="Zoom out"
                    >
                        &minus;
                    </button>
                    <button
                        type="button"
                        aria-label="Zoom in"
                        onclick="window.CargoTrace && window.CargoTrace.zoomMap ? window.CargoTrace.zoomMap('in') : null"
                        class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs hover:bg-blue-500 shadow-md transition cursor-pointer"
                        title="Zoom in"
                    >
                        &#43;
                    </button>
                </div>
            </div>
        </div>

        <!-- RIGHT 3 COLS: "Main Statistics" Stack (3 Cards) -->
        <div class="xl:col-span-3 flex flex-col justify-between gap-4 font-sans">
            <h2 class="text-sm font-bold theme-heading tracking-tight flex items-center justify-between font-sans">
                <span>Main statistics</span>
                <span class="text-[11px] theme-text-muted font-normal font-sans">Database aggregates</span>
            </h2>

            <!-- Card 1: Active Shipments & On-Time Rate Comparison -->
            <div class="p-5 rounded-3xl theme-card border flex flex-col justify-between flex-1 transition-colors duration-200">
                <div class="flex items-center justify-between text-xs theme-text-secondary font-medium">
                    <span>Active shipments</span>
                    @if($onTimeComparison)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                            {{ $onTimeComparison }}
                        </span>
                    @endif
                </div>
                <div class="text-3xl font-extrabold theme-heading tracking-tight my-2 tabular-nums">
                    {{ count($activeShipments) }} <span class="text-xs font-normal theme-text-muted font-sans">active</span>
                </div>
                <!-- Mini Sparkline SVG: 14-Day Shipment Creation (Real Database Data) -->
                <div class="h-10 w-full mt-2" title="Shipments created per day (last 14 days)">
                    @if($spark1Flat)
                        <div class="h-full w-full flex items-center justify-center text-[11px] theme-text-muted italic border border-dashed theme-border rounded-lg">
                            Not enough data yet
                        </div>
                    @else
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
                    @endif
                </div>
            </div>

            <!-- Card 2: Total Cargo Valuation & Labeled Volume -->
            <div class="p-5 rounded-3xl theme-card border flex flex-col justify-between flex-1 transition-colors duration-200">
                <div class="flex items-center justify-between text-xs theme-text-secondary font-medium">
                    <span title="Declared value converted to USD at the latest cached rate" class="cursor-help border-b border-dotted theme-border">
                        Total cargo valuation
                    </span>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/25">
                        Volume {{ number_format($totalVolumeCbm, 0) }} CBM
                    </span>
                </div>
                <div class="text-3xl font-extrabold theme-heading tracking-tight my-2 tabular-nums">
                    ${{ number_format($totalValueUsd / 1000, 0) }}k <span class="text-xs font-normal theme-text-muted font-sans">USD</span>
                </div>
                <!-- Mini Sparkline SVG: 8-Week On-Time Rate (Real Database Data) -->
                <div class="h-10 w-full mt-2" title="Weekly on-time performance (last 8 weeks)">
                    @if($spark2Flat)
                        <div class="h-full w-full flex items-center justify-center text-[11px] theme-text-muted italic border border-dashed theme-border rounded-lg">
                            Not enough data yet
                        </div>
                    @else
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
                    @endif
                </div>
            </div>

            <!-- Card 3: Exception Rate (Real Database Metric) -->
            <div class="p-5 rounded-3xl theme-card border flex items-center justify-between flex-1 transition-colors duration-200">
                <div class="space-y-1">
                    <span class="text-xs theme-text-secondary font-medium block">Exception rate</span>
                    <div class="text-3xl font-extrabold theme-heading tabular-nums font-mono">
                        {{ $exceptionRate }}%
                    </div>
                    <span class="text-[10px] theme-text-muted block font-sans">
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

    <!-- Row 4: Shipments by Status & Shipments by Carrier (Single Card) -->
    <section aria-labelledby="dashboard-charts-heading" class="p-6 rounded-3xl theme-card border space-y-5 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b theme-border">
            <div>
                <h2 id="dashboard-charts-heading" class="text-lg font-bold theme-heading tracking-tight font-sans">
                    Shipment distribution
                </h2>
                <p class="text-xs theme-text-secondary mt-0.5">
                    {{ $user->role === 'customer' ? 'Your shipments grouped by lifecycle status and carrier' : 'All shipments grouped by lifecycle status and carrier' }}
                </p>
            </div>
            <a href="{{ route('analytics.index') }}" class="text-xs font-semibold text-blue-600 dark:text-cyan-400 hover:underline inline-flex items-center gap-1 min-h-[44px] self-start sm:self-auto">
                <span>Full analytics</span>
                <span>&rarr;</span>
            </a>
        </div>

        @if($totalShipments === 0)
            <div id="dashboard-charts-empty-state" class="py-10 px-4 rounded-2xl theme-card-elevated border border-dashed theme-border text-center space-y-2">
                <p class="text-sm font-semibold theme-heading">No shipments recorded yet</p>
                <p class="text-xs theme-text-secondary max-w-md mx-auto">
                    Status and carrier breakdowns appear automatically once consignments are added to the database.
                </p>
                @if(in_array($user->role, ['admin', 'operator'], true))
                    <div class="pt-2">
                        <a href="{{ route('shipments.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition min-h-[44px]">
                            <span>New shipment</span>
                        </a>
                    </div>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                <!-- Chart 1: Shipments by Status (Doughnut + Accessible Table Legend) -->
                <div class="lg:col-span-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold theme-heading font-sans">Shipments by status</h3>
                        <span class="text-xs font-mono theme-text-muted tabular-nums">{{ number_format($totalShipments) }} total</span>
                    </div>

                    <div class="h-52 w-full max-w-[240px] mx-auto">
                        <canvas id="shipmentsStatusChart" role="img" aria-label="Doughnut chart of shipments by lifecycle status"></canvas>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border-collapse" aria-label="Shipments by status legend">
                            <caption class="sr-only">Shipments by status breakdown</caption>
                            <thead>
                                <tr class="border-b theme-border theme-text-muted uppercase text-[10px] tracking-wider">
                                    <th scope="col" class="py-2 pr-2 text-left font-semibold">Status</th>
                                    <th scope="col" class="py-2 px-2 text-right font-semibold">Count</th>
                                    <th scope="col" class="py-2 pl-2 text-right font-semibold">Share</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y theme-border">
                                @foreach($statusBreakdown as $row)
                                    @php
                                        $dotClass = match($row['status']) {
                                            'CREATED' => 'bg-slate-400',
                                            'BOOKED' => 'bg-sky-400',
                                            'PICKED_UP' => 'bg-blue-600',
                                            'IN_TRANSIT' => 'bg-cyan-500',
                                            'DELIVERED' => 'bg-emerald-500',
                                            default => 'bg-slate-400',
                                        };
                                    @endphp
                                    <tr>
                                        <th scope="row" class="py-2 pr-2 font-medium text-left">
                                            <a href="{{ route('shipments.index', ['status' => $row['status']]) }}" class="inline-flex items-center gap-2 theme-text-primary hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">
                                                <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $dotClass }}" aria-hidden="true"></span>
                                                <span>{{ $row['label'] }}</span>
                                            </a>
                                        </th>
                                        <td class="py-2 px-2 text-right font-mono font-semibold theme-heading tabular-nums">{{ number_format($row['count']) }}</td>
                                        <td class="py-2 pl-2 text-right font-mono theme-text-muted tabular-nums">{{ number_format($row['share'], 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Chart 2: Shipments by Carrier (Horizontal Bar Top 6 + Others + Accessible Table Legend) -->
                <div class="lg:col-span-7 lg:border-l theme-border lg:pl-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold theme-heading font-sans">Shipments by carrier</h3>
                        <span class="text-xs theme-text-muted">Top 6 carriers plus others</span>
                    </div>

                    <div class="h-52 w-full">
                        <canvas id="carrierBarChart" role="img" aria-label="Horizontal bar chart of shipments by carrier"></canvas>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border-collapse" aria-label="Shipments by carrier legend">
                            <caption class="sr-only">Shipments by carrier breakdown</caption>
                            <thead>
                                <tr class="border-b theme-border theme-text-muted uppercase text-[10px] tracking-wider">
                                    <th scope="col" class="py-2 pr-2 text-left font-semibold">Carrier</th>
                                    <th scope="col" class="py-2 px-2 text-right font-semibold">Count</th>
                                    <th scope="col" class="py-2 pl-2 text-right font-semibold">Share</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y theme-border">
                                @foreach($carrierStats as $c)
                                    <tr>
                                        <th scope="row" class="py-2 pr-2 font-medium text-left">
                                            @if(empty($c['is_others']) && !empty($c['id']))
                                                <a href="{{ route('shipments.index', ['carrier_id' => $c['id']]) }}" class="inline-flex items-center gap-2 theme-text-primary hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">
                                                    <span class="w-2.5 h-2.5 rounded-sm shrink-0 bg-blue-600 dark:bg-sky-400" aria-hidden="true"></span>
                                                    <span>{{ $c['name'] }}</span>
                                                    <span class="text-[10px] font-mono theme-text-muted">({{ $c['code'] }})</span>
                                                </a>
                                            @else
                                                <span class="inline-flex items-center gap-2 theme-text-secondary">
                                                    <span class="w-2.5 h-2.5 rounded-sm shrink-0 bg-slate-500 dark:bg-slate-400" aria-hidden="true"></span>
                                                    <span>Others ({{ $c['carrier_count'] ?? 0 }} carriers)</span>
                                                </span>
                                            @endif
                                        </th>
                                        <td class="py-2 px-2 text-right font-mono font-semibold theme-heading tabular-nums">{{ number_format($c['count']) }}</td>
                                        <td class="py-2 pl-2 text-right font-mono theme-text-muted tabular-nums">{{ number_format($c['share'], 1) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </section>

    <!-- Row 5: Freight Activities Table (Full Width) -->
    <div class="p-6 rounded-3xl theme-card border space-y-4 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b theme-border">
            <div>
                <h2 class="text-lg font-bold theme-heading tracking-tight font-sans">
                    Freight activities
                </h2>
                <p class="text-xs theme-text-secondary mt-0.5">Click any consignment row to focus camera on the 3D globe or 2D map</p>
            </div>

            <!-- Search Input in Header -->
            <div class="relative w-full sm:w-64">
                <input
                    type="text"
                    id="activities-search-input"
                    aria-label="Search freight activities"
                    placeholder="Search tracking no., reference, hub..."
                    onkeyup="filterActivitiesTable()"
                    class="w-full pl-9 pr-3 py-2 rounded-xl theme-input border text-xs focus:outline-none focus:border-cyan-500"
                >
                <svg class="w-4 h-4 theme-text-muted absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Table Rows with Visual Route and FlyTo Click -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[700px] text-left text-xs border-collapse" id="activities-table">
                <thead>
                    <tr class="theme-text-muted uppercase text-[10px] font-semibold tracking-wider border-b theme-border">
                        <th class="py-3 px-3">Cargo ID & Mode</th>
                        <th class="py-3 px-4">Freight Route & Corridor</th>
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
                            $sMode = strtoupper($s->transport_mode ?? 'SEA');
                            $modeIcon = $sMode === 'AIR' ? '✈️' : ($sMode === 'ROAD' ? '🚚' : '🚢');
                        @endphp
                        <tr
                            class="activity-row cursor-pointer hover:bg-blue-600/10 transition-colors duration-150 border-l-2 border-transparent"
                            tabindex="0"
                            data-tracking="{{ $s->tracking_number }}"
                            onclick="if (event.target.closest('a, button')) return; window.CargoTrace && window.CargoTrace.focusMaritimeEntity ? window.CargoTrace.focusMaritimeEntity({{ $curr->latitude ?? $orig->latitude ?? 0 }}, {{ $curr->longitude ?? $orig->longitude ?? 0 }}, '{{ $s->tracking_number }}') : null"
                            onkeydown="if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('a, button')) { event.preventDefault(); window.CargoTrace && window.CargoTrace.focusMaritimeEntity ? window.CargoTrace.focusMaritimeEntity({{ $curr->latitude ?? $orig->latitude ?? 0 }}, {{ $curr->longitude ?? $orig->longitude ?? 0 }}, '{{ $s->tracking_number }}') : null; }"
                        >
                            <!-- Cargo ID & Carrier Icon -->
                            <td class="py-3.5 px-3">
                                <div class="flex items-center space-x-3">
                                    <!-- Carrier/Mode Icon Capsule -->
                                    <div class="w-8 h-8 rounded-xl {{ $s->is_delayed ? 'bg-amber-500/15 text-amber-500' : ($sMode === 'AIR' ? 'bg-sky-500/15 text-sky-500' : ($sMode === 'ROAD' ? 'bg-emerald-500/15 text-emerald-500' : 'bg-blue-500/15 text-blue-500')) }} flex items-center justify-center font-bold text-xs shrink-0" title="Mode: {{ $sMode }}">
                                        {{ $modeIcon }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <a href="{{ route('shipments.show', $s) }}" class="font-mono font-bold theme-heading hover:text-cyan-500 block">
                                                {{ $s->tracking_number }}
                                            </a>
                                            <x-mode-badge :mode="$sMode" size="xs" />
                                        </div>
                                        <span class="text-[11px] theme-text-muted block font-sans">
                                            {{ $s->carrier->name ?? 'Freight Carrier' }}
                                            &bull; <span class="font-mono text-cyan-600 dark:text-cyan-400 font-semibold">{{ $s->primary_reference }}</span>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Visual Route: ORIGIN ───[Icon]───> DESTINATION -->
                            <td class="py-3.5 px-4">
                                <div class="space-y-1 max-w-xs">
                                    <div class="flex items-center justify-between text-[11px] font-mono">
                                        <span class="font-bold theme-text-primary">{{ $orig->display_code ?? 'ORI' }}</span>
                                        <span class="text-[10px] theme-text-muted font-mono" title="Great-circle distance between locations">
                                            {{ $s->status === 'DELIVERED' ? 'Arrived' : ($s->great_circle_distance_nm ? number_format($s->great_circle_distance_nm) . ' nm' : ($sMode === 'ROAD' ? 'Road Leg' : 'Direct Corridor')) }}
                                        </span>
                                        <span class="font-bold theme-text-primary">{{ $dest->display_code ?? 'DST' }}</span>
                                    </div>
                                    <!-- Visual Progress Line -->
                                    <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500 {{ $hasExceptions ? 'bg-rose-500' : ($s->is_delayed ? 'bg-amber-500' : ($sMode === 'AIR' ? 'bg-sky-400' : ($sMode === 'ROAD' ? 'bg-emerald-400' : 'bg-cyan-500'))) }}"
                                            style="width: {{ $s->status === 'DELIVERED' ? '100%' : ($s->status === 'IN_TRANSIT' ? '65%' : '15%') }}"
                                        ></div>
                                    </div>
                                    <div class="text-[10px] theme-text-muted truncate font-sans">
                                        {{ $orig->short_name ?? 'Origin' }} to {{ $dest->short_name ?? 'Destination' }}
                                    </div>
                                </div>
                            </td>

                            <!-- Arrival Date -->
                            <td class="py-3.5 px-3 font-mono text-xs theme-text-secondary whitespace-nowrap">
                                {{ $s->estimated_arrival_at ? $s->estimated_arrival_at->format('M d, Y') : 'Confirmed' }}
                            </td>

                            <!-- Status Pill -->
                            <td class="py-3.5 px-3">
                                @php
                                    $ftEval = ($s->status !== 'DELIVERED' && $s->actual_arrival_at) ? app(\App\Services\FreeTimeService::class)->calculate($s) : null;
                                @endphp
                                @if($ftEval && $ftEval['is_over_limit'])
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase font-mono bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30" title="{{ $ftEval['badge_label'] }}">
                                        Over free time (+{{ $ftEval['days_over'] }}d)
                                    </span>
                                @elseif($hasExceptions)
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
                                <a href="{{ route('shipments.show', $s) }}" class="p-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 theme-text-muted hover:theme-heading inline-flex items-center justify-center min-w-[44px] min-h-[44px]" aria-label="View shipment {{ $s->tracking_number }}" title="Inspect consignment">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center theme-text-muted">No freight activities found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Row 6: Lower Secondary Context (Free-Time Attention, Weather, Currency, News) -->
    @if(isset($overFreeTimeShipments) && $overFreeTimeShipments->isNotEmpty())
        <!-- Attention List: Shipments Exceeding Terminal Free-Time Dwell Limit -->
        <div class="p-5 rounded-3xl bg-rose-500/10 border border-rose-500/25 space-y-3 font-sans transition-colors duration-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-rose-500/20">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                    <h2 class="text-sm font-bold theme-heading tracking-tight font-sans">
                        Terminal dwell attention &bull; {{ $overFreeTimeShipments->count() }} {{ $overFreeTimeShipments->count() === 1 ? 'consignment exceeds' : 'consignments exceed' }} free-time limit
                    </h2>
                </div>
                <span class="text-[11px] font-mono text-rose-600 dark:text-rose-400">
                    Standard free time: {{ (int) (\App\Models\Setting::get('free_days_after_arrival') ?? 5) }} days
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($overFreeTimeShipments as $ofs)
                    @php
                        $eval = $ofs->free_time_eval ?? app(\App\Services\FreeTimeService::class)->calculate($ofs);
                        $containers = $ofs->items->map(fn($it) => $it->formatted_container_number)->filter()->unique()->values();
                    @endphp
                    <div class="p-3.5 rounded-2xl theme-card border theme-border flex flex-col justify-between space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <a href="{{ route('shipments.show', $ofs) }}" class="font-mono font-bold text-xs theme-heading hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">
                                    {{ $ofs->tracking_number }}
                                </a>
                                <span class="text-[11px] theme-text-muted block font-sans">
                                    {{ $ofs->carrier->name ?? 'Carrier' }} &bull; {{ $ofs->originLocation->short_name ?? 'Origin' }} to {{ $ofs->destinationLocation->short_name ?? 'Destination' }} ({{ $ofs->originLocation->display_code ?? 'ORI' }} &rarr; {{ $ofs->destinationLocation->display_code ?? 'DST' }})
                                </span>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30 whitespace-nowrap">
                                +{{ $eval['days_over'] }}d over limit
                            </span>
                        </div>

                        @if($containers->isNotEmpty())
                            <div class="text-[11px] font-mono theme-text-secondary">
                                <span class="theme-text-muted">Container:</span> {{ $containers->implode(', ') }}
                            </div>
                        @endif

                        <div class="flex items-center justify-between pt-1 border-t theme-border text-[10px] font-mono theme-text-muted">
                            <span>Arrived {{ $eval['arrival_at']?->format('M d, Y') }}</span>
                            <a href="{{ route('shipments.show', $ofs) }}" class="text-blue-600 dark:text-cyan-400 hover:underline font-sans">
                                Details &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Secondary Utility Strip: Port Weather, Currency Converter, Maritime News -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start font-sans">
        <!-- 1. Port Weather Widget -->
        @php
            $selectedPort = $weatherPorts[0] ?? [
                'city' => 'Singapore',
                'code' => 'SGSIN',
                'code_type' => 'UN/LOCODE',
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
                <span class="text-xs font-semibold theme-heading font-sans">Port weather</span>
                <div class="flex items-center gap-1.5">
                    <x-data-source-badge :source="$selectedPort['data_source'] ?? 'live'" />
                    @if(isset($selectedPort['weather']['timestamp']) && $selectedPort['weather']['timestamp'])
                        <span class="text-[10px] theme-text-muted font-sans">
                            Updated {{ \Carbon\Carbon::parse($selectedPort['weather']['timestamp'])->format('H:i') }}
                        </span>
                    @endif
                    <a href="{{ route('weather.index') }}" class="text-[10px] font-semibold px-2 py-0.5 rounded theme-card-elevated border theme-border hover:border-blue-500 theme-heading font-sans transition inline-flex items-center gap-1" title="Open full weather hub">
                        <span>Hub</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-extrabold theme-heading font-sans">{{ $selectedPort['city'] ?? 'Singapore' }}</h3>
                        <span class="text-[11px] theme-text-muted font-mono" title="{{ $selectedPort['code_type'] ?? 'UN/LOCODE' }}: {{ $selectedPort['code'] ?? $selectedPort['unlocode'] ?? 'SGSIN' }}">{{ $selectedPort['code'] ?? $selectedPort['unlocode'] ?? 'SGSIN' }} &bull; {{ $selectedPort['code_type'] ?? 'UN/LOCODE' }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-3xl font-black theme-heading tabular-nums font-mono">
                            {{ $selectedPort['weather']['temperature_c'] ?? '31.5' }}°C
                        </span>
                        <span class="text-xs font-semibold theme-text-secondary block font-sans">
                            {{ $selectedPort['weather']['condition'] ?? 'Clear & Sunny' }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs theme-text-secondary pt-3 border-t theme-border font-sans">
                    <div>Wind speed: <b class="theme-heading font-mono">{{ $selectedPort['weather']['wind_kph'] ?? '14.5' }} km/h</b></div>
                    <div>Relative humidity: <b class="theme-heading font-mono">{{ $selectedPort['weather']['humidity'] ?? '68' }}%</b></div>
                </div>
            </div>
        </div>

        <!-- 2. Two-Way Global Currency Converter Widget -->
        <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
            <div class="flex items-center justify-between pb-2 border-b theme-border">
                <span class="text-xs font-semibold theme-heading font-sans">Currency converter</span>
                <div class="flex items-center gap-1.5">
                    <x-data-source-badge :source="$currencyData['data_source'] ?? 'live'" />
                    @if(isset($currencyData['time_last_update_utc']) && $currencyData['time_last_update_utc'])
                        <span class="text-[10px] theme-text-muted font-sans">
                            Updated {{ \Carbon\Carbon::parse($currencyData['time_last_update_utc'])->format('H:i') }}
                        </span>
                    @endif
                    <a href="{{ route('currency.index') }}" class="text-[10px] text-cyan-600 dark:text-cyan-400 hover:underline font-sans" title="Open full currency converter">
                        Full rates &rarr;
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-5 gap-2 items-center">
                <input
                    type="number"
                    id="two-way-amount"
                    aria-label="Currency amount"
                    value="1000"
                    oninput="calculateTwoWayCurrency()"
                    class="col-span-2 px-3 py-2 rounded-xl theme-input border text-xs tabular-nums focus:outline-none focus:border-cyan-500 font-mono min-h-[44px]"
                >
                <select
                    id="two-way-from"
                    aria-label="From currency"
                    onchange="calculateTwoWayCurrency()"
                    class="col-span-1 px-1 py-2 rounded-xl theme-input border text-xs font-mono focus:outline-none min-h-[44px]"
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
                    aria-label="Swap currencies"
                    onclick="swapCurrencies()"
                    class="col-span-1 p-2 rounded-xl bg-blue-600/15 hover:bg-blue-600 text-blue-600 dark:text-cyan-400 hover:text-white transition flex items-center justify-center font-bold min-h-[44px] cursor-pointer"
                    title="Swap currencies"
                >
                    &rlarr;
                </button>

                <select
                    id="two-way-to"
                    aria-label="To currency"
                    onchange="calculateTwoWayCurrency()"
                    class="col-span-1 px-1 py-2 rounded-xl theme-input border text-xs font-mono focus:outline-none min-h-[44px]"
                >
                    <option value="IDR" selected>IDR</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="CNY">CNY</option>
                    <option value="SGD">SGD</option>
                    <option value="AUD">AUD</option>
                </select>
            </div>

            <div class="p-3 rounded-2xl theme-card-elevated border theme-border flex items-center justify-between font-sans">
                <span class="text-xs theme-text-secondary">Converted amount:</span>
                <span id="two-way-result" class="text-sm font-bold font-mono text-cyan-600 dark:text-cyan-400">
                    Rp 15,873,000 IDR
                </span>
            </div>
        </div>

        <!-- 3. Live Maritime News Feed -->
        <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200 font-sans">
            <div class="flex items-center justify-between pb-2 border-b theme-border">
                <span class="text-xs font-semibold theme-heading font-sans">Maritime news wire</span>
                <div class="flex items-center gap-1.5">
                    <x-data-source-badge :source="$news[0]['data_source'] ?? 'live'" />
                    @if(isset($news[0]['published_at']) && $news[0]['published_at'])
                        <span class="text-[10px] theme-text-muted font-sans">
                            Updated {{ \Carbon\Carbon::parse($news[0]['published_at'])->format('H:i') }}
                        </span>
                    @endif
                    <a href="{{ route('news.index') }}" class="text-[10px] text-cyan-600 dark:text-cyan-400 hover:underline font-sans" title="Open all maritime news">
                        All news &rarr;
                    </a>
                </div>
            </div>
            <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                @forelse(array_slice($news, 0, 4) as $n)
                    <div class="p-2.5 rounded-xl theme-card-elevated border theme-border flex items-center gap-3 group">
                        <img
                            src="{{ $n['thumbnail'] ?? asset('images/news-placeholder.svg') }}"
                            alt=""
                            width="80"
                            height="64"
                            class="w-16 h-14 sm:w-20 sm:h-16 rounded-lg object-cover flex-shrink-0 bg-slate-800 border border-white/5 group-hover:opacity-90 transition"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='{{ asset('images/news-placeholder.svg') }}';"
                        >
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center justify-between gap-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <span class="text-[10px] font-semibold text-cyan-600 dark:text-cyan-400 font-sans truncate max-w-[110px]" title="{{ $n['source'] ?? 'Maritime Wire' }}">
                                        {{ $n['source'] ?? 'Maritime Wire' }}
                                    </span>
                                    @if(!empty($n['is_relevant']))
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-sans font-medium bg-blue-500/20 text-cyan-400 border border-blue-400/30">
                                            Related
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] theme-text-muted flex-shrink-0 font-sans">
                                    {{ \Carbon\Carbon::parse($n['published_at'])->diffForHumans(null, true) }}
                                </span>
                            </div>
                            <a href="{{ $n['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold theme-heading hover:text-cyan-500 transition line-clamp-2 block leading-snug" title="{{ $n['headline'] }}">
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

{{-- Global Payload for window.__CARGOTRACE__ --}}
<script>
    window.__CARGOTRACE__ = {
        maritimeGlobe: {
            globe3dId: 'globe-3d-canvas',
            map2dId: 'globe-2d-canvas',
            options: {
                dataUrl: '{{ route('dashboard.map-data') }}',
                initialData: @json($mapData),
                shipments: @json($mapData['shipments']),
                locations: @json($mapData['locations'])
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

    // Filter Maritime / Freight Activities Table by Search Input
    function filterActivitiesTable() {
        const query = (document.getElementById('activities-search-input').value || '').toLowerCase();
        const rows = document.querySelectorAll('#activities-table tbody tr');
        rows.forEach(r => {
            const text = r.textContent.toLowerCase();
            r.style.display = text.includes(query) ? '' : 'none';
        });
    }

    // Map Filter Tabs (All, Sea, Air, Road, Delayed, Exceptions)
    function setMapFilter(filterKey) {
        if (window.CargoTrace && window.CargoTrace.setMapFilter) {
            return window.CargoTrace.setMapFilter(filterKey);
        }
        return null;
    }
</script>
@endsection

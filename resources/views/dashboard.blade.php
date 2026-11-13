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

    <!-- BOTTOM SECTION: Maritime Activities Table (Left) + Utility Panel (Right) -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- LEFT 8 COLS: "Maritime Activities" Table with Vessel Icon, Visual Route & FlyTo -->
        <div class="xl:col-span-8 space-y-6">
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
                                            {{ $ofs->carrier->name ?? 'Carrier' }} &bull; {{ $ofs->originLocation->unlocode ?? 'ORI' }} &rarr; {{ $ofs->destinationLocation->unlocode ?? 'DST' }}
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

            <div class="p-6 rounded-3xl theme-card border space-y-4 transition-colors duration-200">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b theme-border">
                <div>
                    <h2 class="text-lg font-bold theme-heading tracking-tight font-sans">
                        Maritime activities
                    </h2>
                    <p class="text-xs theme-text-secondary mt-0.5">Click any vessel row to focus camera on the 3D globe or 2D map</p>
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
                                            <span class="text-[11px] theme-text-muted block font-sans">
                                                {{ $s->carrier->name ?? 'Maritime Line' }}
                                                @if($s->items->isNotEmpty() && ($cont = $s->items->first()->formatted_container_number))
                                                    &bull; <span class="font-mono text-cyan-600 dark:text-cyan-400 font-semibold">{{ $cont }}</span>
                                                @endif
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
                                    @php
                                        $ftEval = ($s->actual_arrival_at || $s->status === 'DELIVERED') ? app(\App\Services\FreeTimeService::class)->calculate($s) : null;
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
    </div>

        <!-- RIGHT 4 COLS: Weather & Currency Utility Panel (Global View Style) -->
        <div class="xl:col-span-4 space-y-6 font-sans">
            <!-- 1. Port Weather Widget -->
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
                            <span class="text-[11px] theme-text-muted font-mono" title="{{ $selectedPort['unlocode'] ?? 'SGSIN' }} Container Terminal">{{ $selectedPort['unlocode'] ?? 'SGSIN' }} container terminal</span>
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
                        value="1000"
                        oninput="calculateTwoWayCurrency()"
                        class="col-span-2 px-3 py-1.5 rounded-xl theme-input border text-xs tabular-nums focus:outline-none focus:border-cyan-500 font-mono"
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

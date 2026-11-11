@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- Header with Port Selector & Live Status -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b theme-border">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="text-[11px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-400 font-bold px-2 py-0.5 rounded-md bg-cyan-500/10 border border-cyan-500/20">
                    Open-Meteo Integration
                </span>
                <x-data-source-badge :source="$currentWeather['data_source'] ?? 'live'" />
            </div>
            <h1 class="text-2xl sm:text-3xl font-black theme-heading tracking-tight mt-1.5">
                Seaport Weather & Marine Telemetry
            </h1>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Atmospheric conditions, wind thresholds for quay crane operations, and marine forecast across container terminal hubs.
            </p>
        </div>

        <!-- Seaport Switcher Dropdown -->
        <div class="flex items-center gap-3">
            <label for="seaport-selector" class="text-xs theme-text-secondary font-medium hidden sm:inline">Select Seaport:</label>
            <div class="relative min-w-[240px]">
                <select
                    id="seaport-selector"
                    onchange="window.location.href = '?port=' + encodeURIComponent(this.value)"
                    class="w-full pl-3 pr-8 py-2 rounded-xl theme-card-elevated border theme-border text-xs font-semibold theme-heading focus:outline-none focus:border-cyan-500 appearance-none cursor-pointer"
                >
                    @foreach($portsList as $p)
                        <option value="{{ $p['id'] }}" {{ $p['id'] === $selectedPort['id'] ? 'selected' : '' }}>
                            {{ $p['name'] }} ({{ $p['unlocode'] }})
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Meteorological Observation Hero -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Primary Atmospheric Status Card (Global View Inspired) -->
        <div class="lg:col-span-2 p-6 sm:p-8 rounded-3xl theme-card border space-y-6 relative overflow-hidden transition-colors duration-200">
            <!-- Terminal Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b theme-border">
                <div>
                    <span class="text-[11px] font-mono text-cyan-600 dark:text-cyan-400 font-semibold tracking-wider uppercase block">
                        Terminal Location &bull; UN/LOCODE: {{ $selectedPort['unlocode'] }}
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black theme-heading tracking-tight mt-0.5">
                        {{ $selectedPort['name'] }}
                    </h2>
                    <span class="text-xs theme-text-secondary">
                        {{ $selectedPort['city'] }}, {{ $selectedPort['country'] }} &bull; Lat: {{ number_format($selectedPort['latitude'], 3) }}°, Lng: {{ number_format($selectedPort['longitude'], 3) }}°
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <span class="text-4xl sm:text-5xl font-black theme-heading tabular-nums">
                            {{ $currentWeather['temperature_c'] }}°C
                        </span>
                        <span class="text-xs font-semibold text-cyan-600 dark:text-cyan-400 block">
                            {{ $currentWeather['condition'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sun / Atmospheric Arc Visual & Live Telemetry -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                <div class="sm:col-span-2 relative h-32 flex items-center justify-center p-2 rounded-2xl theme-card-elevated border theme-border">
                    <svg class="w-full h-full max-h-28" viewBox="0 0 280 90">
                        <path d="M 20,80 Q 140,10 260,80" fill="none" stroke="#0284C7" stroke-width="2" stroke-dasharray="4,4" opacity="0.6"/>
                        <!-- Sun Marker at Apex -->
                        <circle cx="140" cy="30" r="12" fill="#F59E0B" />
                        <circle cx="140" cy="30" r="18" fill="none" stroke="#F59E0B" stroke-width="1.5" stroke-dasharray="2,2" opacity="0.8" />
                        <text x="140" y="70" text-anchor="middle" font-size="11" font-weight="700" fill="currentColor" class="theme-text-primary">
                            {{ round($currentWeather['temperature_c']) }}° Maritime Horizon
                        </text>
                    </svg>
                    <span class="absolute bottom-2 left-4 text-[10px] font-mono theme-text-muted">06:00 Dawn</span>
                    <span class="absolute bottom-2 right-4 text-[10px] font-mono theme-text-muted">18:30 Dusk</span>
                </div>

                <div class="p-4 rounded-2xl theme-card-elevated border theme-border space-y-2">
                    <div class="text-xs theme-text-secondary">Apparent Temp</div>
                    <div class="text-xl font-bold theme-heading font-mono">{{ $currentWeather['feels_like_c'] ?? $currentWeather['temperature_c'] }}°C</div>
                    <div class="text-[11px] theme-text-muted pt-1 border-t theme-border">
                        Sea-level Barometer: <b class="theme-text-primary font-mono">1013.2 hPa</b>
                    </div>
                </div>
            </div>

            <!-- Quay Crane & Marine Safety Guidance -->
            <div class="p-4 rounded-2xl bg-slate-900/40 dark:bg-slate-900/40 border border-slate-700/60 dark:border-slate-800 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300">
                        Ship-to-Shore (STS) Crane Advisory
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase border
                        {{ $craneStatus['color'] === 'emerald' ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : ($craneStatus['color'] === 'amber' ? 'bg-amber-500/15 text-amber-300 border-amber-500/30' : 'bg-rose-500/15 text-rose-300 border-rose-500/30') }}">
                        {{ $craneStatus['status'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    {{ $craneStatus['note'] }}
                </p>
            </div>
        </div>

        <!-- Right: Real-Time Operational Telemetry Cards -->
        <div class="space-y-4">
            <!-- Card 1: Wind & Beaufort -->
            <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Wind & Force Scale</span>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/20">
                        Beaufort {{ $beaufort['force'] }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-3xl font-black theme-heading font-mono tabular-nums">
                            {{ $currentWeather['wind_kph'] }}
                        </span>
                        <span class="text-xs theme-text-secondary ml-1">km/h</span>
                    </div>
                    <span class="text-xs font-semibold theme-text-primary text-right">
                        {{ $beaufort['desc'] }}
                    </span>
                </div>
                <!-- Progress bar against safety cutoff (45 km/h) -->
                @php
                    $windPct = min(100, round(($currentWeather['wind_kph'] / 45) * 100));
                @endphp
                <div class="space-y-1">
                    <div class="flex justify-between text-[10px] font-mono theme-text-muted">
                        <span>Gust Load</span>
                        <span>{{ $windPct }}% of 45 km/h limit</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-slate-800 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $windPct < 60 ? 'bg-emerald-500' : ($windPct < 85 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $windPct }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Marine Swell & Waterway -->
            <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Channel Sea Swell</span>
                    <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 font-semibold">Tidal Est.</span>
                </div>
                <div class="space-y-1">
                    <span class="text-base font-bold theme-heading block">
                        {{ $swellEstimate }}
                    </span>
                    <p class="text-xs theme-text-secondary">
                        Wave height suitable for feeder barge transshipment and deep-water container docking maneuvers.
                    </p>
                </div>
            </div>

            <!-- Card 3: Atmospheric Moisture -->
            <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Moisture & Visibility</span>
                    <span class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold">Normal</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-2.5 rounded-xl theme-card-elevated border theme-border">
                        <span class="text-[10px] theme-text-muted block">Relative Humidity</span>
                        <span class="text-lg font-bold font-mono theme-heading">{{ $currentWeather['humidity'] }}%</span>
                    </div>
                    <div class="p-2.5 rounded-xl theme-card-elevated border theme-border">
                        <span class="text-[10px] theme-text-muted block">Visual Horizon</span>
                        <span class="text-lg font-bold font-mono theme-heading">> 10 km</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 7-Day Maritime Forecast Strip -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold theme-heading">
                7-Day Seaport Forecast & Berth Planning
            </h3>
            <span class="text-xs theme-text-secondary font-mono">
                Source: {{ $forecast['source'] ?? 'Open-Meteo Weather API' }}
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            @foreach(($forecast['days'] ?? []) as $day)
                <div class="p-4 rounded-2xl theme-card border text-center space-y-2 hover:border-cyan-500/40 transition">
                    <span class="text-xs font-bold theme-heading block truncate">
                        {{ $day['day_name'] }}
                    </span>
                    <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 block">
                        {{ $day['condition'] }}
                    </span>

                    <div class="py-2 border-y theme-border my-1">
                        <span class="text-base font-extrabold theme-heading font-mono">
                            {{ round($day['temp_max']) }}°
                        </span>
                        <span class="text-xs theme-text-muted font-mono ml-1">
                            / {{ round($day['temp_min']) }}°
                        </span>
                    </div>

                    <div class="space-y-1 text-[11px] theme-text-secondary font-mono">
                        <div>Wind: <b>{{ round($day['wind_max']) }} km/h</b></div>
                        <div>Rain: <b>{{ $day['precip_prob'] }}%</b></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Quick Seaports Hub Switcher -->
    <div class="p-6 rounded-3xl theme-card border space-y-4 transition-colors duration-200">
        <div class="flex items-center justify-between pb-2 border-b theme-border">
            <span class="text-xs font-bold uppercase tracking-wider theme-heading">Global Container Hub Quick Switcher</span>
            <span class="text-xs theme-text-secondary">Click any terminal to inspect live marine weather</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @foreach($portsList->take(6) as $p)
                <a
                    href="?port={{ urlencode($p['id']) }}"
                    class="p-3 rounded-2xl border transition text-left flex flex-col justify-between space-y-2 {{ $p['id'] === $selectedPort['id'] ? 'bg-blue-600/15 border-blue-500 text-blue-600 dark:text-cyan-400 shadow-md' : 'theme-card-elevated theme-border hover:border-cyan-500/50' }}"
                >
                    <div>
                        <span class="text-[10px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-400 font-bold block">
                            {{ $p['unlocode'] }}
                        </span>
                        <span class="text-xs font-bold theme-heading block truncate">
                            {{ $p['name'] }}
                        </span>
                    </div>
                    <span class="text-[10px] theme-text-secondary block">
                        {{ $p['city'] }}, {{ $p['country'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection


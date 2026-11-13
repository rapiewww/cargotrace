@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- Header with Port Selector & Attribution -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b theme-border">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="text-[11px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-400 font-bold px-2 py-0.5 rounded-md bg-cyan-500/10 border border-cyan-500/20">
                    Open-Meteo Integration & Marine API
                </span>
                <x-data-source-badge :source="$currentWeather['data_source'] ?? 'live'" />
            </div>
            <h1 class="text-2xl sm:text-3xl font-black theme-heading tracking-tight mt-1.5">
                Seaport Weather & Marine Observations
            </h1>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Atmospheric conditions, wind thresholds for quay crane operations, and marine observations across container terminal hubs.
            </p>
        </div>

        <!-- Searchable Seaport Switcher -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3">
            <div class="relative w-full sm:w-80">
                <div class="flex items-center gap-2">
                    <label for="seaport-filter-input" class="sr-only">Search seaport</label>
                    <div class="relative w-full">
                        <input
                            type="text"
                            id="seaport-filter-input"
                            placeholder="Type to filter seaports..."
                            class="w-full pl-8 pr-3 py-2 rounded-xl theme-card-elevated border theme-border text-xs theme-heading placeholder:text-slate-400 focus:outline-none focus:border-cyan-500"
                        />
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                    </div>
                </div>

                <select
                    id="seaport-selector"
                    onchange="window.location.href = '?port=' + encodeURIComponent(this.value)"
                    class="mt-2 w-full pl-3 pr-8 py-2 rounded-xl theme-card-elevated border theme-border text-xs font-semibold theme-heading focus:outline-none focus:border-cyan-500 appearance-none cursor-pointer"
                >
                    @if($activeRoutePorts->isNotEmpty())
                        <optgroup label="Ports on Your Active Routes">
                            @foreach($activeRoutePorts as $p)
                                <option value="{{ $p->id }}" {{ (string)$p->id === (string)$selectedPort['id'] ? 'selected' : '' }}>
                                    ★ {{ $p->name }} ({{ $p->unlocode }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endif

                    <optgroup label="All Global Seaports">
                        @foreach($otherPorts as $p)
                            <option value="{{ $p->id }}" {{ (string)$p->id === (string)$selectedPort['id'] ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->unlocode }})
                            </option>
                        @endforeach
                    </optgroup>
                </select>
            </div>
        </div>
    </div>

    <!-- Main Meteorological Observation Hero -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Primary Atmospheric Status Card -->
        <div class="lg:col-span-2 p-6 sm:p-8 rounded-3xl theme-card border space-y-6 relative overflow-hidden transition-colors duration-200">
            <!-- Terminal Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b theme-border">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-mono text-cyan-600 dark:text-cyan-400 font-semibold tracking-wider uppercase">
                            Terminal Location &bull; UN/LOCODE: {{ $selectedPort['unlocode'] }}
                        </span>
                        @if($selectedPort['is_active_route'])
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                Active Route Corridor
                            </span>
                        @endif
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black theme-heading tracking-tight mt-1">
                        {{ $selectedPort['name'] }}
                    </h2>
                    <span class="text-xs theme-text-secondary">
                        {{ $selectedPort['city'] }}, {{ $selectedPort['country'] }} &bull; Coordinates: {{ number_format($selectedPort['latitude'], 4) }}°, {{ number_format($selectedPort['longitude'], 4) }}°
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <span class="text-4xl sm:text-5xl font-black theme-heading tabular-nums">
                            {{ $currentWeather['temperature_c'] }}°C
                        </span>
                        <span class="text-xs font-semibold text-cyan-600 dark:text-cyan-400 block mt-0.5">
                            {{ $currentWeather['condition'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sun / Horizon Arc & Observations -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                <div class="sm:col-span-2 relative h-32 flex items-center justify-center p-2 rounded-2xl theme-card-elevated border theme-border">
                    <svg class="w-full h-full max-h-28" viewBox="0 0 280 90">
                        <path d="M 20,80 Q 140,10 260,80" fill="none" stroke="#0284C7" stroke-width="2" stroke-dasharray="4,4" opacity="0.6"/>
                        <circle cx="140" cy="30" r="12" fill="#F59E0B" />
                        <circle cx="140" cy="30" r="18" fill="none" stroke="#F59E0B" stroke-width="1.5" stroke-dasharray="2,2" opacity="0.8" />
                        <text x="140" y="70" text-anchor="middle" font-size="11" font-weight="700" fill="currentColor" class="theme-text-primary">
                            {{ round($currentWeather['temperature_c']) }}° Sea-Level Temperature
                        </text>
                    </svg>
                    <span class="absolute bottom-2 left-4 text-[10px] font-mono theme-text-muted">Observation Time: {{ Carbon\Carbon::parse($currentWeather['recorded_at'])->format('H:i') }} UTC</span>
                    <span class="absolute bottom-2 right-4 text-[10px] font-mono theme-text-muted">Open-Meteo Sync</span>
                </div>

                <div class="p-4 rounded-2xl theme-card-elevated border theme-border space-y-2">
                    <div class="text-xs theme-text-secondary">Apparent Temp</div>
                    <div class="text-xl font-bold theme-heading font-mono">{{ $currentWeather['feels_like_c'] ?? $currentWeather['temperature_c'] }}°C</div>
                    <div class="text-[11px] theme-text-muted pt-1 border-t theme-border">
                        Relative Humidity: <b class="theme-text-primary font-mono">{{ $currentWeather['humidity'] }}%</b>
                    </div>
                </div>
            </div>

            <!-- Ship-to-Shore (STS) Quay Crane Advisory -->
            <div class="p-4 rounded-2xl theme-card-elevated border theme-border space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider theme-heading">
                            Ship-to-Shore (STS) Quay Crane Advisory
                        </span>
                        <span class="text-[10px] font-mono font-medium px-1.5 py-0.5 rounded bg-slate-500/10 text-slate-500 dark:text-slate-400">
                            {{ $craneStatus['basis'] }}
                        </span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase border
                        {{ $craneStatus['color'] === 'emerald' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : ($craneStatus['color'] === 'amber' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30' : 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border-rose-500/30') }}">
                        {{ $craneStatus['status'] }}
                    </span>
                </div>
                <p class="text-xs theme-text-secondary leading-relaxed">
                    {{ $craneStatus['note'] }}
                </p>
            </div>
        </div>

        <!-- Right: Operational Observations & Marine Wave Data -->
        <div class="space-y-4">
            <!-- Card 1: Wind Speed & Beaufort Scale (Derived from wind speed) -->
            <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <div class="space-y-0.5">
                        <span class="text-xs font-bold uppercase tracking-wider theme-heading block">Wind Speed</span>
                        <span class="text-[10px] theme-text-muted">{{ $beaufort['basis'] }}</span>
                    </div>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/20">
                        Beaufort Force {{ $beaufort['force'] }}
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
                <!-- Progress bar against crane suspension limit (40 km/h) -->
                @php
                    $craneLimit = (float) config('cargotrace.weather.crane_thresholds.suspended_kph', 40.0);
                    $windPct = min(100, round(($currentWeather['wind_kph'] / $craneLimit) * 100));
                @endphp
                <div class="space-y-1">
                    <div class="flex justify-between text-[10px] font-mono theme-text-muted">
                        <span>Crane Operational Load</span>
                        <span>{{ $windPct }}% of {{ $craneLimit }} km/h suspension limit</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $windPct < 70 ? 'bg-emerald-500' : ($windPct < 100 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $windPct }}%"></div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Real Marine Wave & Swell Data (Open-Meteo Marine API) -->
            <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Marine Wave & Swell</span>
                    <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 font-semibold">Open-Meteo Marine</span>
                </div>

                @if(!empty($marineWeather['has_marine_data']))
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-2xl font-black theme-heading font-mono tabular-nums">
                                    {{ number_format($marineWeather['wave_height'], 2) }}
                                </span>
                                <span class="text-xs theme-text-secondary ml-1">m wave height</span>
                            </div>
                            @if($marineWeather['wave_direction'] !== null)
                                <div class="text-right">
                                    <span class="text-xs font-mono font-bold theme-heading block">{{ $marineWeather['wave_direction'] }}°</span>
                                    <span class="text-[10px] theme-text-muted">Wave Direction</span>
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs pt-1 border-t theme-border">
                            <div class="p-2 rounded-xl theme-card-elevated border theme-border">
                                <span class="text-[10px] theme-text-muted block">Wave Period</span>
                                <span class="font-bold font-mono theme-heading">{{ $marineWeather['wave_period'] !== null ? $marineWeather['wave_period'] . ' s' : 'N/A' }}</span>
                            </div>
                            <div class="p-2 rounded-xl theme-card-elevated border theme-border">
                                <span class="text-[10px] theme-text-muted block">Swell Height</span>
                                <span class="font-bold font-mono theme-heading">{{ $marineWeather['swell_wave_height'] !== null ? number_format($marineWeather['swell_wave_height'], 2) . ' m' : 'N/A' }}</span>
                            </div>
                        </div>

                        @if(!empty($marineWeather['ocean_current_velocity']))
                            <div class="text-[11px] theme-text-secondary flex justify-between pt-1">
                                <span>Ocean Current:</span>
                                <span class="font-mono font-bold theme-heading">{{ $marineWeather['ocean_current_velocity'] }} km/h {{ $marineWeather['ocean_current_direction'] !== null ? '(' . $marineWeather['ocean_current_direction'] . '°)' : '' }}</span>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="py-4 text-center space-y-1">
                        <svg class="w-8 h-8 mx-auto text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs font-semibold theme-heading">No marine data for this location</p>
                        <p class="text-[11px] theme-text-secondary">Open-Meteo Marine API reports null wave metrics for this coastal coordinate or inland point.</p>
                    </div>
                @endif
            </div>

            <!-- Card 3: Visibility (Only shown if returned by Open-Meteo) -->
            @if(isset($currentWeather['visibility_km']) && $currentWeather['visibility_km'] !== null)
                <div class="p-5 rounded-3xl theme-card border space-y-3 transition-colors duration-200">
                    <div class="flex items-center justify-between pb-2 border-b theme-border">
                        <span class="text-xs font-bold uppercase tracking-wider theme-heading">Visual Horizon</span>
                        <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 font-semibold">Atmospheric</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-black theme-heading font-mono tabular-nums">
                                {{ $currentWeather['visibility_km'] }}
                            </span>
                            <span class="text-xs theme-text-secondary ml-1">km</span>
                        </div>
                        <span class="text-xs font-semibold theme-text-secondary">
                            {{ $currentWeather['visibility_km'] >= 10 ? 'Optimal Line-of-Sight' : 'Reduced Visibility' }}
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- 7-Day Seaport Forecast Strip -->
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-lg font-bold theme-heading">
                    7-Day Seaport Forecast & Berth Planning
                </h3>
                <p class="text-xs theme-text-secondary">
                    Derived from Open-Meteo global meteorological model.
                </p>
            </div>
            <div class="text-xs theme-text-muted font-mono">
                Attribution: <a href="https://open-meteo.com" target="_blank" rel="noopener noreferrer" class="underline hover:text-cyan-500">Open-Meteo (CC BY 4.0)</a>
            </div>
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

    <!-- Seaport Comparison Matrix -->
    <div class="p-6 rounded-3xl theme-card border space-y-4 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b theme-border">
            <div>
                <h3 class="text-base font-bold theme-heading">
                    Seaport Meteorological Comparison Matrix
                </h3>
                <p class="text-xs theme-text-secondary">
                    Side-by-side temperature, wind load, wave height, and STS crane advisory across key terminals.
                </p>
            </div>
            <span class="text-xs font-mono text-cyan-600 dark:text-cyan-400">
                {{ count($compareData) }} Ports Compared
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b theme-border text-[11px] font-mono uppercase tracking-wider theme-text-secondary">
                        <th class="py-3 px-4">Seaport Terminal</th>
                        <th class="py-3 px-4">UN/LOCODE</th>
                        <th class="py-3 px-4">Condition</th>
                        <th class="py-3 px-4">Temp</th>
                        <th class="py-3 px-4">Wind Speed</th>
                        <th class="py-3 px-4">Wave Height</th>
                        <th class="py-3 px-4">STS Crane Advisory</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y theme-border font-sans">
                    @foreach($compareData as $row)
                        <tr class="hover:bg-slate-500/5 transition">
                            <td class="py-3 px-4 font-bold theme-heading">
                                {{ $row['name'] }}
                                <div class="text-[10px] theme-text-secondary font-normal">{{ $row['city'] }}, {{ $row['country'] }}</div>
                            </td>
                            <td class="py-3 px-4 font-mono font-semibold text-cyan-600 dark:text-cyan-400">
                                {{ $row['unlocode'] }}
                            </td>
                            <td class="py-3 px-4 theme-text-secondary">
                                {{ $row['condition'] }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold theme-heading">
                                {{ $row['temp_c'] !== null ? $row['temp_c'] . '°C' : 'N/A' }}
                            </td>
                            <td class="py-3 px-4 font-mono">
                                <span class="font-bold theme-heading">{{ $row['wind_kph'] !== null ? $row['wind_kph'] . ' km/h' : 'N/A' }}</span>
                                <span class="text-[10px] theme-text-muted ml-1">(F{{ $row['beaufort_force'] }})</span>
                            </td>
                            <td class="py-3 px-4 font-mono">
                                @if($row['has_marine_data'] && $row['wave_height'] !== null)
                                    <span class="font-bold theme-heading">{{ number_format($row['wave_height'], 2) }} m</span>
                                @else
                                    <span class="text-[11px] theme-text-muted">No marine data</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-bold uppercase border
                                    {{ $row['crane_color'] === 'emerald' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : ($row['crane_color'] === 'amber' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30' : 'bg-rose-500/15 text-rose-600 dark:text-rose-300 border-rose-500/30') }}">
                                    {{ $row['crane_status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="?port={{ urlencode($row['id']) }}" class="px-2.5 py-1 rounded-lg theme-card-elevated border theme-border font-semibold text-[11px] hover:border-cyan-500 transition text-cyan-600 dark:text-cyan-400">
                                    Inspect
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const filterInput = document.getElementById('seaport-filter-input');
    const select = document.getElementById('seaport-selector');
    if (!filterInput || !select) return;

    filterInput.addEventListener('input', (e) => {
        const val = e.target.value.toLowerCase().trim();
        const options = select.querySelectorAll('option');
        options.forEach(opt => {
            const text = opt.textContent.toLowerCase();
            if (!val || text.includes(val)) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
    });
});
</script>
@endsection

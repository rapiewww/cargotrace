@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-8 max-w-7xl mx-auto">
    <!-- Top Header & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                Shipments Overview
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20">Live Telemetry</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Track global shipment status, transit variances, and external weather risks in real time.</p>
        </div>

        <div class="flex items-center space-x-3">
            <!-- Region Filter Pill -->
            <div class="relative">
                <select class="appearance-none pl-3.5 pr-8 py-2 rounded-xl bg-[#0B1728] border border-slate-800 text-xs text-slate-200 font-semibold focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    <option>Region: Global Operations</option>
                    <option>Trans-Pacific (Asia - North America)</option>
                    <option>Asia - Europe (via Suez)</option>
                    <option>Intra-Asia Freight Network</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-teal-400">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            @if(in_array(auth()->user()->role, ['admin', 'operator']))
                <a href="{{ route('shipments.create') }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-amber-500/20 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                    <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    New shipment
                </a>
            @endif
        </div>
    </div>

    <!-- Hero Canvas: Shipments Overview (Exact MSC Aurora Aesthetic) -->
    <div class="relative rounded-2xl overflow-hidden border border-white/10 shadow-2xl bg-gradient-to-b from-[#0B1728] to-[#050B14] min-h-[420px] lg:min-h-[460px]">
        <!-- High-res Container Ship Aerial Backdrop -->
        <img src="https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=2200&q=80" alt="Aerial container vessel in turquoise dark waters" class="absolute inset-0 w-full h-full object-cover opacity-35 mix-blend-luminosity filter contrast-125">
        <div class="absolute inset-0 bg-gradient-to-r from-[#050B14]/95 via-[#050B14]/70 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-[#050B14] via-transparent to-transparent"></div>

        <!-- Floating Interactive Badges over Vessel -->
        <div class="absolute top-16 left-16 sm:left-28 hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-500/20 border border-amber-400/40 backdrop-blur-md text-amber-300 text-xs font-semibold shadow-xl">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            <span>Customs Hold &bull; Bay 4</span>
        </div>

        <div class="absolute top-44 left-32 sm:left-64 hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/20 border border-emerald-400/40 backdrop-blur-md text-emerald-300 text-xs font-semibold shadow-xl">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span>Optimal Reefer Temp (+4°C)</span>
        </div>

        <div class="absolute bottom-20 left-16 sm:left-40 hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-teal-500/20 border border-teal-400/40 backdrop-blur-md text-teal-300 text-xs font-semibold shadow-xl">
            <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
            <span>AIS Telemetry Signal Active</span>
        </div>

        <!-- Left Hero Metrics Stack -->
        <div class="relative z-10 p-6 sm:p-8 lg:p-10 max-w-md space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-400/10 border border-teal-400/25 text-teal-300 text-xs font-mono font-semibold">
                <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                ACTIVE FLEET MONITOR
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight">
                Global Transit Tracking
            </h2>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                Seamless real-time telematics connecting maritime container lines, air freighters, and bonded distribution hubs across {{ count($locations) }} world ports.
            </p>

            <div class="pt-2 flex items-center space-x-6 text-xs font-mono">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Active Shipments</span>
                    <span class="text-xl font-bold text-white">{{ $inTransit }} in transit</span>
                </div>
                <div class="border-l border-white/10 pl-6">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Open Exceptions</span>
                    <span class="text-xl font-bold text-rose-400">{{ $openExceptions }} incidents</span>
                </div>
                <div class="border-l border-white/10 pl-6">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Delayed Ratio</span>
                    <span class="text-xl font-bold text-amber-400">{{ $delayed }} delayed</span>
                </div>
            </div>
        </div>

        <!-- Floating Glass Card for Featured Shipment (Right side) -->
        @php
            $feat = $featuredShipment ?? $activeShipments->first();
        @endphp
        @if($feat)
        <div class="relative lg:absolute lg:right-8 lg:top-8 lg:bottom-8 lg:w-[420px] m-4 lg:m-0 z-20">
            <div class="h-full p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl flex flex-col justify-between space-y-4">
                <!-- Card Header -->
                <div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-mono text-teal-300 uppercase font-semibold">{{ $feat->carrier->name ?? 'MSC Aurora' }}</span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wider uppercase flex items-center gap-1.5 {{ $feat->is_delayed ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40' : 'bg-emerald-400/20 text-emerald-300 border border-emerald-400/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $feat->is_delayed ? 'bg-amber-400' : 'bg-emerald-400' }} animate-pulse"></span>
                            {{ $feat->is_delayed ? 'Delayed' : 'In Transit' }}
                        </span>
                    </div>
                    <div class="text-lg font-bold font-mono text-white mt-1">{{ $feat->tracking_number }}</div>
                    <div class="text-xs text-slate-300 flex items-center justify-between mt-1">
                        <span>{{ $feat->originLocation->name ?? 'Shanghai Port' }}</span>
                        <span class="text-teal-400">&rarr;</span>
                        <span>{{ $feat->destinationLocation->name ?? 'Rotterdam' }}</span>
                    </div>
                </div>

                <!-- Horizontal Port Progress Bar with Dots -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Departed: {{ $feat->originLocation->unlocode ?? 'CNSHA' }}</span>
                        <span class="text-amber-400 font-semibold font-mono">ETA: {{ $feat->estimated_arrival_at?->diffForHumans() ?? 'On Track' }}</span>
                    </div>
                    <div class="relative w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-teal-400 via-amber-400 to-amber-500 rounded-full" style="width: {{ $feat->status === 'DELIVERED' ? '100%' : '68%' }}"></div>
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
                            <span class="font-bold text-amber-300">Port Variance: +{{ $feat->delay_hours }}h Delay</span>
                            <p class="text-[11px] text-amber-200/80 mt-0.5 line-clamp-1">{{ $feat->delay_reason ?? 'Adverse berth weather conditions' }}</p>
                        </div>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-xs text-emerald-200 flex items-center space-x-2.5">
                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="font-bold">On-Schedule: Optimal sea lanes & clear port gate</span>
                    </div>
                @endif

                <!-- Checkpoint Weather & Telemetry Snapshot -->
                <div class="pt-2 border-t border-white/10 flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-2 text-slate-300">
                        <svg class="w-4 h-4 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 00-9.78 2.096A4.001 4.001 0 003 15z"/></svg>
                        <span>{{ $feat->currentLocation->city ?? 'Checkpoint' }}: <strong>{{ $feat->currentLocation->weather_snapshot['temperature_c'] ?? '22' }}°C {{ $feat->currentLocation->weather_snapshot['condition'] ?? 'Clear' }}</strong></span>
                    </div>
                    <a href="{{ route('shipments.show', $feat) }}" class="text-xs font-bold text-teal-300 hover:text-white inline-flex items-center gap-1 transition-colors">
                        View telemetry &rarr;
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- 3 Bottom Panels (Balanced Grid cols-1 lg:grid-cols-3 gap-6) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Panel 1: Shipment Volume & Carrier Analytics (Left, Chart.js) -->
        <div class="rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Fleet Analytics</span>
                    <h3 class="text-base font-bold text-white">Carrier & Status Distribution</h3>
                </div>
                <span class="text-xs font-mono text-slate-400">{{ $totalShipments }} total</span>
            </div>

            <div class="h-56 relative flex items-center justify-center">
                <canvas id="shipmentsStatusChart"></canvas>
            </div>

            <div class="pt-3 border-t border-white/10 grid grid-cols-2 gap-3 text-center text-xs">
                <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Delivered Safely</span>
                    <span class="text-sm font-bold text-emerald-400 font-mono">{{ $delivered }}</span>
                </div>
                <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Total Value (USD)</span>
                    <span class="text-sm font-bold text-amber-400 font-mono">${{ number_format($totalValueUsd / 1000, 0) }}k</span>
                </div>
            </div>
        </div>

        <!-- Panel 2: Delivery Schedule & On-Time Rate (Center) -->
        <div class="rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Reliability Metric</span>
                    <h3 class="text-base font-bold text-white">Delivery Schedule & Variance</h3>
                </div>
                <div class="text-right">
                    <span class="text-lg font-bold font-mono text-emerald-400">{{ $onTimeRate }}%</span>
                    <span class="text-[10px] block text-slate-500 uppercase font-semibold">On-Time</span>
                </div>
            </div>

            <!-- List of active routes with visual indicators -->
            <div class="space-y-3 overflow-y-auto max-h-56 pr-1 text-xs">
                @forelse($activeRoutes as $route)
                    <div class="p-3 rounded-xl bg-slate-950/50 border border-slate-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-white">{{ $route->tracking_number }}</span>
                            <span class="font-mono text-[10px] px-2 py-0.5 rounded {{ $route->is_delayed ? 'bg-amber-400/20 text-amber-300' : 'bg-emerald-400/20 text-emerald-300' }}">
                                {{ $route->is_delayed ? '+'.$route->delay_hours.'h' : 'On Schedule' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300 text-[11px]">
                            <span>{{ $route->originLocation->unlocode }} &rarr; {{ $route->destinationLocation->unlocode }}</span>
                            <span class="text-slate-400 font-mono">{{ $route->carrier->code }}</span>
                        </div>
                        <div class="w-full bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="h-full bg-teal-400 rounded-full" style="width: {{ $route->status === 'IN_TRANSIT' ? '65%' : '30%' }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-500">No active transit schedules</div>
                @endforelse
            </div>

            <div class="pt-3 border-t border-white/10 flex items-center justify-between text-xs text-slate-400">
                <span>Calculated via `EtaService`</span>
                <a href="{{ route('shipments.index') }}" class="text-teal-300 hover:text-white font-semibold">All Shipments &rarr;</a>
            </div>
        </div>

        <!-- Panel 3: Global Intelligence: Weather, Currency & News (Right) -->
        <div class="rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 p-6 flex flex-col justify-between space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-white/10">
                <div>
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">External Telemetry</span>
                    <h3 class="text-base font-bold text-white">Live Port Weather & Currency</h3>
                </div>
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>

            <!-- Currency Quick Converter Widget -->
            <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2 text-xs">
                <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Quick Currency Converter</span>
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" id="dash-curr-amount" value="1000" class="col-span-1 px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none">
                    <select id="dash-curr-from" class="col-span-1 px-2 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white text-xs font-mono focus:outline-none">
                        <option value="EUR">EUR</option>
                        <option value="IDR">IDR</option>
                        <option value="SGD">SGD</option>
                        <option value="CNY">CNY</option>
                    </select>
                    <button onclick="convertDashCurrency()" class="col-span-1 py-1.5 rounded-lg bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                        Convert
                    </button>
                </div>
                <div id="dash-curr-result" class="text-right font-mono font-bold text-amber-400 text-xs">
                    ≈ $1,086.96 USD
                </div>
            </div>

            <!-- Weather for Major Ports -->
            <div class="space-y-2">
                <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Port Weather Conditions</span>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    @foreach($weatherPorts as $p)
                        <div class="p-2.5 rounded-xl bg-slate-950/40 border border-slate-800 flex flex-col justify-between">
                            <span class="font-semibold text-slate-200 truncate">{{ $p['city'] }}</span>
                            <div class="flex items-center justify-between mt-1 text-[11px]">
                                <span class="font-bold text-teal-300 font-mono">{{ $p['weather']['temperature_c'] ?? '24' }}°C</span>
                                <span class="text-slate-400 truncate max-w-[70px] text-[10px]">{{ $p['weather']['condition'] ?? 'Clear' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Latest News Headline -->
            <div class="pt-3 border-t border-white/10 text-xs">
                @if(!empty($news[0]))
                    <div class="flex items-start space-x-2">
                        <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 text-[10px] font-bold shrink-0">NEWS</span>
                        <p class="text-slate-300 text-[11px] line-clamp-1 hover:text-white transition">
                            {{ $news[0]['headline'] }}
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function convertDashCurrency() {
        const amount = document.getElementById('dash-curr-amount').value || 1000;
        const from = document.getElementById('dash-curr-from').value || 'EUR';

        fetch(`/api/v1/integrations/currency?amount=${amount}&from=${from}&to=USD`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(res => {
            if (res.data) {
                document.getElementById('dash-curr-result').innerText = `≈ $${Number(res.data.converted_amount).toLocaleString('en-US', {minimumFractionDigits: 2})} USD`;
            }
        })
        .catch(() => {
            const rates = { EUR: 1.086, IDR: 0.000063, SGD: 0.746, CNY: 0.138 };
            const est = amount * (rates[from] || 1);
            document.getElementById('dash-curr-result').innerText = `≈ $${est.toFixed(2)} USD`;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('shipmentsStatusChart');
        if (ctx) {
            const statusCounts = @json($statusCounts);
            const labels = Object.keys(statusCounts);
            const data = Object.values(statusCounts);

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data.length ? data : [1, 2, 3],
                        backgroundColor: [
                            '#38bdf8',
                            '#f59e0b',
                            '#10b981',
                            '#818cf8',
                            '#94a3b8'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#94a3b8',
                                font: { size: 10, family: 'sans-serif' },
                                boxWidth: 10
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    });
</script>
@endpush

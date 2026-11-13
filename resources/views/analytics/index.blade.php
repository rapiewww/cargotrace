@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- 1. Header & Filter Toolbar -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 pb-6 border-b theme-border">
        <div>
            <div class="flex flex-wrap items-center gap-2.5 mb-2">
                @if($isCustomer)
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/25">
                        Customer consignments scope
                    </span>
                @else
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-600/15 text-blue-600 dark:text-cyan-400 border border-blue-500/25">
                        Fleet-wide operations scope
                    </span>
                @endif
                <span class="text-xs theme-text-muted">
                    Window: {{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold theme-heading tracking-tight">
                @if($isCustomer)
                    Consignment analytics summary
                @else
                    Operations and fleet analytics
                @endif
            </h1>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1 max-w-3xl">
                @if($isCustomer)
                    Performance indicators, carrier reliability, and corridor metrics for your contracted ocean-container shipments.
                @else
                    Carrier reliability metrics, corridor delay variance, weekly throughput, and exception resolution velocity across commercial container operations.
                @endif
            </p>
        </div>

        <!-- Date Range Filter Form -->
        <form method="GET" action="{{ route('analytics.index') }}" class="flex flex-wrap items-center gap-3 p-3 rounded-2xl theme-card border theme-border">
            <div class="flex items-center gap-2">
                <label for="filter-from" class="text-xs font-medium theme-text-secondary">From</label>
                <input
                    type="date"
                    id="filter-from"
                    name="from"
                    value="{{ $from->toDateString() }}"
                    class="px-2.5 py-1.5 rounded-xl theme-card-elevated border theme-border text-xs font-mono theme-heading focus:outline-none focus:border-cyan-500"
                />
            </div>

            <div class="flex items-center gap-2">
                <label for="filter-to" class="text-xs font-medium theme-text-secondary">To</label>
                <input
                    type="date"
                    id="filter-to"
                    name="to"
                    value="{{ $to->toDateString() }}"
                    class="px-2.5 py-1.5 rounded-xl theme-card-elevated border theme-border text-xs font-mono theme-heading focus:outline-none focus:border-cyan-500"
                />
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="submit"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500/40"
                >
                    Apply filter
                </button>
                <a
                    href="{{ route('analytics.index') }}"
                    title="Reset to default 90 days"
                    class="px-3 py-1.5 rounded-xl text-xs font-medium theme-card-elevated border theme-border theme-text-secondary hover:theme-heading transition-colors duration-150"
                >
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Quick Date Range Presets -->
    <div class="flex flex-wrap items-center gap-2 text-xs theme-text-secondary">
        <span class="font-medium mr-1">Quick presets:</span>
        @php
            $today = \Carbon\Carbon::now()->toDateString();
            $d30 = \Carbon\Carbon::now()->subDays(30)->toDateString();
            $d90 = \Carbon\Carbon::now()->subDays(90)->toDateString();
            $d180 = \Carbon\Carbon::now()->subDays(180)->toDateString();
            $ytd = \Carbon\Carbon::now()->startOfYear()->toDateString();
        @endphp
        <a href="{{ route('analytics.index', ['from' => $d30, 'to' => $today]) }}" class="px-2.5 py-1 rounded-lg theme-card border theme-border hover:theme-heading transition-colors">Last 30 days</a>
        <a href="{{ route('analytics.index', ['from' => $d90, 'to' => $today]) }}" class="px-2.5 py-1 rounded-lg theme-card border theme-border hover:theme-heading transition-colors">Last 90 days</a>
        <a href="{{ route('analytics.index', ['from' => $d180, 'to' => $today]) }}" class="px-2.5 py-1 rounded-lg theme-card border theme-border hover:theme-heading transition-colors">Last 180 days</a>
        <a href="{{ route('analytics.index', ['from' => $ytd, 'to' => $today]) }}" class="px-2.5 py-1 rounded-lg theme-card border theme-border hover:theme-heading transition-colors">Year to date</a>
    </div>

    <!-- 2. High-Level KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Total Shipments & On-Time Rate -->
        <div class="p-5 rounded-2xl theme-card border theme-border flex flex-col justify-between">
            <span class="text-xs font-medium theme-text-secondary">Total shipments</span>
            <div class="flex items-baseline justify-between mt-2">
                <div class="text-3xl font-extrabold theme-heading font-mono">
                    {{ number_format($summary['total_shipments']) }}
                </div>
                <div class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $summary['on_time_rate'] >= 80 ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25' }}">
                    {{ $summary['on_time_rate'] }}% on-time
                </div>
            </div>
            <div class="mt-2 text-xs theme-text-muted flex items-center justify-between">
                <span>{{ number_format($summary['on_time_count']) }} on-time</span>
                <span>{{ number_format($summary['delayed_count']) }} delayed</span>
            </div>
        </div>

        <!-- KPI 2: Average Delay Hours -->
        <div class="p-5 rounded-2xl theme-card border theme-border flex flex-col justify-between">
            <span class="text-xs font-medium theme-text-secondary">Average delay (delayed cargo)</span>
            <div class="flex items-baseline justify-between mt-2">
                <div class="text-3xl font-extrabold theme-heading font-mono">
                    {{ $summary['avg_delay_hours'] }}<span class="text-sm font-normal theme-text-muted font-sans ml-1">hours</span>
                </div>
                @if($summary['delayed_count'] > 0)
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25">
                        {{ $summary['delayed_count'] }} delayed consignments
                    </span>
                @else
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                        Zero delays
                    </span>
                @endif
            </div>
            <div class="mt-2 text-xs theme-text-muted">
                Calculated across delayed voyages in range
            </div>
        </div>

        <!-- KPI 3: Cargo Valuation & Volume -->
        <div class="p-5 rounded-2xl theme-card border theme-border flex flex-col justify-between">
            <span class="text-xs font-medium theme-text-secondary">Total cargo valuation</span>
            <div class="flex items-baseline justify-between mt-2">
                <div class="text-3xl font-extrabold theme-heading font-mono">
                    ${{ number_format($summary['total_value_usd'] / 1000, 0) }}k <span class="text-xs font-normal theme-text-muted font-sans">USD</span>
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/25">
                    {{ number_format($summary['total_volume_cbm'], 0) }} CBM
                </span>
            </div>
            <div class="mt-2 text-xs theme-text-muted">
                Converted to USD at transaction rates
            </div>
        </div>

        <!-- KPI 4: Exceptions Raised & Resolution -->
        <div class="p-5 rounded-2xl theme-card border theme-border flex flex-col justify-between">
            <span class="text-xs font-medium theme-text-secondary">Exceptions logged</span>
            <div class="flex items-baseline justify-between mt-2">
                <div class="text-3xl font-extrabold theme-heading font-mono">
                    {{ number_format($summary['total_exceptions']) }}
                </div>
                @if($summary['open_exceptions'] > 0)
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/25">
                        {{ $summary['open_exceptions'] }} open / investigating
                    </span>
                @else
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                        All resolved
                    </span>
                @endif
            </div>
            <div class="mt-2 text-xs theme-text-muted flex items-center justify-between">
                <span>{{ number_format($summary['resolved_exceptions']) }} resolved</span>
                <span>{{ $resolutionTime['overall_avg_hours'] !== null ? $resolutionTime['overall_avg_hours'].'h avg resolution' : 'No resolutions' }}</span>
            </div>
        </div>
    </div>

    @if(!$summary['has_data'])
        <!-- Empty State When No Data in Range -->
        <div class="p-12 rounded-3xl theme-card border theme-border text-center flex flex-col items-center justify-center my-8">
            <div class="w-14 h-14 rounded-2xl bg-slate-500/10 border theme-border flex items-center justify-center text-slate-400 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold theme-heading">No shipment activity found in selected date range</h3>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1.5 max-w-md">
                There are no ocean consignments or exception events recorded between {{ $from->format('M d, Y') }} and {{ $to->format('M d, Y') }}. Try widening the filter or resetting to the last 90 days.
            </p>
            <div class="mt-5">
                <a
                    href="{{ route('analytics.index', ['from' => $d90, 'to' => $today]) }}"
                    class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors"
                >
                    Reset to last 90 days
                </a>
            </div>
        </div>
    @else
        <!-- 3. Section 1: Carrier On-Time Performance (Chart + Accessible Table) -->
        <div class="p-6 rounded-3xl theme-card border theme-border space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b theme-border">
                <div>
                    <h2 class="text-lg font-bold theme-heading">Carrier reliability and on-time performance</h2>
                    <p class="text-xs theme-text-secondary mt-0.5">
                        Performance aggregates by ocean container liner operating across active shipping corridors.
                    </p>
                </div>
                <a
                    href="{{ route('analytics.export', ['table' => 'carriers', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-medium theme-card-elevated border theme-border theme-text-secondary hover:theme-heading transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Chart Container (Left col-span-5) -->
                <div class="lg:col-span-5 space-y-2">
                    <div class="h-64 sm:h-72 w-full">
                        <canvas id="analyticsCarrierChart"></canvas>
                    </div>
                    <p class="text-[11px] theme-text-muted text-center italic">
                        Color indicator: green for 85%+ on-time, cyan for 70%-84%, amber under 70%.
                    </p>
                </div>

                <!-- Accessible Data Table (Right col-span-7) -->
                <div class="lg:col-span-7 overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <caption class="sr-only">Carrier on-time performance statistics table</caption>
                        <thead>
                            <tr class="border-b theme-border theme-text-secondary">
                                <th scope="col" class="py-2.5 px-3 font-semibold uppercase tracking-wider font-mono text-[10px]">Code</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold">Carrier name</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Shipments</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">On-time</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Delayed</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y theme-border">
                            @forelse($carrierPerformance as $carrier)
                                <tr class="hover:bg-slate-500/5 transition-colors">
                                    <td class="py-2.5 px-3 font-mono font-bold text-cyan-600 dark:text-cyan-400">
                                        {{ $carrier->carrier_code }}
                                    </td>
                                    <th scope="row" class="py-2.5 px-3 font-medium theme-heading">
                                        {{ $carrier->carrier_name }}
                                    </th>
                                    <td class="py-2.5 px-3 text-right font-mono theme-heading">
                                        {{ number_format($carrier->total_shipments) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                                        {{ number_format($carrier->on_time_count) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono {{ $carrier->delayed_count > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'theme-text-muted' }}">
                                        {{ number_format($carrier->delayed_count) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="inline-block px-2 py-0.5 rounded-full font-mono font-bold text-[10px] {{ $carrier->on_time_rate >= 85 ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25' : ($carrier->on_time_rate >= 70 ? 'bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 border border-cyan-500/25' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25') }}">
                                            {{ $carrier->on_time_rate }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center theme-text-muted">No carrier records available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. Section 2: 12-Week Shipment Throughput & Transit Reliability (Chart + Table) -->
        <div class="p-6 rounded-3xl theme-card border theme-border space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b theme-border">
                <div>
                    <h2 class="text-lg font-bold theme-heading">12-week throughput and performance trend</h2>
                    <p class="text-xs theme-text-secondary mt-0.5">
                        Weekly consignment creations comparing on-time arrivals against voyage delays.
                    </p>
                </div>
                <a
                    href="{{ route('analytics.export', ['table' => 'weekly', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-medium theme-card-elevated border theme-border theme-text-secondary hover:theme-heading transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Weekly Chart (Left col-span-6) -->
                <div class="lg:col-span-6 space-y-2">
                    <div class="h-64 sm:h-72 w-full">
                        <canvas id="analyticsWeeklyChart"></canvas>
                    </div>
                    <p class="text-[11px] theme-text-muted text-center italic">
                        Stacked columns represent total weekly container creations (green: on-time, amber: delayed).
                    </p>
                </div>

                <!-- Accessible Weekly Table (Right col-span-6) -->
                <div class="lg:col-span-6 overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <caption class="sr-only">12-week shipment throughput data table</caption>
                        <thead>
                            <tr class="border-b theme-border theme-text-secondary">
                                <th scope="col" class="py-2.5 px-3 font-semibold">Week period</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Volume</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">On-time</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Delayed</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y theme-border font-mono">
                            @foreach($weeklyTrend as $week)
                                <tr class="hover:bg-slate-500/5 transition-colors">
                                    <th scope="row" class="py-2 px-3 font-sans font-medium theme-heading">
                                        {{ $week['week'] }}
                                    </th>
                                    <td class="py-2 px-3 text-right theme-heading font-bold">
                                        {{ number_format($week['total']) }}
                                    </td>
                                    <td class="py-2 px-3 text-right text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($week['on_time']) }}
                                    </td>
                                    <td class="py-2 px-3 text-right {{ $week['delayed'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'theme-text-muted' }}">
                                        {{ number_format($week['delayed']) }}
                                    </td>
                                    <td class="py-2 px-3 text-right font-sans">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold {{ $week['total'] == 0 ? 'theme-text-muted' : ($week['on_time_rate'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400') }}">
                                            {{ $week['total'] > 0 ? $week['on_time_rate'].'%' : '—' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 5. Section 3: Exceptions Distribution & Resolution Velocity -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Exceptions by Category (col-span-7) -->
            <div class="lg:col-span-7 p-6 rounded-3xl theme-card border theme-border space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b theme-border">
                    <div>
                        <h2 class="text-lg font-bold theme-heading">Exceptions by operational category</h2>
                        <p class="text-xs theme-text-secondary mt-0.5">
                            Frequency and severity classifications logged across customs, weather, and terminal points.
                        </p>
                    </div>
                    <a
                        href="{{ route('analytics.export', ['table' => 'exceptions', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-medium theme-card-elevated border theme-border theme-text-secondary hover:theme-heading transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export CSV</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                    <div class="sm:col-span-5 h-56 w-full">
                        <canvas id="analyticsExceptionChart"></canvas>
                    </div>

                    <div class="sm:col-span-7 overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <caption class="sr-only">Exceptions distribution table by type</caption>
                            <thead>
                                <tr class="border-b theme-border theme-text-secondary">
                                    <th scope="col" class="py-2 px-2.5 font-semibold">Category</th>
                                    <th scope="col" class="py-2 px-2.5 font-semibold text-right">Count</th>
                                    <th scope="col" class="py-2 px-2.5 font-semibold text-center">Severity</th>
                                    <th scope="col" class="py-2 px-2.5 font-semibold text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y theme-border">
                                @forelse($exceptionsByType as $ex)
                                    <tr class="hover:bg-slate-500/5 transition-colors">
                                        <th scope="row" class="py-2 px-2.5 font-medium theme-heading">
                                            {{ $ex->label }}
                                        </th>
                                        <td class="py-2 px-2.5 text-right font-mono font-bold theme-heading">
                                            {{ $ex->count }}
                                        </td>
                                        <td class="py-2 px-2.5 text-center">
                                            <span class="inline-flex gap-1 text-[10px] font-mono">
                                                @if($ex->low_count > 0)<span class="px-1 rounded bg-slate-500/20 text-slate-400" title="Low severity">{{ $ex->low_count }}L</span>@endif
                                                @if($ex->medium_count > 0)<span class="px-1 rounded bg-amber-500/20 text-amber-500 font-semibold" title="Medium severity">{{ $ex->medium_count }}M</span>@endif
                                                @if($ex->high_count > 0)<span class="px-1 rounded bg-rose-500/20 text-rose-500 font-bold" title="High severity">{{ $ex->high_count }}H</span>@endif
                                            </span>
                                        </td>
                                        <td class="py-2 px-2.5 text-right font-mono text-[11px]">
                                            @if($ex->open_count > 0)
                                                <span class="text-rose-500 font-semibold">{{ $ex->open_count }} open</span>
                                            @elseif($ex->investigating_count > 0)
                                                <span class="text-amber-500 font-semibold">{{ $ex->investigating_count }} inv.</span>
                                            @else
                                                <span class="text-emerald-500">all resolved</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center theme-text-muted">No exception records in range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Average Resolution Velocity (col-span-5) -->
            <div class="lg:col-span-5 p-6 rounded-3xl theme-card border theme-border flex flex-col justify-between space-y-6">
                <div>
                    <div class="flex items-center justify-between pb-4 border-b theme-border">
                        <div>
                            <h2 class="text-lg font-bold theme-heading">Resolution velocity</h2>
                            <p class="text-xs theme-text-secondary mt-0.5">
                                Mean duration to clear and resolve incident tickets.
                            </p>
                        </div>
                        <a
                            href="{{ route('analytics.export', ['table' => 'resolution', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-medium theme-card-elevated border theme-border theme-text-secondary hover:theme-heading transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <span>CSV</span>
                        </a>
                    </div>

                    <div class="mt-4 p-4 rounded-2xl theme-card-elevated border theme-border flex items-center justify-between">
                        <div>
                            <span class="text-[11px] theme-text-secondary font-medium">Mean resolution time</span>
                            <div class="text-2xl font-black font-mono theme-heading mt-0.5">
                                {{ $resolutionTime['overall_avg_hours'] !== null ? $resolutionTime['overall_avg_hours'].' hours' : 'No resolved items' }}
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
                            {{ $resolutionTime['resolved_total'] }} resolved
                        </span>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <caption class="sr-only">Average resolution time per exception type</caption>
                            <thead>
                                <tr class="border-b theme-border theme-text-secondary">
                                    <th scope="col" class="py-2 px-2.5 font-semibold">Incident type</th>
                                    <th scope="col" class="py-2 px-2.5 font-semibold text-right">Resolved</th>
                                    <th scope="col" class="py-2 px-2.5 font-semibold text-right">Avg (h)</th>
                                    <th scope="col" class="py-2 px-2.5 font-semibold text-right">Max (h)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y theme-border font-mono">
                                @forelse($resolutionTime['by_type'] as $row)
                                    <tr class="hover:bg-slate-500/5 transition-colors">
                                        <th scope="row" class="py-2 px-2.5 font-sans font-medium theme-heading">
                                            {{ $row->label }}
                                        </th>
                                        <td class="py-2 px-2.5 text-right text-emerald-600 dark:text-emerald-400">
                                            {{ $row->resolved_count }}
                                        </td>
                                        <td class="py-2 px-2.5 text-right font-bold theme-heading">
                                            {{ $row->avg_hours }}h
                                        </td>
                                        <td class="py-2 px-2.5 text-right theme-text-muted">
                                            {{ $row->max_hours }}h
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center font-sans theme-text-muted">No resolution events recorded in range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="text-[11px] theme-text-muted italic border-t theme-border pt-3">
                    Calculated from initial timestamp of detection to formal resolver sign-off.
                </div>
            </div>
        </div>

        <!-- 6. Section 4: Shipping Lanes & Transit Delay Performance (Chart + Table) -->
        <div class="p-6 rounded-3xl theme-card border theme-border space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b theme-border">
                <div>
                    <h2 class="text-lg font-bold theme-heading">Maritime shipping lanes and delay variance</h2>
                    <p class="text-xs theme-text-secondary mt-0.5">
                        Corridor routing metrics comparing scheduled voyage arrivals to actual terminal berthing.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('analytics.export', ['table' => 'top_delayed', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium theme-card-elevated border theme-border theme-text-secondary hover:theme-heading transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export top delayed</span>
                    </a>
                    <a
                        href="{{ route('analytics.export', ['table' => 'lanes', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium theme-card-elevated border theme-border theme-text-secondary hover:theme-heading transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export all lanes</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- Top 5 Delayed Lanes Chart (Left col-span-5) -->
                <div class="lg:col-span-5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold theme-heading">Top 5 delayed corridors</span>
                        <span class="text-[10px] theme-text-muted">Average delay hours</span>
                    </div>
                    <div class="h-64 sm:h-72 w-full">
                        <canvas id="analyticsLaneChart"></canvas>
                    </div>
                    <p class="text-[11px] theme-text-muted text-center italic">
                        Corridors identified with highest mean voyage delay hours.
                    </p>
                </div>

                <!-- Comprehensive Lanes Table (Right col-span-7) -->
                <div class="lg:col-span-7 overflow-x-auto max-h-[380px] overflow-y-auto pr-1">
                    <table class="w-full text-left border-collapse text-xs">
                        <caption class="sr-only">All shipping lane performance statistics table</caption>
                        <thead class="sticky top-0 theme-card z-10">
                            <tr class="border-b theme-border theme-text-secondary">
                                <th scope="col" class="py-2.5 px-3 font-semibold uppercase tracking-wider font-mono text-[10px]">Corridor</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold">Origin & destination</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Shipments</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">On-time</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Avg delay</th>
                                <th scope="col" class="py-2.5 px-3 font-semibold text-right">Max delay</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y theme-border">
                            @forelse($laneDelays as $lane)
                                <tr class="hover:bg-slate-500/5 transition-colors">
                                    <td class="py-2.5 px-3 font-mono font-bold text-cyan-600 dark:text-cyan-400 whitespace-nowrap">
                                        {{ $lane->lane_label }}
                                    </td>
                                    <th scope="row" class="py-2.5 px-3 font-medium theme-heading text-[11px] whitespace-nowrap">
                                        {{ $lane->origin_name }} → {{ $lane->destination_name }}
                                    </th>
                                    <td class="py-2.5 px-3 text-right font-mono theme-heading font-bold">
                                        {{ number_format($lane->total_shipments) }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono">
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $lane->on_time_rate >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                            {{ $lane->on_time_rate }}%
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono {{ $lane->avg_delay_hours > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'theme-text-muted' }}">
                                        {{ $lane->avg_delay_hours }}h
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono theme-text-muted">
                                        {{ $lane->max_delay_hours }}h
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center theme-text-muted">No lane transit records in range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    window.__CARGOTRACE__ = window.__CARGOTRACE__ || {};
    window.__CARGOTRACE__.analyticsCharts = {
        carrierChartData: @json($carrierChartData),
        weeklyChartData: @json($weeklyChartData),
        exceptionChartData: @json($exceptionChartData),
        laneChartData: @json($laneChartData)
    };
</script>
@endsection

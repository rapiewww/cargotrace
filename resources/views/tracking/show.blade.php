@extends('layouts.guest')
@section('has-locale-switcher', '1')

@section('content')
<div class="relative min-h-screen theme-page theme-text-primary overflow-x-hidden">
    <!-- Enterprise Navbar -->
    <header class="sticky top-0 z-50 theme-header backdrop-blur-xl border-b theme-border shadow-lg">
        <div class="max-w-[1600px] mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-2">
                <!-- Brand -->
                <a href="{{ route('welcome') }}" class="flex items-center space-x-2.5 sm:space-x-3.5 group min-w-0">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-blue-500 via-blue-600 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-500/25 shrink-0">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 22L2 5h20L12 22zm0-4.5l6.5-11h-13l6.5 11z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-lg sm:text-xl font-extrabold tracking-tight theme-heading block truncate">CargoTrace</span>
                            <span class="hidden sm:inline-block text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-cyan-400 border border-blue-500/20 font-mono">{{ __('Public Track') }}</span>
                        </div>
                        <span class="hidden sm:block text-[10px] uppercase tracking-widest theme-text-muted font-semibold leading-tight">{{ __('Global spice freight by sea, air and road') }}</span>
                    </div>
                </a>

                <!-- Nav & Actions -->
                <div class="flex items-center space-x-1.5 sm:space-x-3 shrink-0">
                    <a href="{{ route('welcome') }}" class="hidden md:inline-block text-xs theme-text-secondary hover:theme-heading transition whitespace-nowrap">
                        &larr; {{ __('Back to Home') }}
                    </a>

                    <!-- Theme Toggle Switcher -->
                    <button
                        type="button"
                        id="tracking-theme-toggle"
                        role="switch"
                        aria-checked="true"
                        aria-pressed="true"
                        tabindex="0"
                        onclick="window.CargoTrace && window.CargoTrace.toggleTheme ? window.CargoTrace.toggleTheme() : null"
                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.CargoTrace&&window.CargoTrace.toggleTheme?window.CargoTrace.toggleTheme():null}"
                        class="relative flex items-center p-1 rounded-full bg-slate-200 dark:bg-slate-900 border border-slate-300 dark:border-slate-800 w-12 sm:w-14 h-7 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shrink-0"
                        aria-label="{{ __('Toggle dark and light theme') }}"
                        title="{{ __('Switch theme') }}"
                    >
                        <span class="w-5 h-5 flex items-center justify-center text-slate-400 dark:text-cyan-400 transition-colors">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/></svg>
                        </span>
                        <span class="w-5 h-5 flex items-center justify-center text-amber-500 dark:text-slate-500 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </span>
                    </button>

                    <!-- Language Switcher (EN | ID) -->
                    <x-locale-switcher size="sm" />

                    @auth
                        <a href="{{ route('dashboard') }}" class="px-2.5 sm:px-4 py-2 rounded-xl text-[11px] sm:text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition whitespace-nowrap">
                            {{ __('Back to Dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-2.5 sm:px-4 py-2 rounded-xl text-[11px] sm:text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 transition whitespace-nowrap">
                            {{ __('Sign In / Portal') }} &rarr;
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8">
        <!-- Shipment Card Header -->
        <div class="p-6 sm:p-8 rounded-2xl theme-card backdrop-blur-xl border theme-border shadow-2xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b theme-border">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-cyan-400 font-mono">
                        {{ $shipment->carrier->name ?? __('Carrier Fleet') }} ({{ $shipment->carrier->code ?? 'LINE' }})
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-extrabold font-mono theme-heading tracking-tight mt-1">
                        {{ $shipment->tracking_number }}
                    </h1>
                    <div class="text-xs font-mono text-cyan-600 dark:text-cyan-400 mt-1 flex flex-wrap items-center gap-x-3 gap-y-1">
                        @foreach($shipment->formatted_reference_parts as $part)
                            <span><span class="theme-text-muted">{{ __($part['label']) }}:</span> {{ __($part['value']) }}</span>
                        @endforeach
                    </div>
                    <div class="text-xs theme-text-secondary flex items-center gap-2 mt-2 font-mono">
                        <span>{{ $shipment->originLocation->name }} ({{ $shipment->originLocation->display_code }})</span>
                        <span class="text-blue-600 dark:text-cyan-400">&rarr;</span>
                        <span>{{ $shipment->destinationLocation->name }} ({{ $shipment->destinationLocation->display_code }})</span>
                    </div>
                </div>

                <div class="flex flex-col items-start sm:items-end gap-2">
                    <span class="px-3.5 py-1.5 rounded-full text-xs font-bold font-mono uppercase tracking-wider border {{ $shipment->status === 'DELIVERED' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30' }}">
                        {{ $shipment->status_display_label }}
                    </span>

                    @if($shipment->is_delayed)
                        <span class="text-xs font-mono text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ __('Variance: +:hours h Delay', ['hours' => $shipment->delay_hours]) }}
                        </span>
                    @else
                        <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            {{ __('On Schedule') }}
                        </span>
                    @endif

                    @if(isset($freeTime) && $freeTime['has_arrived'])
                        @if($freeTime['is_over_limit'])
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                {{ $freeTime['badge_label'] }}
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-bold uppercase bg-blue-500/15 text-blue-600 dark:text-cyan-400 border border-blue-500/30">
                                {{ $freeTime['badge_label'] }}
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Stepper -->
            <div class="space-y-3">
                <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">{{ __('Transit Progression') }}</span>
                <x-status-stepper :status="$shipment->status" :mode="$shipment->transport_mode ?? 'SEA'" :compact="false" />
            </div>

            <!-- Dates / ETA Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 border-t theme-border text-xs font-mono">
                <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                    <span class="text-[10px] theme-text-muted uppercase block">{{ __('Expected Departure') }}</span>
                    <span class="font-bold theme-heading">{{ $shipment->expected_departure_at?->format('M d, Y H:i') ?? __('TBD') }}</span>
                </div>
                <div class="p-3 rounded-xl theme-card-elevated border theme-border">
                    <span class="text-[10px] theme-text-muted uppercase block">{{ __('Estimated Arrival (ETA)') }}</span>
                    <span class="font-bold {{ $shipment->is_delayed ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $shipment->estimated_arrival_at?->format('M d, Y H:i') ?? __('On Schedule') }}
                    </span>
                </div>
                <div class="p-3 rounded-xl theme-card-elevated border theme-border col-span-2 sm:col-span-1">
                    <span class="text-[10px] theme-text-muted uppercase block">{{ __('Transport Mode') }}</span>
                    <span class="font-bold theme-heading">{{ $shipment->cargo_type }} &bull; {{ $shipment->transport_mode ?? $shipment->carrier->mode ?? 'SEA' }}</span>
                </div>
            </div>
        </div>

        <!-- Interactive Map Container -->
        <div class="rounded-2xl overflow-hidden border theme-border shadow-2xl theme-card space-y-2 p-2">
            <div id="public-tracking-map" class="w-full h-80 sm:h-96 rounded-xl bg-slate-900"></div>
            <div class="px-3 py-2 flex items-center justify-between text-[11px] theme-text-muted font-mono">
                <span>{{ $shipment->originLocation->short_name }} {{ __('to') }} {{ $shipment->destinationLocation->short_name }} ({{ $shipment->originLocation->display_code }} &rarr; {{ $shipment->destinationLocation->display_code }})</span>
                <span>{{ __('Milestone Geodesic Plot') }}</span>
            </div>
        </div>

        <!-- Milestones Checkpoints Timeline -->
        <div class="p-6 sm:p-8 rounded-2xl theme-card backdrop-blur-xl border theme-border shadow-2xl space-y-6">
            <div class="flex items-center justify-between pb-4 border-b theme-border">
                <div>
                    <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">{{ __('Checkpoint Ledger') }}</span>
                    <h2 class="text-xl font-bold theme-heading">{{ __('Verified Route Milestones') }}</h2>
                </div>
                <span class="text-xs font-mono text-blue-600 dark:text-cyan-400">{{ __(':count Checkpoints', ['count' => count($shipment->trackingEvents)]) }}</span>
            </div>

            <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-300 dark:before:bg-slate-800">
                @forelse($shipment->trackingEvents as $evt)
                    <div class="relative">
                        <div class="absolute -left-6 top-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-[#0B1728] {{ $loop->first ? 'bg-blue-600 ring-4 ring-blue-500/20' : 'bg-slate-400 dark:bg-slate-600' }}"></div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs font-mono">
                                <span class="font-bold theme-heading">{{ $evt->status }}</span>
                                <span class="theme-text-muted">{{ $evt->event_time?->format('M d, Y H:i') }}</span>
                            </div>
                            <p class="text-xs theme-text-secondary">{{ $evt->description }}</p>
                            <div class="flex items-center justify-between text-[11px] theme-text-muted font-mono pt-1">
                                <span class="text-blue-600 dark:text-cyan-400">@ {{ $evt->location->name ?? __('Transit waypoint') }}@if($evt->location) ({{ $evt->location->display_code }})@endif</span>
                                @if($evt->weather_snapshot)
                                    <span class="inline-flex items-center gap-1"><x-mode-icon mode="THERMOMETER" class="w-3.5 h-3.5" /> {{ $evt->weather_snapshot['temperature_c'] ?? '' }}°C {{ $evt->weather_snapshot['condition'] ?? '' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 theme-text-muted text-xs">
                        {{ __('No milestone events recorded yet for this shipment.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Quick Track Another Shipment Box -->
        <div class="p-6 rounded-2xl theme-card-elevated border theme-border text-center space-y-3">
            <span class="text-xs theme-text-muted">{{ __('Need to track another shipment or reference?') }}</span>
            <div class="max-w-md mx-auto flex items-center gap-2">
                <input type="text" id="public-track-input" placeholder="{{ __('Enter tracking number...') }}" class="flex-1 px-4 py-2 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                <button type="button" onclick="CargoTraceTracking.search()" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs uppercase tracking-wider transition cursor-pointer">
                    {{ __('Track') }} &rarr;
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="theme-card border-t theme-border py-8 text-xs theme-text-muted text-center">
        <div class="max-w-5xl mx-auto px-4 space-y-2">
            <div>&copy; {{ date('Y') }} CargoTrace &bull; {{ __('Public Shipment Visibility') }}</div>
            <p class="text-[11px] theme-text-muted opacity-80">
                {{ __('Non-sensitive checkpoint view. Shipper identity, cargo value, and commercial invoices are restricted to authenticated consignees.') }}
            </p>
        </div>
    </footer>
</div>

{{-- Route Map Payload --}}
<script>
    window.__CARGOTRACE__ = {
        routeMap: {
            containerId: 'public-tracking-map',
            shipment: @json($mapPayload)
        }
    };
</script>
@endsection


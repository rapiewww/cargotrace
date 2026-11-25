@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    Global Locations & Checkpoints
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 font-mono">
                    Location Network ({{ count($locations) }})
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Seaports (UN/LOCODE), airports (IATA), and depots or borders (internal codes) with coordinates and local weather.
            </p>
        </div>

        @if(auth()->user()->role === 'admin')
            <button type="button" onclick="document.getElementById('add-location-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/20">
                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Register Location
            </button>
        @endif
    </div>

    <!-- Locations Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($locations as $loc)
            <x-card class="flex flex-col justify-between space-y-4 hover:border-cyan-500/40 transition-all duration-200 hover:scale-[1.01]">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-mono font-bold text-amber-500 dark:text-amber-400">{{ $loc->display_code }}</span>
                                <span class="text-[10px] theme-text-muted font-sans">{{ $loc->code_type_label }}</span>
                            </div>
                            <h3 class="text-base font-bold theme-heading leading-tight mt-0.5 tracking-tight">{{ $loc->name }}</h3>
                            <span class="text-xs theme-text-secondary">{{ $loc->city }}, {{ $loc->country }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono bg-blue-500/15 text-blue-600 dark:text-blue-300 border border-blue-500/30">
                            {{ $loc->type }}
                        </span>
                    </div>

                    <!-- Weather & Currency Snapshot -->
                    <div class="p-3 rounded-xl theme-card-elevated border theme-border space-y-2 text-xs">
                        <div class="flex items-center justify-between theme-text-secondary text-[11px] font-mono">
                            <span>Currency: <strong class="theme-heading">{{ $loc->currency_code }}</strong></span>
                            <span>TZ: <strong class="theme-text-primary">{{ $loc->timezone }}</strong></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-1.5 border-t theme-border font-mono">
                            <span class="theme-text-muted">GPS: {{ number_format($loc->latitude, 2) }}, {{ number_format($loc->longitude, 2) }}</span>
                            <a href="{{ route('welcome') }}#network" class="text-cyan-600 dark:text-cyan-400 hover:underline">View Map</a>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t theme-border flex items-center justify-between text-xs font-mono">
                    <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold">Origin Manifests</span>
                    <span class="font-bold theme-heading text-sm">{{ $loc->origin_shipments_count ?? 0 }}</span>
                </div>
            </x-card>
        @endforeach
    </div>
</div>

<!-- Add Location Modal -->
<div id="add-location-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="theme-card border theme-border rounded-2xl max-w-md w-full p-6 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between border-b theme-border pb-3">
            <h3 class="text-base font-bold theme-heading tracking-tight">Register Location</h3>
            <button onclick="document.getElementById('add-location-modal').classList.add('hidden')" class="theme-text-muted hover:theme-heading text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('operations.locations.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Location Code *</label>
                    <input type="text" name="unlocode" required placeholder="IDJKT / CGK / DEP-MES" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono uppercase focus:ring-1 focus:ring-cyan-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Location Type *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500 font-mono">
                        <option value="PORT" selected>PORT (Seaport)</option>
                        <option value="AIRPORT">AIRPORT</option>
                        <option value="DEPOT">DEPOT</option>
                        <option value="WAREHOUSE">WAREHOUSE</option>
                        <option value="BORDER">BORDER</option>
                        <option value="HUB">HUB (Distribution)</option>
                        <option value="CUSTOMS">CUSTOMS</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Location Name *</label>
                <input type="text" name="name" required placeholder="Port of Tanjung Priok / Kualanamu Airport / Medan Depot" class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">City *</label>
                    <input type="text" name="city" required placeholder="Jakarta" class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Country (2 letters) *</label>
                    <input type="text" name="country" required maxlength="2" placeholder="ID" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono uppercase focus:ring-1 focus:ring-cyan-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Latitude *</label>
                    <input type="number" step="0.000001" name="latitude" required placeholder="-6.1018" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Longitude *</label>
                    <input type="number" step="0.000001" name="longitude" required placeholder="106.8833" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Timezone</label>
                    <input type="text" name="timezone" placeholder="Asia/Jakarta" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono focus:ring-1 focus:ring-cyan-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Currency Code</label>
                    <input type="text" name="currency_code" maxlength="3" placeholder="IDR" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono uppercase focus:ring-1 focus:ring-cyan-500">
                </div>
            </div>

            <div class="pt-2 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('add-location-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl theme-text-muted hover:theme-heading border theme-border font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold transition">
                    Save Location
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

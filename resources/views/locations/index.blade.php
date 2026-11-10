@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                Global Ports & Checkpoints
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20">UN/LOCODE Network</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Primary container terminals, air cargo cities, and bonded free-trade hubs with live telematics.</p>
        </div>

        @if(auth()->user()->role === 'admin')
            <button onclick="document.getElementById('add-location-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                + Register Port / Hub
            </button>
        @endif
    </div>

    <!-- Locations Grid Cards (Balanced 4-col grid) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($locations as $loc)
            <div class="p-5 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl flex flex-col justify-between space-y-4 hover:border-teal-400/30 transition-all duration-200 hover:scale-[1.01]">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-mono font-bold text-amber-400 block">{{ $loc->unlocode }}</span>
                            <h3 class="text-base font-bold text-white leading-tight mt-0.5 tracking-tight">{{ $loc->name }}</h3>
                            <span class="text-xs text-slate-400">{{ $loc->city }}, {{ $loc->country }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $loc->type === 'PORT' ? 'bg-blue-500/15 text-blue-300 border border-blue-500/30' : ($loc->type === 'AIRPORT' ? 'bg-teal-500/15 text-teal-300 border border-teal-500/30' : 'bg-amber-500/15 text-amber-300 border border-amber-500/30') }}">
                            {{ $loc->type }}
                        </span>
                    </div>

                    <!-- Telemetry & Currency Snapshot -->
                    <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-400 text-[11px]">
                            <span>Currency: <strong class="text-white font-mono">{{ $loc->currency_code }}</strong></span>
                            <span>TZ: <strong class="text-slate-300 font-mono">{{ $loc->timezone }}</strong></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] pt-1.5 border-t border-white/5">
                            <span class="text-teal-300 font-bold font-mono">
                                🌡️ {{ $loc->weather_snapshot['temperature_c'] ?? '24' }}°C
                            </span>
                            <span class="text-slate-400 truncate max-w-[110px] text-[10px]">
                                {{ $loc->weather_snapshot['condition'] ?? 'Clear' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="pt-2.5 border-t border-white/10 flex items-center justify-between text-[11px] font-mono text-slate-400">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold">Active Bookings:</span>
                    <span class="font-bold text-teal-300">{{ ($loc->origin_shipments_count ?? 0) + ($loc->destination_shipments_count ?? 0) }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Add Location Modal -->
<div id="add-location-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-[#0B1728] border border-white/10 rounded-2xl max-w-md w-full p-6 text-slate-100 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <h3 class="text-base font-bold text-white tracking-tight">Register International Location</h3>
            <button onclick="document.getElementById('add-location-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('operations.locations.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">UN/LOCODE *</label>
                    <input type="text" name="unlocode" required placeholder="IDJKT" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Type *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                        <option value="PORT">PORT (Maritime)</option>
                        <option value="AIRPORT">AIRPORT (Air Cargo)</option>
                        <option value="HUB">HUB (Distribution)</option>
                        <option value="WAREHOUSE">WAREHOUSE (Bonded)</option>
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Facility / Port Name *</label>
                <input type="text" name="name" required placeholder="Tanjung Priok Container Terminal" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">City *</label>
                    <input type="text" name="city" required placeholder="Jakarta" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Country Code (2-char) *</label>
                    <input type="text" name="country_code" required placeholder="ID" maxlength="2" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Country Name *</label>
                <input type="text" name="country" required placeholder="Indonesia" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Currency (3) *</label>
                    <input type="text" name="currency_code" required placeholder="IDR" maxlength="3" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Latitude</label>
                    <input type="number" step="0.0000001" name="latitude" placeholder="-6.1000" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Longitude</label>
                    <input type="number" step="0.0000001" name="longitude" placeholder="106.8800" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                </div>
            </div>

            <div class="pt-3 border-t border-white/10 flex items-center justify-end space-x-2">
                <button type="button" onclick="document.getElementById('add-location-modal').classList.add('hidden')" class="px-3.5 py-2 rounded-xl text-slate-400 hover:text-white transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold transition-all duration-200 hover:scale-[1.02] shadow-md">
                    Save Location &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white font-sans">
                    Global Ports & Checkpoints
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20 font-mono">
                    UN/LOCODE Network ({{ count($locations) }})
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Primary container terminals, air cargo cities, and bonded free-trade hubs with live telematics.
            </p>
        </div>

        @if(auth()->user()->role === 'admin')
            <button type="button" onclick="document.getElementById('add-location-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/20">
                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Register Port / Hub
            </button>
        @endif
    </div>

    <!-- Locations Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($locations as $loc)
            <x-card class="flex flex-col justify-between space-y-4 hover:border-teal-400/30 transition-all duration-200 hover:scale-[1.01]">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-mono font-bold text-amber-400 block">{{ $loc->unlocode }}</span>
                            <h3 class="text-base font-bold text-white leading-tight mt-0.5 tracking-tight">{{ $loc->name }}</h3>
                            <span class="text-xs text-slate-400">{{ $loc->city }}, {{ $loc->country }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase font-mono {{ $loc->type === 'PORT' ? 'bg-blue-500/15 text-blue-300 border border-blue-500/30' : ($loc->type === 'AIRPORT' ? 'bg-teal-500/15 text-teal-300 border border-teal-500/30' : 'bg-amber-500/15 text-amber-300 border border-amber-500/30') }}">
                            {{ $loc->type }}
                        </span>
                    </div>

                    <!-- Telemetry & Currency Snapshot -->
                    <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-slate-400 text-[11px] font-mono">
                            <span>Currency: <strong class="text-white">{{ $loc->currency_code }}</strong></span>
                            <span>TZ: <strong class="text-slate-300">{{ $loc->timezone }}</strong></span>
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
            </x-card>
        @endforeach
    </div>
</div>

<!-- Add Location Modal -->
<div id="add-location-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-[#0B1728] border border-white/10 rounded-2xl max-w-md w-full p-6 text-slate-100 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <h3 class="text-base font-bold text-white tracking-tight">Register Port or Distribution Hub</h3>
            <button onclick="document.getElementById('add-location-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('operations.locations.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">UN/LOCODE (e.g. IDJKT, NLRTM) *</label>
                <input type="text" name="unlocode" required placeholder="IDJKT" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono uppercase focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Facility / Port Name *</label>
                <input type="text" name="name" required placeholder="Tanjung Priok Port" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">City *</label>
                    <input type="text" name="city" required placeholder="Jakarta" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Country *</label>
                    <input type="text" name="country" required placeholder="Indonesia" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Country Code (2 letters) *</label>
                    <input type="text" name="country_code" required maxlength="2" placeholder="ID" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono uppercase focus:ring-1 focus:ring-amber-500">
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Currency Code (3 letters) *</label>
                    <input type="text" name="currency_code" required maxlength="3" placeholder="IDR" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono uppercase focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Facility Type *</label>
                    <select name="type" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                        <option value="PORT">PORT</option>
                        <option value="AIRPORT">AIRPORT</option>
                        <option value="HUB">HUB</option>
                        <option value="WAREHOUSE">WAREHOUSE</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Timezone (e.g. Asia/Jakarta) *</label>
                    <input type="text" name="timezone" required value="UTC" placeholder="Asia/Jakarta" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                </div>
            </div>

            <div class="pt-2 flex justify-end space-x-2">
                <button type="button" onclick="document.getElementById('add-location-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-slate-400 hover:text-white transition">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-teal-400 to-teal-500 hover:from-teal-300 hover:to-teal-400 text-slate-950 font-bold transition">
                    Save Port / Hub
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

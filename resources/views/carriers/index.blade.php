@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-7xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-3">
                Carrier Fleet & Airlines
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-teal-400/10 text-teal-300 border border-teal-400/20">Global Fleet</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">Maritime shipping lines, air freighter fleets, and multimodal carriers across global trade lanes.</p>
        </div>

        @if(auth()->user()->role === 'admin')
            <button onclick="document.getElementById('add-carrier-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                + Register Carrier
            </button>
        @endif
    </div>

    <!-- Carriers Grid Cards (Balanced 3-col grid) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($carriers as $c)
            <div class="p-6 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl flex flex-col justify-between space-y-4 hover:border-teal-400/30 transition-all duration-200 hover:scale-[1.01]">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-mono font-bold text-amber-400 block">{{ $c->code }}</span>
                            <h3 class="text-lg font-bold text-white tracking-tight">{{ $c->name }}</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $c->mode === 'SEA' ? 'bg-blue-500/15 text-blue-300 border border-blue-500/30' : ($c->mode === 'AIR' ? 'bg-teal-500/15 text-teal-300 border border-teal-500/30' : 'bg-amber-500/15 text-amber-300 border border-amber-500/30') }}">
                            {{ $c->mode }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-300 space-y-1">
                        <div>Headquarters: <strong>{{ $c->headquarters_country ?? 'International' }}</strong></div>
                        <div class="truncate">Contact: <span class="text-slate-400">{{ $c->contact_email ?? 'support@carrier.com' }}</span></div>
                        @if($c->website)
                            <div class="truncate"><a href="{{ $c->website }}" target="_blank" class="text-teal-300 hover:text-amber-400 transition-colors">{{ $c->website }}</a></div>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t border-white/10 flex items-center justify-between text-xs font-mono">
                    <span class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold">Active Manifests</span>
                    <span class="font-bold text-white text-sm">{{ $c->shipments_count ?? 0 }} bookings</span>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Add Carrier Modal -->
<div id="add-carrier-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-[#0B1728] border border-white/10 rounded-2xl max-w-md w-full p-6 text-slate-100 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
            <h3 class="text-base font-bold text-white tracking-tight">Register Carrier Partner</h3>
            <button onclick="document.getElementById('add-carrier-modal').classList.add('hidden')" class="text-slate-400 hover:text-white text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('operations.carriers.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Carrier Code (e.g. MAEU, DHL) *</label>
                <input type="text" name="code" required placeholder="MAEU" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Full Carrier Name *</label>
                <input type="text" name="name" required placeholder="Maersk Line" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Mode *</label>
                    <select name="mode" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                        <option value="SEA">SEA</option>
                        <option value="AIR">AIR</option>
                        <option value="LAND">LAND</option>
                        <option value="MULTIMODAL">MULTIMODAL</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Headquarters</label>
                    <input type="text" name="headquarters_country" placeholder="Denmark" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Contact Email</label>
                <input type="email" name="contact_email" placeholder="ops@carrier.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Official Website</label>
                <input type="url" name="website" placeholder="https://www.carrier.com" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
            </div>

            <div class="pt-3 border-t border-white/10 flex items-center justify-end space-x-2">
                <button type="button" onclick="document.getElementById('add-carrier-modal').classList.add('hidden')" class="px-3.5 py-2 rounded-xl text-slate-400 hover:text-white transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold transition-all duration-200 hover:scale-[1.02] shadow-md">
                    Register Carrier &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

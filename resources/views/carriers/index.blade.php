@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    Ocean Container Fleet
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 font-mono">
                    {{ count($carriers) }} Shipping Lines
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Global container carriers, TEU capacity allocation, and maritime liner fleets across oceanic trade lanes.
            </p>
        </div>

        @if(auth()->user()->role === 'admin')
            <button type="button" onclick="document.getElementById('add-carrier-modal').classList.remove('hidden')" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/20">
                <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Register Carrier
            </button>
        @endif
    </div>

    <!-- Carriers Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($carriers as $c)
            <x-card class="flex flex-col justify-between space-y-4 hover:border-cyan-500/40 transition-all duration-200 hover:scale-[1.01]">
                <div class="space-y-3">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-xs font-mono font-bold text-amber-500 dark:text-amber-400 block">{{ $c->code }}</span>
                            <h3 class="text-lg font-bold theme-heading tracking-tight">{{ $c->name }}</h3>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider font-mono bg-blue-500/15 text-blue-600 dark:text-blue-300 border border-blue-500/30">
                            {{ $c->mode }}
                        </span>
                    </div>

                    <div class="text-xs theme-text-primary space-y-1.5 pt-2 border-t theme-border">
                        <div class="flex justify-between">
                            <span class="theme-text-muted">Headquarters:</span>
                            <span class="font-semibold theme-heading">{{ $c->headquarters_country ?? 'International' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="theme-text-muted">Contact:</span>
                            <span class="theme-text-secondary font-mono">{{ $c->contact_email ?? 'ops@carrier.com' }}</span>
                        </div>
                        @if($c->website)
                            <div class="flex justify-between">
                                <span class="theme-text-muted">Website:</span>
                                <a href="{{ $c->website }}" target="_blank" class="text-cyan-600 dark:text-cyan-400 hover:underline font-mono truncate max-w-[180px]">{{ $c->website }}</a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t theme-border flex items-center justify-between text-xs font-mono">
                    <span class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold">Active Manifests</span>
                    <span class="font-bold theme-heading text-sm">{{ $c->shipments_count ?? 0 }} Bookings</span>
                </div>
            </x-card>
        @endforeach
    </div>
</div>

<!-- Add Carrier Modal -->
<div id="add-carrier-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="theme-card border theme-border rounded-2xl max-w-md w-full p-6 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between border-b theme-border pb-3">
            <h3 class="text-base font-bold theme-heading tracking-tight">Register Carrier Partner</h3>
            <button onclick="document.getElementById('add-carrier-modal').classList.add('hidden')" class="theme-text-muted hover:theme-heading text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('operations.carriers.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Carrier Code (e.g. MAEU) *</label>
                <input type="text" name="code" required placeholder="MAEU" class="w-full px-3.5 py-2.5 rounded-xl theme-input border font-mono uppercase focus:ring-1 focus:ring-cyan-500">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Carrier Company Name *</label>
                <input type="text" name="name" required placeholder="A.P. Moller - Maersk" class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Transport Mode *</label>
                <select name="mode" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500 font-mono">
                    <option value="SEA" selected>SEA (Maritime Shipping)</option>
                    <option value="AIR">AIR (Air Cargo Freight)</option>
                    <option value="LAND">LAND (Rail / Intermodal)</option>
                    <option value="MULTIMODAL">MULTIMODAL</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Headquarters Country</label>
                <input type="text" name="headquarters_country" placeholder="Denmark" class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Contact Email</label>
                <input type="email" name="contact_email" placeholder="operations@maersk.com" class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500">
            </div>

            <div class="space-y-1.5">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Website URL</label>
                <input type="url" name="website" placeholder="https://www.maersk.com" class="w-full px-3.5 py-2.5 rounded-xl theme-input border focus:ring-1 focus:ring-cyan-500">
            </div>

            <div class="pt-2 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('add-carrier-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl theme-text-muted hover:theme-heading border theme-border font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold transition">
                    Save Carrier
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

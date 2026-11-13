@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs text-slate-400 font-mono">
            <a href="{{ route('dashboard') }}" class="hover:text-teal-300 transition-colors">Platform</a>
            <span>&rarr;</span>
            <span class="text-teal-300">System Settings</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white font-sans">
            Platform Operations Settings
        </h1>
        <p class="text-xs sm:text-sm text-slate-400">
            Configure dynamic delay severity thresholds and trigger fleet-wide ETA re-evaluation.
        </p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            <div class="font-bold">Error updating settings:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Settings Form (2 cols) -->
        <div class="md:col-span-2 space-y-6">
            <x-card class="space-y-6">
                <div>
                    <h2 class="text-base font-bold text-white">ETA Delay Severity Thresholds</h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Define hour variances that classify a shipment into LOW, MEDIUM, or HIGH delay risk.
                    </p>
                </div>

                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-5 text-xs">
                    @csrf

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">
                            LOW Severity Boundary (Hours) *
                        </label>
                        <input type="number" step="0.5" name="delay_low_hours" value="{{ old('delay_low_hours', $lowThreshold) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                        <span class="text-[11px] text-slate-500 block">
                            Delays &gt; 0h up to this threshold will be tagged as <strong>LOW</strong> severity.
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">
                            MEDIUM Severity Boundary (Hours) *
                        </label>
                        <input type="number" step="0.5" name="delay_medium_hours" value="{{ old('delay_medium_hours', $mediumThreshold) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-amber-500">
                        <span class="text-[11px] text-slate-500 block">
                            Delays above LOW up to this threshold will be tagged as <strong>MEDIUM</strong>. Beyond this is <strong>HIGH</strong>.
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">
                            Terminal Free Time Allowance (Days After Arrival) *
                        </label>
                        <input type="number" min="0" step="1" name="free_days_after_arrival" value="{{ old('free_days_after_arrival', $freeDays) }}" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-blue-500">
                        <span class="text-[11px] text-slate-500 block">
                            Standard calendar days granted after port arrival before container is flagged as exceeding free-time limit (default: 5 days).
                        </span>
                    </div>

                    <div class="pt-4 border-t border-white/10 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-md shadow-blue-500/20">
                            Save Settings
                        </button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Re-evaluate Active Shipments Panel (1 col) -->
        <div class="space-y-6">
            <x-card class="space-y-4">
                <div>
                    <h3 class="text-sm font-bold theme-heading tracking-tight font-sans">Active tracking status</h3>
                    <div class="mt-3 space-y-2 text-xs font-mono">
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex justify-between">
                            <span class="text-slate-400">Active Shipments:</span>
                            <span class="font-bold text-white">{{ $activeShipmentsCount }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 flex justify-between">
                            <span class="text-slate-400">Currently Delayed:</span>
                            <span class="font-bold text-amber-400">{{ $delayedShipmentsCount }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-white/10 space-y-3">
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Re-evaluate ETA variances for all active (non-delivered) shipments against the newly configured thresholds.
                    </p>
                    <form action="{{ route('admin.settings.reevaluate') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-amber-500/20">
                            Re-Evaluate Active Shipments
                        </button>
                    </form>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection


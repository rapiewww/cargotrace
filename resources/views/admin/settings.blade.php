@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs theme-text-muted font-mono">
            <a href="{{ route('dashboard') }}" class="hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">{{ __('Platform') }}</a>
            <span>&rarr;</span>
            <span class="text-blue-600 dark:text-cyan-400">{{ __('System Settings') }}</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
            {{ __('Platform Operations Settings') }}
        </h1>
        <p class="text-xs sm:text-sm theme-text-secondary">
            {{ __('Configure dynamic per-mode delay severity thresholds and trigger fleet-wide ETA re-evaluation.') }}
        </p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs space-y-1">
            <div class="font-bold">{{ __('Error updating settings:') }}</div>
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
                    <h2 class="text-base font-bold theme-heading">{{ __('ETA Delay Severity Thresholds (Per Mode)') }}</h2>
                    <p class="text-xs theme-text-secondary mt-0.5">
                        {{ __('Define hour variances that classify consignments into LOW, MEDIUM, or HIGH delay risk by transport mode.') }}
                    </p>
                </div>

                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6 text-xs">
                    @csrf

                    <!-- SEA THRESHOLDS CARD -->
                    <div class="p-4 rounded-xl theme-card-elevated border theme-border space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold theme-heading uppercase tracking-wider text-[11px] flex items-center gap-2">
                                <x-mode-icon mode="SEA" class="w-4 h-4 text-blue-600 dark:text-cyan-400" />
                                <span>{{ __('Sea Freight (SEA)') }}</span>
                            </span>
                            <span class="text-[10px] theme-text-muted font-mono">{{ __('Default:') }} 12h / 48h</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label for="delay_sea_low_hours" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">
                                    {{ __('Low threshold (hours)') }} *
                                </label>
                                <div class="relative">
                                    <input id="delay_sea_low_hours" type="number" step="0.5" name="delay_sea_low_hours" value="{{ old('delay_sea_low_hours', $seaThresholds['low']) }}" required class="w-full px-3 py-2 pr-14 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                    <span class="absolute right-3 top-2 theme-text-muted text-xs font-mono">{{ __('hours') }}</span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label for="delay_sea_medium_hours" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">
                                    {{ __('Medium threshold (hours)') }} *
                                </label>
                                <div class="relative">
                                    <input id="delay_sea_medium_hours" type="number" step="0.5" name="delay_sea_medium_hours" value="{{ old('delay_sea_medium_hours', $seaThresholds['medium']) }}" required class="w-full px-3 py-2 pr-14 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                    <span class="absolute right-3 top-2 theme-text-muted text-xs font-mono">{{ __('hours') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AIR THRESHOLDS CARD -->
                    <div class="p-4 rounded-xl theme-card-elevated border theme-border space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold theme-heading uppercase tracking-wider text-[11px] flex items-center gap-2">
                                <x-mode-icon mode="AIR" class="w-4 h-4 text-sky-600 dark:text-sky-400" />
                                <span>{{ __('Air Freight (AIR)') }}</span>
                            </span>
                            <span class="text-[10px] theme-text-muted font-mono">{{ __('Default:') }} 3h / 12h</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label for="delay_air_low_hours" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">
                                    {{ __('Low threshold (hours)') }} *
                                </label>
                                <div class="relative">
                                    <input id="delay_air_low_hours" type="number" step="0.5" name="delay_air_low_hours" value="{{ old('delay_air_low_hours', $airThresholds['low']) }}" required class="w-full px-3 py-2 pr-14 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                    <span class="absolute right-3 top-2 theme-text-muted text-xs font-mono">{{ __('hours') }}</span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label for="delay_air_medium_hours" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">
                                    {{ __('Medium threshold (hours)') }} *
                                </label>
                                <div class="relative">
                                    <input id="delay_air_medium_hours" type="number" step="0.5" name="delay_air_medium_hours" value="{{ old('delay_air_medium_hours', $airThresholds['medium']) }}" required class="w-full px-3 py-2 pr-14 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                    <span class="absolute right-3 top-2 theme-text-muted text-xs font-mono">{{ __('hours') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ROAD THRESHOLDS CARD -->
                    <div class="p-4 rounded-xl theme-card-elevated border theme-border space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold theme-heading uppercase tracking-wider text-[11px] flex items-center gap-2">
                                <x-mode-icon mode="ROAD" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                <span>{{ __('Road Freight (ROAD)') }}</span>
                            </span>
                            <span class="text-[10px] theme-text-muted font-mono">{{ __('Default:') }} 6h / 24h</span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label for="delay_road_low_hours" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">
                                    {{ __('Low threshold (hours)') }} *
                                </label>
                                <div class="relative">
                                    <input id="delay_road_low_hours" type="number" step="0.5" name="delay_road_low_hours" value="{{ old('delay_road_low_hours', $roadThresholds['low']) }}" required class="w-full px-3 py-2 pr-14 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                    <span class="absolute right-3 top-2 theme-text-muted text-xs font-mono">{{ __('hours') }}</span>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label for="delay_road_medium_hours" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">
                                    {{ __('Medium threshold (hours)') }} *
                                </label>
                                <div class="relative">
                                    <input id="delay_road_medium_hours" type="number" step="0.5" name="delay_road_medium_hours" value="{{ old('delay_road_medium_hours', $roadThresholds['medium']) }}" required class="w-full px-3 py-2 pr-14 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                    <span class="absolute right-3 top-2 theme-text-muted text-xs font-mono">{{ __('hours') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-1.5 pt-2 border-t theme-border">
                        <label for="free_days_after_arrival" class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">
                            {{ __('Terminal Free Time Allowance (Days After Arrival)') }} *
                        </label>
                        <input id="free_days_after_arrival" type="number" min="0" step="1" name="free_days_after_arrival" value="{{ old('free_days_after_arrival', $freeDays) }}" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <span class="text-[11px] theme-text-muted block">
                            {{ __('Standard calendar days granted after arrival before container/cargo is flagged as exceeding free-time limit (default: 5 days).') }}
                        </span>
                    </div>

                    <div class="pt-4 border-t theme-border flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs transition shadow-md shadow-blue-500/20 cursor-pointer">
                            {{ __('Save Settings') }}
                        </button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Re-evaluate Active Shipments Panel (1 col) -->
        <div class="space-y-6">
            <x-card class="space-y-4">
                <div>
                    <h3 class="text-sm font-bold theme-heading tracking-tight font-sans">{{ __('Active tracking status') }}</h3>
                    <div class="mt-3 space-y-2 text-xs font-mono">
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border flex justify-between">
                            <span class="theme-text-secondary">{{ __('Active Shipments:') }}</span>
                            <span class="font-bold theme-heading">{{ $activeShipmentsCount }}</span>
                        </div>
                        <div class="p-3 rounded-xl theme-card-elevated border theme-border flex justify-between">
                            <span class="theme-text-secondary">{{ __('Currently Delayed:') }}</span>
                            <span class="font-bold text-amber-700 dark:text-amber-400">{{ $delayedShipmentsCount }}</span>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t theme-border space-y-3">
                    <p class="text-[11px] theme-text-secondary leading-relaxed">
                        {{ __('Re-evaluate ETA variances for all active (non-delivered) shipments against the newly configured thresholds.') }}
                    </p>
                    <form action="{{ route('admin.settings.reevaluate') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-amber-500/20 cursor-pointer">
                            {{ __('Re-Evaluate Active Shipments') }}
                        </button>
                    </form>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection

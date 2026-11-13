@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-[1600px] mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
                    Import preview and verification
                </h1>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20 font-mono">
                    {{ $results['valid_count'] }} of {{ $results['total_rows'] }} valid
                </span>
            </div>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Review parsed consignment data and validation results before committing records to the database.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('shipments.import.index') }}" class="px-3.5 py-2 rounded-xl theme-card-elevated theme-text-secondary hover:theme-heading border theme-border text-xs font-semibold transition">
                Upload different file
            </a>
            @if($results['valid_count'] > 0)
                <form action="{{ route('shipments.import.confirm') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-blue-500/25 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Confirm and import {{ $results['valid_count'] }} {{ $results['valid_count'] === 1 ? 'shipment' : 'shipments' }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-card class="p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider theme-text-muted">Total evaluated</p>
                    <p class="text-2xl font-black theme-heading font-mono mt-1">{{ $results['total_rows'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center text-blue-600 dark:text-cyan-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            </div>
            <p class="text-[11px] theme-text-secondary mt-2">Manifest records in uploaded CSV.</p>
        </x-card>

        <x-card class="p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Ready to import</p>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">{{ $results['valid_count'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <p class="text-[11px] theme-text-secondary mt-2">Passed all ISO 6346 and relational checks.</p>
        </x-card>

        <x-card class="p-4 sm:p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400">Omitted with errors</p>
                    <p class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono mt-1">{{ $results['invalid_count'] }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
            </div>
            <p class="text-[11px] theme-text-secondary mt-2">Will be excluded from the database transaction.</p>
        </x-card>
    </div>

    <!-- Valid Rows Section -->
    @if($results['valid_count'] > 0)
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold theme-heading flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    Verified rows ready to commit ({{ $results['valid_count'] }})
                </h2>
                <span class="text-xs font-mono theme-text-muted">Transaction scope: 1 atomic commit</span>
            </div>

            <x-card :padded="false" class="overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="theme-card-elevated theme-text-muted uppercase tracking-wider text-[10px] border-b theme-border font-sans font-semibold">
                            <tr>
                                <th class="py-3 px-4">Line</th>
                                <th class="py-3 px-4">Container ID</th>
                                <th class="py-3 px-4">Tracking number</th>
                                <th class="py-3 px-4">Shipping line</th>
                                <th class="py-3 px-4">Corridor lane</th>
                                <th class="py-3 px-4">Cargo type</th>
                                <th class="py-3 px-4">Departure &rarr; Arrival</th>
                                <th class="py-3 px-4 text-right">Declared value</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y theme-border">
                            @foreach($results['valid_rows'] as $row)
                                <tr class="hover:bg-blue-600/5 transition-colors">
                                    <td class="py-3.5 px-4 theme-text-muted">
                                        #{{ $row['row_number'] }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-blue-600 dark:text-cyan-400">
                                        {{ $row['container_number'] ?? 'Auto-assigned' }}
                                    </td>
                                    <td class="py-3.5 px-4 theme-text-secondary">
                                        {{ $row['tracking_number'] ?? 'Auto-generated' }}
                                    </td>
                                    <td class="py-3.5 px-4 font-sans theme-text-primary">
                                        {{ $row['carrier_name'] }}
                                        <span class="font-mono text-[10px] theme-text-muted block">{{ $row['carrier_code'] }}</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1.5 font-bold theme-text-primary">
                                            <span>{{ $row['origin_unlocode'] }}</span>
                                            <span class="theme-text-muted">&rarr;</span>
                                            <span>{{ $row['destination_unlocode'] }}</span>
                                        </div>
                                        <span class="text-[10px] theme-text-muted block font-sans truncate max-w-xs">
                                            {{ $row['origin_name'] }} to {{ $row['destination_name'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-sans">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold border theme-border theme-card-elevated">
                                            {{ $row['cargo_type'] }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 theme-text-secondary whitespace-nowrap text-[11px]">
                                        <div>{{ substr($row['expected_departure_at'], 0, 10) }}</div>
                                        <div class="theme-text-muted">{{ substr($row['expected_arrival_at'], 0, 10) }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-bold theme-text-primary">
                                        {{ number_format($row['declared_value'], 2) }} {{ $row['currency_code'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    @else
        <div class="p-6 rounded-2xl theme-card-elevated border theme-border text-center space-y-2">
            <p class="text-sm font-bold theme-heading">No valid rows to commit</p>
            <p class="text-xs theme-text-secondary">All rows in the uploaded CSV contained validation errors. Please correct the flagged issues below and try again.</p>
        </div>
    @endif

    <!-- Invalid Rows Section -->
    @if($results['invalid_count'] > 0)
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-rose-600 dark:text-rose-400 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    Flagged rows with validation errors ({{ $results['invalid_count'] }})
                </h2>
                <span class="text-xs theme-text-muted">Excluded from transaction</span>
            </div>

            <x-card :padded="false" class="overflow-hidden border-rose-500/30">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-rose-500/5 text-rose-600 dark:text-rose-400 uppercase tracking-wider text-[10px] border-b theme-border font-sans font-semibold">
                            <tr>
                                <th class="py-3 px-4 w-16">Line</th>
                                <th class="py-3 px-4 w-64">Manifest reference</th>
                                <th class="py-3 px-4">Validation failures</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y theme-border">
                            @foreach($results['invalid_rows'] as $invalid)
                                <tr class="hover:bg-rose-500/5 transition-colors">
                                    <td class="py-3.5 px-4 font-bold text-rose-500">
                                        #{{ $invalid['row_number'] }}
                                    </td>
                                    <td class="py-3.5 px-4 theme-text-secondary font-sans text-xs space-y-1">
                                        <div class="font-mono text-[11px] theme-heading">
                                            {{ $invalid['data']['container_number'] ?? ($invalid['data']['tracking_number'] ?? 'Row '.$invalid['row_number']) }}
                                        </div>
                                        <div class="text-[11px] theme-text-muted font-mono">
                                            Carrier: {{ $invalid['data']['carrier_code'] ?? ($invalid['data']['carrier'] ?? 'N/A') }} | 
                                            Lane: {{ $invalid['data']['origin_unlocode'] ?? ($invalid['data']['origin'] ?? 'N/A') }} &rarr; {{ $invalid['data']['destination_unlocode'] ?? ($invalid['data']['destination'] ?? 'N/A') }}
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-4 space-y-1">
                                        @foreach($invalid['errors'] as $msg)
                                            <div class="text-[11px] text-rose-600 dark:text-rose-400 flex items-start gap-1.5 font-sans">
                                                <span class="text-rose-500 font-bold shrink-0">&bull;</span>
                                                <span>{{ $msg }}</span>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    @endif

    <!-- Footer Controls -->
    <div class="flex items-center justify-between pt-4 border-t theme-border">
        <a href="{{ route('shipments.import.index') }}" class="px-4 py-2 rounded-xl theme-card-elevated theme-text-secondary hover:theme-heading border theme-border text-xs font-semibold transition">
            Cancel and start over
        </a>

        @if($results['valid_count'] > 0)
            <form action="{{ route('shipments.import.confirm') }}" method="POST">
                @csrf
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-2 shadow-md shadow-blue-500/25 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Confirm and import {{ $results['valid_count'] }} {{ $results['valid_count'] === 1 ? 'shipment' : 'shipments' }}
                </button>
            </form>
        @endif
    </div>
</div>
@endsection


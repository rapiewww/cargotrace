@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- Header with Source Status -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b theme-border">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="text-[11px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-400 font-bold px-2 py-0.5 rounded-md bg-cyan-500/10 border border-cyan-500/20">
                    {{ $sourceName }}
                </span>
                <x-data-source-badge :source="$dataSource" />
            </div>
            <h1 class="text-2xl sm:text-3xl font-black theme-heading tracking-tight mt-1.5">
                Maritime Currency FX & Freight Benchmarks
            </h1>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Multi-currency spot valuation, container route TEU/FEU index conversions, and global marine bunker price tracking.
            </p>
        </div>

        <!-- Quick Base Currency Selector -->
        <div class="flex items-center gap-2">
            <span class="text-xs theme-text-secondary font-medium">Base Matrix:</span>
            <div class="flex items-center gap-1.5">
                @foreach(['USD', 'EUR', 'SGD'] as $b)
                    <a
                        href="?base={{ $b }}"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition border {{ $baseCurrency === $b ? 'bg-blue-600 text-white border-blue-600' : 'theme-card-elevated theme-border theme-text-secondary hover:text-white' }}"
                    >
                        {{ $b }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Top Section: Interactive Converter & Spot Freight Presets -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Interactive Two-Way Converter Calculator (7 cols) -->
        <div class="lg:col-span-7 p-6 sm:p-8 rounded-3xl theme-card border space-y-6 transition-colors duration-200">
            <div class="flex items-center justify-between pb-3 border-b theme-border">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Ocean Freight Valuation Calculator</span>
                </div>
                <span class="text-[11px] text-cyan-600 dark:text-cyan-400 font-mono">Exchange Rate Conversion</span>
            </div>

            <!-- Inputs & Swap Button -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-center">
                <div class="sm:col-span-2 space-y-1">
                    <label for="calc-amount" class="text-[11px] uppercase tracking-wider font-semibold theme-text-muted">Amount</label>
                    <input
                        type="number"
                        id="calc-amount"
                        value="2850"
                        oninput="runFullCurrencyCalculation()"
                        class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-sm font-mono font-bold tabular-nums focus:outline-none focus:border-cyan-500"
                    >
                </div>

                <div class="sm:col-span-1 space-y-1">
                    <label for="calc-from" class="text-[11px] uppercase tracking-wider font-semibold theme-text-muted">From</label>
                    <select
                        id="calc-from"
                        onchange="runFullCurrencyCalculation()"
                        class="w-full px-3 py-2.5 rounded-xl theme-input border text-xs font-mono font-bold focus:outline-none"
                    >
                        @foreach($supportedCurrencies as $c)
                            <option value="{{ $c }}" {{ $c === 'USD' ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1 flex items-end justify-center pb-1">
                    <button
                        type="button"
                        onclick="swapFullCurrencies()"
                        class="w-10 h-10 rounded-xl bg-blue-600/15 hover:bg-blue-600 text-blue-600 dark:text-cyan-400 hover:text-white transition flex items-center justify-center font-black text-base shadow-sm"
                        title="Swap Currencies"
                    >
                        &rlarr;
                    </button>
                </div>

                <div class="sm:col-span-1 space-y-1">
                    <label for="calc-to" class="text-[11px] uppercase tracking-wider font-semibold theme-text-muted">To</label>
                    <select
                        id="calc-to"
                        onchange="runFullCurrencyCalculation()"
                        class="w-full px-3 py-2.5 rounded-xl theme-input border text-xs font-mono font-bold focus:outline-none"
                    >
                        @foreach($supportedCurrencies as $c)
                            <option value="{{ $c }}" {{ $c === 'IDR' ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Converted Big Display Output -->
            <div class="p-6 rounded-2xl theme-card-elevated border theme-border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <span class="text-xs theme-text-secondary">Converted Value (Net Equivalent):</span>
                    <span id="full-calc-details" class="text-xs font-mono theme-text-muted block">
                        1 USD = 15,873.00 IDR
                    </span>
                </div>
                <div class="text-left sm:text-right">
                    <span id="full-calc-result" class="text-2xl sm:text-3xl font-black font-mono text-cyan-600 dark:text-cyan-400 tabular-nums">
                        Rp 45,238,050 IDR
                    </span>
                </div>
            </div>

            <!-- Quick Preset Freight Cost Buttons -->
            <div class="space-y-2 pt-2">
                <span class="text-[11px] font-mono uppercase tracking-wider theme-text-muted block">
                    Quick Freight Benchmarks Presets:
                </span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <button
                        type="button"
                        onclick="applyPresetAmount(420, 'USD', 'IDR')"
                        class="p-2.5 rounded-xl theme-card-elevated border theme-border text-left hover:border-cyan-500/50 transition group"
                    >
                        <span class="text-[10px] theme-text-muted block truncate">Feeder 20ft TEU</span>
                        <span class="text-xs font-bold theme-heading font-mono group-hover:text-cyan-400">$420 USD</span>
                    </button>
                    <button
                        type="button"
                        onclick="applyPresetAmount(2850, 'USD', 'IDR')"
                        class="p-2.5 rounded-xl theme-card-elevated border theme-border text-left hover:border-cyan-500/50 transition group"
                    >
                        <span class="text-[10px] theme-text-muted block truncate">Transpacific 40ft</span>
                        <span class="text-xs font-bold theme-heading font-mono group-hover:text-cyan-400">$2,850 USD</span>
                    </button>
                    <button
                        type="button"
                        onclick="applyPresetAmount(2140, 'USD', 'EUR')"
                        class="p-2.5 rounded-xl theme-card-elevated border theme-border text-left hover:border-cyan-500/50 transition group"
                    >
                        <span class="text-[10px] theme-text-muted block truncate">Asia-Europe Loop</span>
                        <span class="text-xs font-bold theme-heading font-mono group-hover:text-cyan-400">$2,140 USD</span>
                    </button>
                    <button
                        type="button"
                        onclick="applyPresetAmount(185, 'USD', 'SGD')"
                        class="p-2.5 rounded-xl theme-card-elevated border theme-border text-left hover:border-cyan-500/50 transition group"
                    >
                        <span class="text-[10px] theme-text-muted block truncate">Terminal Handling</span>
                        <span class="text-xs font-bold theme-heading font-mono group-hover:text-cyan-400">$185 USD</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right: Marine Bunker Fuel Benchmark Index (5 cols) -->
        <div class="lg:col-span-5 p-6 sm:p-8 rounded-3xl theme-card border space-y-5 transition-colors duration-200 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="flex items-center justify-between pb-3 border-b theme-border">
                    <span class="text-xs font-bold uppercase tracking-wider theme-heading">Vessel Bunker Fuel Index</span>
                    <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 font-bold">Global Average</span>
                </div>
                <p class="text-xs theme-text-secondary leading-relaxed">
                    Estimated port bunker fuel averages across Singapore, Rotterdam, and Fujairah maritime bunkering terminals.
                </p>
            </div>

            <div class="space-y-3">
                @foreach($bunkerPrices as $b)
                    <div class="p-4 rounded-2xl theme-card-elevated border theme-border flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold theme-heading block">{{ $b['name'] }}</span>
                            <span class="text-[10px] theme-text-muted font-mono">{{ $b['unit'] }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-base font-extrabold theme-heading font-mono tabular-nums">
                                ${{ number_format($b['price_mt'], 2) }}
                            </span>
                            <span class="text-[10px] font-mono font-bold block {{ str_starts_with($b['change'], '+') ? 'text-rose-500' : 'text-emerald-500' }}">
                                {{ $b['change'] }} 24h
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-3 rounded-xl theme-card-elevated border theme-border text-[11px] theme-text-secondary">
                Notice: Fuel surcharges (BAF) for container charters recalculate weekly based on VLSFO pricing indices.
            </div>
        </div>
    </div>

    <!-- Exchange Cross-Rate Matrix Table -->
    <div class="p-6 sm:p-8 rounded-3xl theme-card border space-y-4 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b theme-border">
            <div>
                <h3 class="text-base font-bold theme-heading">Maritime FX Cross-Rate Matrix</h3>
                <span class="text-xs theme-text-secondary">Official ECB foreign exchange rate conversion grid</span>
            </div>
            <span class="text-[11px] font-mono text-cyan-600 dark:text-cyan-400">
                Base: {{ $baseCurrency }} &bull; Updated Daily
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="border-b theme-border text-[11px] font-mono uppercase text-slate-400">
                        <th class="py-2.5 px-3">Currency</th>
                        @foreach($matrixCurrencies as $col)
                            <th class="py-2.5 px-3 text-right">Per 1 {{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y theme-border font-mono">
                    @foreach($matrixCurrencies as $row)
                        <tr class="hover:bg-slate-800/20 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3 px-3 font-bold theme-heading">
                                1 {{ $row }}
                            </td>
                            @foreach($matrixCurrencies as $col)
                                <td class="py-3 px-3 text-right tabular-nums {{ $row === $col ? 'theme-text-muted font-normal' : 'font-semibold text-cyan-600 dark:text-cyan-400' }}">
                                    {{ $col === 'IDR' ? number_format($crossMatrix[$row][$col] ?? 1, 0) : number_format($crossMatrix[$row][$col] ?? 1, 4) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Global Container Route Spot Freight Benchmarks -->
    <div class="p-6 sm:p-8 rounded-3xl theme-card border space-y-5 transition-colors duration-200">
        <div class="flex items-center justify-between pb-3 border-b theme-border">
            <div>
                <h3 class="text-base font-bold theme-heading">Ocean Container Route Freight Rate Benchmarks</h3>
                <span class="text-xs theme-text-secondary">Spot freight indices along primary East-West and Intra-Asia shipping lanes</span>
            </div>
            <span class="text-xs font-mono theme-text-muted">TEU/FEU Index</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($routeBenchmarks as $rb)
                <div class="p-5 rounded-2xl theme-card-elevated border theme-border space-y-3 hover:border-cyan-500/40 transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold theme-heading">{{ $rb['route_name'] }}</span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded {{ $rb['trend'] === 'up' ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : ($rb['trend'] === 'down' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/15 text-slate-400 border border-slate-500/30') }}">
                            {{ $rb['change_pct'] }}
                        </span>
                    </div>

                    <div class="text-xs font-mono text-cyan-600 dark:text-cyan-400">
                        {!! $rb['corridor'] !!}
                    </div>

                    <div class="flex items-end justify-between pt-2 border-t theme-border">
                        <div>
                            <span class="text-[10px] theme-text-muted block">{{ $rb['mode'] }}</span>
                            <span class="text-xl font-black font-mono theme-heading tabular-nums">
                                ${{ number_format($rb['index_usd']) }} <span class="text-xs font-normal text-slate-400">USD</span>
                            </span>
                        </div>
                        <span class="text-[11px] font-mono theme-text-secondary">
                            {{ $rb['lead_time_days'] }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    const globalFxRates = Object.assign({
        'USD': 1.0,
        'EUR': 0.92,
        'IDR': 15873.0,
        'SGD': 1.34,
        'CNY': 7.23,
        'AUD': 1.52,
        'GBP': 0.79,
        'JPY': 154.20,
        'AED': 3.67
    }, @json($rates ?? []));

    let fullCalcDebounce = null;

    function runFullCurrencyCalculation() {
        const amount = parseFloat(document.getElementById('calc-amount').value) || 0;
        const from = document.getElementById('calc-from').value;
        const to = document.getElementById('calc-to').value;

        const rFrom = globalFxRates[from] || 1.0;
        const rTo = globalFxRates[to] || 1.0;

        // Instant optimistic preview
        const converted = (amount / rFrom) * rTo;
        const unitRate = (1.0 / rFrom) * rTo;

        const formatted = converted.toLocaleString(undefined, {
            minimumFractionDigits: to === 'IDR' || to === 'JPY' ? 0 : 2,
            maximumFractionDigits: to === 'IDR' || to === 'JPY' ? 0 : 2
        });

        const formattedUnit = unitRate.toLocaleString(undefined, {
            minimumFractionDigits: to === 'IDR' ? 0 : 4,
            maximumFractionDigits: to === 'IDR' ? 0 : 4
        });

        document.getElementById('full-calc-result').textContent = `${to === 'IDR' ? 'Rp ' : ''}${formatted} ${to}`;
        document.getElementById('full-calc-details').textContent = `1 ${from} = ${formattedUnit} ${to}`;

        // Debounced live fetch
        clearTimeout(fullCalcDebounce);
        fullCalcDebounce = setTimeout(() => {
            fetch(`/api/currency/convert?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&amount=${encodeURIComponent(amount)}`)
                .then(res => res.ok ? res.json() : null)
                .then(json => {
                    const data = json && json.data ? json.data : json;
                    if (data && (data.formatted_result || data.formatted_converted)) {
                        document.getElementById('full-calc-result').textContent = data.formatted_result || data.formatted_converted;
                        if (data.rate) {
                            document.getElementById('full-calc-details').textContent = `1 ${from} = ${data.rate.toLocaleString()} ${to}`;
                        }
                    }
                })
                .catch(() => {});
        }, 300);
    }

    function swapFullCurrencies() {
        const fromSel = document.getElementById('calc-from');
        const toSel = document.getElementById('calc-to');
        const tmp = fromSel.value;
        fromSel.value = toSel.value;
        toSel.value = tmp;
        runFullCurrencyCalculation();
    }

    function applyPresetAmount(amount, from, to) {
        document.getElementById('calc-amount').value = amount;
        document.getElementById('calc-from').value = from;
        document.getElementById('calc-to').value = to;
        runFullCurrencyCalculation();
    }

    document.addEventListener('DOMContentLoaded', () => {
        runFullCurrencyCalculation();
    });
</script>
@endsection


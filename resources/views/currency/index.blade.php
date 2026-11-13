@extends('layouts.app')

@section('content')
<div class="p-6 sm:p-8 space-y-8 max-w-[1600px] mx-auto">
    <!-- Header with Source Status & Provider Attribution -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b theme-border">
        <div>
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-xs font-medium text-cyan-600 dark:text-cyan-400 px-2 py-0.5 rounded-md bg-cyan-500/10 border border-cyan-500/20">
                    Daily reference rates
                </span>
                <x-data-source-badge :source="$dataSource" />
                @if($timeLastUpdateUtc)
                    <span class="text-xs theme-text-muted">
                        Updated {{ $timeLastUpdateUtc }}
                    </span>
                @endif
                <a
                    href="{{ $providerUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-xs theme-text-muted hover:text-cyan-500 transition underline underline-offset-2 flex items-center gap-1 font-sans"
                >
                    <span>{{ $attributionText }}</span>
                    <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black theme-heading tracking-tight mt-1.5">
                <span class="sr-only">Maritime Currency FX &bull; Ocean Freight Valuation Calculator &bull; </span>Currency Converter & Reference Rates
            </h1>
            <p class="text-xs sm:text-sm theme-text-secondary mt-1">
                Global sovereign currency valuations for maritime bills of lading, manifest reconciliation, and container fleet portfolio valuation.
            </p>
        </div>

        <!-- Base Matrix Quick Switcher -->
        <div class="flex items-center gap-2">
            <span class="text-xs theme-text-secondary font-medium">Base matrix:</span>
            <div class="flex items-center gap-1.5">
                @foreach(['USD', 'EUR', 'SGD'] as $b)
                    <a
                        href="?base={{ $b }}@if(request('matrix'))&matrix={{ request('matrix') }}@endif"
                        class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold transition border {{ $baseCurrency === $b ? 'bg-blue-600 text-white border-blue-600' : 'theme-card-elevated theme-border theme-text-secondary hover:theme-heading hover:border-blue-500' }}"
                    >
                        {{ $b }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($error)
        <!-- Failure State Error Banner -->
        <div class="p-6 rounded-2xl bg-amber-500/10 border border-dashed border-amber-500/40 text-amber-900 dark:text-amber-200 space-y-3">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider">Exchange Rate Service Notice</h3>
                    <p class="text-xs mt-0.5">{{ $error }}</p>
                </div>
            </div>
            <div class="pt-2">
                <button
                    type="button"
                    onclick="window.location.reload()"
                    class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold transition shadow-sm"
                >
                    Retry Connection
                </button>
            </div>
        </div>
    @endif

    <!-- Top Section: Interactive Multi-Currency Converter & Portfolio Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- Left: Interactive Searchable Converter & 30-Day History Chart (7 cols) -->
        <div class="lg:col-span-7 p-6 sm:p-8 rounded-3xl theme-card border space-y-6 transition-colors duration-200">
            <div class="flex items-center justify-between pb-3 border-b theme-border">
                <div class="space-y-0.5">
                    <span class="text-xs font-semibold theme-heading">Currency converter</span>
                    <span class="text-xs theme-text-secondary block">Convert consignments across world currencies with daily reference rates</span>
                </div>
                <span class="text-xs text-cyan-600 dark:text-cyan-400 font-medium">Daily reference rates</span>
            </div>

            <!-- Inputs & Swap Button with Searchable Comboboxes -->
            <div class="space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <!-- Amount Input (4 cols) -->
                    <div class="sm:col-span-4 space-y-1">
                        <label for="calc-amount" class="text-xs font-medium theme-text-secondary block">Amount</label>
                        <input
                            type="number"
                            id="calc-amount"
                            value="2850"
                            min="0"
                            step="any"
                            oninput="runFullCurrencyCalculation()"
                            class="w-full px-3.5 py-2.5 rounded-xl theme-input border text-sm font-mono font-bold tabular-nums focus:outline-none focus:border-cyan-500"
                        >
                    </div>

                    <!-- From Combobox (3.5 cols) -->
                    <div class="sm:col-span-3 space-y-1 relative">
                        <label for="from-search" class="text-xs font-medium theme-text-secondary block">From</label>
                        <div class="relative">
                            <input
                                type="text"
                                id="from-search"
                                value="USD"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-autocomplete="list"
                                aria-controls="from-dropdown"
                                class="w-full px-3 py-2.5 rounded-xl theme-input border text-xs font-mono font-bold focus:outline-none cursor-pointer uppercase"
                                onclick="toggleCombobox('from')"
                                oninput="filterCombobox('from')"
                            >
                            <div id="from-dropdown" role="listbox" class="absolute z-50 left-0 right-0 top-full mt-1 max-h-60 overflow-y-auto rounded-xl theme-card-elevated border theme-border shadow-2xl hidden">
                                <!-- Populated dynamically via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Swap Button (1.5 cols) -->
                    <div class="sm:col-span-1 flex items-center justify-center pb-0.5">
                        <button
                            type="button"
                            onclick="swapFullCurrencies()"
                            class="w-10 h-10 rounded-xl bg-blue-600/15 hover:bg-blue-600 text-blue-600 dark:text-cyan-400 hover:text-white transition flex items-center justify-center font-black text-base shadow-sm"
                            title="Swap Currencies"
                            aria-label="Swap Currencies"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </button>
                    </div>

                    <!-- To Combobox (3.5 cols) -->
                    <div class="sm:col-span-4 space-y-1 relative">
                        <label for="to-search" class="text-xs font-medium theme-text-secondary block">To</label>
                        <div class="relative">
                            <input
                                type="text"
                                id="to-search"
                                value="IDR"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-autocomplete="list"
                                aria-controls="to-dropdown"
                                class="w-full px-3 py-2.5 rounded-xl theme-input border text-xs font-mono font-bold focus:outline-none cursor-pointer uppercase"
                                onclick="toggleCombobox('to')"
                                oninput="filterCombobox('to')"
                            >
                            <div id="to-dropdown" role="listbox" class="absolute z-50 left-0 right-0 top-full mt-1 max-h-60 overflow-y-auto rounded-xl theme-card-elevated border theme-border shadow-2xl hidden">
                                <!-- Populated dynamically via JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Pairs List (localStorage) -->
                <div id="recent-pairs-container"></div>
            </div>

            <!-- Converted Big Display Output -->
            <div class="p-6 rounded-2xl theme-card-elevated border theme-border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="space-y-1">
                    <span class="text-xs theme-text-secondary">Converted Consignment Valuation:</span>
                    <span id="full-calc-details" class="text-xs font-mono font-bold text-slate-400 dark:text-slate-300 block">
                        1 USD = 15,873.00 IDR
                    </span>
                    <span class="text-[10px] theme-text-muted font-mono block">
                        Net equivalent valuation at latest reference rates
                    </span>
                </div>
                <div class="text-left sm:text-right">
                    <span id="full-calc-result" class="text-2xl sm:text-3xl font-black font-mono text-cyan-600 dark:text-cyan-400 tabular-nums block">
                        Rp 45,238,050
                    </span>
                    <span id="full-calc-currency-name" class="text-[11px] theme-text-muted font-sans font-medium block">
                        Indonesian Rupiah
                    </span>
                </div>
            </div>

            <!-- 30-Day Historical Rate Trend (Frankfurter API via Chart.js) -->
            <div id="history-chart-card" class="space-y-3 pt-2">
                <div class="flex items-center justify-between pb-2 border-b theme-border">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold theme-heading">30-Day reference rate trend</span>
                        <span class="text-xs text-cyan-600 dark:text-cyan-400 font-medium">ECB via Frankfurter</span>
                    </div>
                    <span id="history-pair-label" class="text-xs font-mono theme-text-muted">USD / EUR</span>
                </div>
                <div class="relative h-48 w-full">
                    <canvas id="currencyHistoryChart"></canvas>
                </div>
            </div>

            <div id="history-unavailable-notice" class="hidden p-3 rounded-xl theme-card-elevated border theme-border text-xs theme-text-secondary text-center">
                Historical 30-day ECB chart is available for major currency pairs (EUR, USD, GBP, JPY, CNY, AUD, CAD, CHF, etc.).
            </div>
        </div>

        <!-- Right: Authentic Portfolio Value by Currency (5 cols) -->
        <div class="lg:col-span-5 p-6 sm:p-8 rounded-3xl theme-card border space-y-6 transition-colors duration-200">
            <div class="space-y-1 pb-3 border-b theme-border">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold theme-heading">Portfolio value by currency</span>
                    <span class="text-xs font-medium text-cyan-600 dark:text-cyan-400">
                        {{ auth()->user()->role === 'customer' ? 'My Shipments' : 'Global Consignments' }}
                    </span>
                </div>
                <p class="text-xs theme-text-secondary">
                    Aggregated declared manifest valuation of your consignments grouped by original sovereign currency.
                </p>
            </div>

            <!-- Portfolio Summary KPI -->
            <div class="p-4 rounded-2xl theme-card-elevated border theme-border flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-400 font-medium block">Total converted value</span>
                    <span class="text-2xl font-black font-mono text-blue-600 dark:text-cyan-400 tabular-nums">
                        ${{ number_format($totalPortfolioUsd, 2) }} <span class="text-xs text-slate-400 font-sans">USD</span>
                    </span>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400 font-medium block">Consignments</span>
                    <span class="text-lg font-bold font-mono theme-heading tabular-nums">
                        {{ $totalShipmentsCount }}
                    </span>
                </div>
            </div>

            <!-- Currency Breakdown List -->
            @if($portfolioByCurrency->isEmpty())
                <div class="p-8 text-center rounded-2xl theme-card-elevated border theme-border space-y-2">
                    <span class="text-xs theme-text-secondary block">No consignments on record in your portfolio.</span>
                    @if(in_array(auth()->user()->role, ['admin', 'operator']))
                        <a href="{{ route('shipments.create') }}" class="inline-block mt-2 px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition">
                            Create First Consignment &rarr;
                        </a>
                    @endif
                </div>
            @else
                <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                    @foreach($portfolioByCurrency as $row)
                        @php
                            $sharePct = $totalPortfolioUsd > 0 ? round(($row->total_usd / $totalPortfolioUsd) * 100, 1) : 0;
                        @endphp
                        <div class="p-3.5 rounded-2xl theme-card-elevated border theme-border space-y-2">
                            <div class="flex items-center justify-between text-xs font-mono">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg bg-blue-600/15 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-xs">
                                        {{ $row->currency_code }}
                                    </span>
                                    <div>
                                        <span class="font-bold theme-heading block">{{ $row->currency_code }}</span>
                                        <span class="text-[10px] theme-text-muted">{{ $row->shipments_count }} {{ Str::plural('shipment', $row->shipments_count) }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold theme-heading block tabular-nums">
                                        {{ number_format($row->total_declared, 2) }} {{ $row->currency_code }}
                                    </span>
                                    <span class="text-[10px] text-cyan-600 dark:text-cyan-400 block tabular-nums">
                                        ${{ number_format($row->total_usd, 2) }} USD ({{ $sharePct }}%)
                                    </span>
                                </div>
                            </div>

                            <!-- Share Progress Bar -->
                            <div class="w-full h-1.5 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full bg-blue-600 dark:bg-cyan-400 transition-all duration-500" style="width: {{ $sharePct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="p-3 rounded-xl theme-card-elevated border theme-border text-[11px] theme-text-secondary">
                <span>All values converted at current official exchange reference rates with database audit integrity.</span>
            </div>
        </div>

    </div>

    <!-- Bottom Section: Cross-Rate Matrix with User Currencies Selection -->
    <div class="p-6 sm:p-8 rounded-3xl theme-card border space-y-5 transition-colors duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b theme-border">
            <div>
                <h3 class="text-base font-bold theme-heading"><span class="sr-only">Maritime FX Cross-Rate Matrix &bull; </span>Maritime cross-rate matrix</h3>
                <span class="text-xs theme-text-secondary">Select 3 to 8 currencies to configure the multi-currency conversion grid</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono text-cyan-600 dark:text-cyan-400 font-medium">
                    Matrix base: {{ $baseCurrency }}
                </span>
            </div>
        </div>

        <!-- Matrix Currency Selector Pills (Pick 3 - 8) -->
        <div class="space-y-2">
            <span class="text-xs text-slate-400 block font-medium">
                Included matrix currencies (pick 3 to 8):
            </span>
            <div class="flex flex-wrap items-center gap-2">
                @php
                    $availablePills = array_unique(array_merge($pinnedCurrencies, $matrixCurrencies));
                @endphp
                @foreach($availablePills as $cur)
                    @php
                        $isSelected = in_array($cur, $matrixCurrencies, true);
                    @endphp
                    <button
                        type="button"
                        onclick="toggleMatrixCurrency('{{ $cur }}')"
                        class="px-3 py-1 rounded-xl text-xs font-mono font-bold transition border {{ $isSelected ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'theme-card-elevated theme-border theme-text-secondary hover:theme-heading hover:border-blue-500' }}"
                    >
                        {{ $cur }} {{ $isSelected ? '✓' : '+' }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Cross-Rate Table -->
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
                        <tr class="hover:bg-slate-800/10 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3 px-3 font-bold theme-heading">
                                1 {{ $row }}
                            </td>
                            @foreach($matrixCurrencies as $col)
                                <td class="py-3 px-3 text-right tabular-nums {{ $row === $col ? 'text-slate-400 font-normal bg-slate-500/5' : 'font-semibold text-cyan-600 dark:text-cyan-400' }}">
                                    {{ in_array($col, ['IDR', 'JPY', 'KRW', 'VND'], true) ? number_format($crossMatrix[$row][$col] ?? 1, 0) : number_format($crossMatrix[$row][$col] ?? 1, 4) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Vanilla JS Multi-Currency Combobox & Interactive Chart --}}
<script>
    const allCurrencyCodes = @json($allCurrencies);
    const pinnedCurrencies = @json($pinnedCurrencies);
    const globalRates = @json($rates);
    const frankfurterCurrencies = @json($frankfurterCurrencies);
    let activeMatrixCurrencies = @json($matrixCurrencies);

    let displayNames;
    try {
        displayNames = new Intl.DisplayNames(['en'], { type: 'currency' });
    } catch (e) {
        displayNames = { of: (c) => c };
    }

    function getCurrencyFullName(code) {
        try {
            return displayNames.of(code) || code;
        } catch (e) {
            return code;
        }
    }

    function renderDropdownOptions(type, filterText = '') {
        const dropdown = document.getElementById(type + '-dropdown');
        if (!dropdown) return;

        const currentVal = (document.getElementById(type + '-search')?.value || '').toUpperCase();
        const query = filterText.trim().toLowerCase();
        let html = '';

        // Pinned Section
        const matchingPinned = pinnedCurrencies.filter(c => {
            if (!allCurrencyCodes.includes(c)) return false;
            const name = getCurrencyFullName(c).toLowerCase();
            return c.toLowerCase().includes(query) || name.includes(query);
        });

        if (matchingPinned.length > 0) {
            html += `<div class="px-3 py-1.5 text-xs font-semibold theme-text-muted bg-slate-500/10">Popular</div>`;
            matchingPinned.forEach(c => {
                const isSelected = c === currentVal;
                html += `
                    <div role="option" tabindex="-1" data-code="${c}" aria-selected="${isSelected}" onclick="selectCurrency('${type}', '${c}')" class="combobox-opt px-3 py-2 text-xs font-mono flex items-center justify-between hover:bg-blue-600 hover:text-white cursor-pointer transition ${isSelected ? 'bg-blue-600/15 text-blue-600 dark:text-cyan-400 font-bold' : ''}">
                        <span class="font-bold">${c}</span>
                        <span class="text-[11px] truncate max-w-[140px] opacity-75 font-sans">${getCurrencyFullName(c)}</span>
                    </div>
                `;
            });
        }

        // All World Currencies Section
        const matchingAll = allCurrencyCodes.filter(c => {
            const name = getCurrencyFullName(c).toLowerCase();
            return c.toLowerCase().includes(query) || name.includes(query);
        });

        if (matchingAll.length > 0) {
            html += `<div class="px-3 py-1.5 text-xs font-semibold theme-text-muted bg-slate-500/10">All currencies (${matchingAll.length})</div>`;
            matchingAll.forEach(c => {
                const isSelected = c === currentVal;
                html += `
                    <div role="option" tabindex="-1" data-code="${c}" aria-selected="${isSelected}" onclick="selectCurrency('${type}', '${c}')" class="combobox-opt px-3 py-2 text-xs font-mono flex items-center justify-between hover:bg-blue-600 hover:text-white cursor-pointer transition ${isSelected ? 'bg-blue-600/15 text-blue-600 dark:text-cyan-400 font-bold' : ''}">
                        <span class="font-bold">${c}</span>
                        <span class="text-[11px] truncate max-w-[140px] opacity-75 font-sans">${getCurrencyFullName(c)}</span>
                    </div>
                `;
            });
        }

        if (matchingPinned.length === 0 && matchingAll.length === 0) {
            html = `<div class="p-3 text-xs text-slate-400 text-center font-sans">No matching currency</div>`;
        }

        dropdown.innerHTML = html;
        highlightedIdx[type] = -1;
    }

    let highlightedIdx = { from: -1, to: -1 };

    function toggleCombobox(type) {
        const dropdown = document.getElementById(type + '-dropdown');
        const input = document.getElementById(type + '-search');
        const otherType = type === 'from' ? 'to' : 'from';
        document.getElementById(otherType + '-dropdown')?.classList.add('hidden');
        document.getElementById(otherType + '-search')?.setAttribute('aria-expanded', 'false');

        if (dropdown.classList.contains('hidden')) {
            renderDropdownOptions(type);
            dropdown.classList.remove('hidden');
            input?.setAttribute('aria-expanded', 'true');
        } else {
            dropdown.classList.add('hidden');
            input?.setAttribute('aria-expanded', 'false');
        }
    }

    function filterCombobox(type) {
        const input = document.getElementById(type + '-search');
        renderDropdownOptions(type, input.value);
        document.getElementById(type + '-dropdown')?.classList.remove('hidden');
        input?.setAttribute('aria-expanded', 'true');
    }

    function selectCurrency(type, code) {
        const input = document.getElementById(type + '-search');
        input.value = code;
        document.getElementById(type + '-dropdown')?.classList.add('hidden');
        input?.setAttribute('aria-expanded', 'false');
        runFullCurrencyCalculation();
        loadHistoricalTrend();

        const from = (document.getElementById('from-search').value || 'USD').toUpperCase();
        const to = (document.getElementById('to-search').value || 'IDR').toUpperCase();
        saveRecentPair(from, to);
    }

    // Accessible keyboard navigation for comboboxes
    function setupKeyboardCombobox(type) {
        const input = document.getElementById(type + '-search');
        const dropdown = document.getElementById(type + '-dropdown');
        if (!input || !dropdown) return;

        input.addEventListener('keydown', (e) => {
            const isHidden = dropdown.classList.contains('hidden');
            const options = dropdown.querySelectorAll('.combobox-opt');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (isHidden) {
                    toggleCombobox(type);
                }
                const newOpts = dropdown.querySelectorAll('.combobox-opt');
                if (newOpts.length > 0) {
                    highlightedIdx[type] = (highlightedIdx[type] + 1) % newOpts.length;
                    updateComboboxHighlight(type, newOpts);
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (isHidden) {
                    toggleCombobox(type);
                }
                const newOpts = dropdown.querySelectorAll('.combobox-opt');
                if (newOpts.length > 0) {
                    highlightedIdx[type] = (highlightedIdx[type] - 1 + newOpts.length) % newOpts.length;
                    updateComboboxHighlight(type, newOpts);
                }
            } else if (e.key === 'Enter') {
                if (!isHidden && highlightedIdx[type] >= 0 && options[highlightedIdx[type]]) {
                    e.preventDefault();
                    options[highlightedIdx[type]].click();
                }
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
                input.setAttribute('aria-expanded', 'false');
                highlightedIdx[type] = -1;
            }
        });
    }

    function updateComboboxHighlight(type, options) {
        options.forEach((opt, idx) => {
            if (idx === highlightedIdx[type]) {
                opt.classList.add('bg-blue-600', 'text-white');
                opt.scrollIntoView({ block: 'nearest' });
            } else {
                opt.classList.remove('bg-blue-600', 'text-white');
            }
        });
    }

    // Recent pairs management via localStorage
    const RECENT_PAIRS_KEY = 'cargotrace_recent_currency_pairs';

    function getRecentPairs() {
        try {
            const raw = localStorage.getItem(RECENT_PAIRS_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return parsed;
                }
            }
        } catch (e) {}
        return [
            { from: 'USD', to: 'EUR' },
            { from: 'USD', to: 'IDR' },
            { from: 'EUR', to: 'SGD' },
            { from: 'CNY', to: 'USD' }
        ];
    }

    function saveRecentPair(from, to) {
        if (!from || !to || from === to) return;
        try {
            let pairs = getRecentPairs().filter(p => !(p.from === from && p.to === to));
            pairs.unshift({ from, to });
            pairs = pairs.slice(0, 5);
            localStorage.setItem(RECENT_PAIRS_KEY, JSON.stringify(pairs));
            renderRecentPairs();
        } catch (e) {}
    }

    function applyRecentPair(from, to) {
        const fromInput = document.getElementById('from-search');
        const toInput = document.getElementById('to-search');
        if (fromInput) fromInput.value = from;
        if (toInput) toInput.value = to;
        runFullCurrencyCalculation();
        loadHistoricalTrend();
        saveRecentPair(from, to);
    }

    function renderRecentPairs() {
        const container = document.getElementById('recent-pairs-container');
        if (!container) return;
        const pairs = getRecentPairs();
        if (pairs.length === 0) {
            container.innerHTML = '';
            return;
        }

        let html = `
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-xs theme-text-muted font-medium">Recent pairs:</span>
        `;
        pairs.forEach(p => {
            html += `
                <button
                    type="button"
                    onclick="applyRecentPair('${p.from}', '${p.to}')"
                    class="px-2.5 py-1 rounded-lg theme-card-elevated border theme-border font-mono text-xs font-semibold text-cyan-600 dark:text-cyan-400 hover:border-blue-500 hover:text-blue-500 transition cursor-pointer"
                >
                    ${p.from} &rarr; ${p.to}
                </button>
            `;
        });
        html += `</div>`;
        container.innerHTML = html;
    }

    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#from-search') && !e.target.closest('#from-dropdown')) {
            document.getElementById('from-dropdown')?.classList.add('hidden');
            document.getElementById('from-search')?.setAttribute('aria-expanded', 'false');
        }
        if (!e.target.closest('#to-search') && !e.target.closest('#to-dropdown')) {
            document.getElementById('to-dropdown')?.classList.add('hidden');
            document.getElementById('to-search')?.setAttribute('aria-expanded', 'false');
        }
    });

    function swapFullCurrencies() {
        const fromInput = document.getElementById('from-search');
        const toInput = document.getElementById('to-search');
        const temp = fromInput.value;
        fromInput.value = toInput.value;
        toInput.value = temp;
        runFullCurrencyCalculation();
        loadHistoricalTrend();
        saveRecentPair(fromInput.value, toInput.value);
    }

    function runFullCurrencyCalculation() {
        const amount = parseFloat(document.getElementById('calc-amount').value) || 0;
        const from = (document.getElementById('from-search').value || 'USD').toUpperCase();
        const to = (document.getElementById('to-search').value || 'IDR').toUpperCase();

        const rateFrom = globalRates[from] || 1.0;
        const rateTo = globalRates[to] || 1.0;

        const effectiveRate = rateFrom > 0 ? (rateTo / rateFrom) : 1.0;
        const converted = amount * effectiveRate;

        // Intl.NumberFormat with appropriate decimals
        const decimals = ['IDR', 'JPY', 'KRW', 'VND', 'CLP'].includes(to) ? 0 : 2;
        let formattedValue;
        try {
            formattedValue = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: to,
                maximumFractionDigits: decimals,
                minimumFractionDigits: decimals
            }).format(converted);
        } catch (e) {
            formattedValue = `${converted.toLocaleString('en-US', { maximumFractionDigits: decimals })} ${to}`;
        }

        const rateDecimals = ['IDR', 'JPY', 'KRW', 'VND'].includes(to) ? 2 : 4;
        const equation = `1 ${from} = ${effectiveRate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: rateDecimals })} ${to}`;

        document.getElementById('full-calc-details').innerText = equation;
        document.getElementById('full-calc-result').innerText = formattedValue;
        document.getElementById('full-calc-currency-name').innerText = getCurrencyFullName(to);
    }

    // Matrix toggling: user picks 3 - 8 currencies
    function toggleMatrixCurrency(code) {
        let current = [...activeMatrixCurrencies];
        if (current.includes(code)) {
            if (current.length <= 3) {
                alert('A minimum of 3 currencies is required for the cross-rate matrix.');
                return;
            }
            current = current.filter(c => c !== code);
        } else {
            if (current.length >= 8) {
                alert('A maximum of 8 currencies can be selected in the matrix.');
                return;
            }
            current.push(code);
        }

        const url = new URL(window.location.href);
        url.searchParams.set('matrix', current.join(','));
        window.location.href = url.toString();
    }

    // 30-Day Historical Chart (Chart.js via Frankfurter)
    let historyChartInstance = null;

    async function loadHistoricalTrend() {
        const from = (document.getElementById('from-search').value || 'USD').toUpperCase();
        const to = (document.getElementById('to-search').value || 'EUR').toUpperCase();
        const chartCard = document.getElementById('history-chart-card');
        const notice = document.getElementById('history-unavailable-notice');

        // Frankfurter check: only if both currencies supported
        if (!frankfurterCurrencies.includes(from) || !frankfurterCurrencies.includes(to) || from === to) {
            chartCard?.classList.add('hidden');
            notice?.classList.remove('hidden');
            return;
        }

        chartCard?.classList.remove('hidden');
        notice?.classList.add('hidden');
        document.getElementById('history-pair-label').innerText = `${from} / ${to}`;

        try {
            const res = await fetch(`/api/currency/history?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&days=30`);
            const json = await res.json();
            const data = json.data;

            if (!data || !Array.isArray(data) || data.length === 0) {
                chartCard?.classList.add('hidden');
                notice?.classList.remove('hidden');
                return;
            }

            renderChart(data.map(d => d.date), data.map(d => d.rate), `${from} → ${to}`);
        } catch (e) {
            console.warn('Failed to load history trend:', e);
            chartCard?.classList.add('hidden');
        }
    }

    function renderChart(labels, values, label) {
        const ctx = document.getElementById('currencyHistoryChart');
        if (!ctx) return;

        if (historyChartInstance) {
            historyChartInstance.destroy();
        }

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const lineColor = isDark ? '#38BDF8' : '#0284C7';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';
        const textColor = isDark ? '#94A3B8' : '#64748B';

        historyChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.map(l => l.slice(5)), // MM-DD
                datasets: [{
                    label: label,
                    data: values,
                    borderColor: lineColor,
                    backgroundColor: isDark ? 'rgba(56, 189, 248, 0.1)' : 'rgba(2, 132, 199, 0.08)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (ctx) => `Rate: ${ctx.parsed.y.toFixed(4)}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { family: 'monospace', size: 10 } }
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { family: 'monospace', size: 10 } }
                    }
                }
            }
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
        setupKeyboardCombobox('from');
        setupKeyboardCombobox('to');
        renderRecentPairs();
        runFullCurrencyCalculation();
        loadHistoricalTrend();
    });
</script>
@endsection

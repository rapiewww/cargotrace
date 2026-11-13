@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-5xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs theme-text-muted font-mono">
            <a href="{{ route('shipments.index') }}" class="hover:text-blue-500 dark:hover:text-cyan-400 transition-colors">Shipments</a>
            <span>&rarr;</span>
            <span class="text-blue-600 dark:text-cyan-400">New Booking</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
            Create Global Cargo Manifest
        </h1>
        <p class="text-xs sm:text-sm theme-text-secondary">
            Register freight waybill, schedule departure/arrival windows, and initialize milestone tracking.
        </p>
    </div>

    <!-- Form Container -->
    <x-card class="space-y-6">
        <form action="{{ route('shipments.store') }}" method="POST" class="space-y-6" id="shipment-form">
            @csrf

            <!-- Section 1: Carrier & Route -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    1. Carrier, Customer & Route Corridors
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <!-- Carrier -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Carrier Line *</label>
                        <select name="carrier_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($carriers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Customer -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Consignee Customer</label>
                        <select name="customer_id" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}">{{ $cust->company_name ?? $cust->name }} ({{ $cust->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Origin -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Origin Port / Gate *</label>
                        <select name="origin_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}">{{ $l->city }} ({{ $l->unlocode }}) &bull; {{ $l->country_code }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Destination -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Destination Port / Hub *</label>
                        <select name="destination_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" {{ $loop->iteration == 2 ? 'selected' : '' }}>{{ $l->city }} ({{ $l->unlocode }}) &bull; {{ $l->country_code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Mode, Valuation & Schedule -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    2. Transport Mode, Valuation & Windows
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Transport Mode *</label>
                        <select name="cargo_type" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <option value="FCL">FCL (Full Container Load)</option>
                            <option value="LCL">LCL (Less Container Load)</option>
                            <option value="REEFER">REEFER (Cold Chain)</option>
                            <option value="BULK">DRY BULK</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Declared Cargo Value *</label>
                        <input type="number" step="0.01" name="declared_value" id="declared_value" value="50000" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Currency Code *</label>
                        <select name="currency_code" id="currency_code" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="IDR">IDR - Indonesian Rupiah</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="CNY">CNY - Chinese Yuan</option>
                            <option value="GBP">GBP - British Pound</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Shipping Cost</label>
                        <input type="number" step="0.01" name="shipping_cost" value="2500" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Expected Departure *</label>
                        <input type="datetime-local" name="expected_departure_at" required value="{{ now()->addDay()->format('Y-m-d\TH:i') }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none transition font-mono text-xs">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Expected Arrival (ETA) *</label>
                        <input type="datetime-local" name="expected_arrival_at" required value="{{ now()->addDays(10)->format('Y-m-d\TH:i') }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none transition font-mono text-xs">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">PO / Reference Number</label>
                        <input type="text" name="reference_number" placeholder="PO-2026-XYZ" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Section 3: Cargo Manifest Line Items -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b theme-border pb-2">
                    <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">
                        3. Cargo Line Items (Manifest)
                    </span>
                    <button type="button" onclick="addItemRow()" class="px-3 py-1.5 rounded-xl bg-blue-600/15 text-blue-600 dark:text-cyan-400 text-xs font-semibold hover:bg-blue-600/25 border border-blue-500/30 transition">
                        + Add Item Row
                    </button>
                </div>

                <div id="items-container" class="space-y-3">
                    <div class="p-4 rounded-xl theme-card-elevated border theme-border grid grid-cols-1 sm:grid-cols-6 gap-3 text-xs item-row">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Description *</label>
                            <input type="text" name="items[0][description]" value="Commercial Industrial Goods" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Container No (ISO 6346)</label>
                            <input type="text" name="items[0][container_number]" placeholder="MSKU 123456 5" class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Qty *</label>
                            <input type="number" name="items[0][quantity]" value="10" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Weight (kg) *</label>
                            <input type="number" step="0.01" name="items[0][weight_kg]" value="1200" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Volume (CBM) *</label>
                            <input type="number" step="0.01" name="items[0][volume_cbm]" value="4.5" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit / Actions -->
            <div class="pt-4 border-t theme-border flex items-center justify-between">
                <a href="{{ route('shipments.index') }}" class="text-xs theme-text-secondary hover:theme-heading transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-blue-500/25">
                    Save & Initialize Milestones &rarr;
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
    let itemIdx = 1;
    function addItemRow() {
        const container = document.getElementById('items-container');
        const row = document.createElement('div');
        row.className = "p-4 rounded-xl theme-card-elevated border theme-border grid grid-cols-1 sm:grid-cols-6 gap-3 text-xs item-row";
        row.innerHTML = `
            <div class="sm:col-span-2 space-y-1">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Description *</label>
                <input type="text" name="items[${itemIdx}][description]" required placeholder="Cargo Item Name" class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
            </div>
            <div class="space-y-1">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Container No (ISO 6346)</label>
                <input type="text" name="items[${itemIdx}][container_number]" placeholder="MSKU 123456 5" class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
            </div>
            <div class="space-y-1">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Qty *</label>
                <input type="number" name="items[${itemIdx}][quantity]" value="1" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
            </div>
            <div class="space-y-1">
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Weight (kg) *</label>
                <input type="number" step="0.01" name="items[${itemIdx}][weight_kg]" value="100" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
            </div>
            <div class="space-y-1 flex items-end">
                <div class="w-full">
                    <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Volume (CBM)</label>
                    <input type="number" step="0.01" name="items[${itemIdx}][volume_cbm]" value="0.5" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                </div>
                <button type="button" onclick="this.closest('.item-row').remove()" class="ml-2 mb-1 text-rose-500 hover:text-rose-400 font-bold cursor-pointer" aria-label="Remove item">&times;</button>
            </div>
        `;
        container.appendChild(row);
        itemIdx++;
    }
</script>
@endpush

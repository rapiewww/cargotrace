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
            Create Freight Consignment
        </h1>
        <p class="text-xs sm:text-sm theme-text-secondary">
            Register shipment, schedule departure and arrival windows, and initialize milestone tracking.
        </p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs space-y-1">
            <div class="font-bold">Please correct the following:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Container -->
    <x-card class="space-y-6">
        <form action="{{ route('shipments.store') }}" method="POST" class="space-y-6" id="shipment-form">
            @csrf

            <!-- Section 1: Mode Select First -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    1. Primary Transport Mode
                </span>
                <div class="max-w-xs space-y-1.5">
                    <label for="transport_mode" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Select Mode *</label>
                    <select name="transport_mode" id="transport_mode" onchange="updateModeUI()" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-semibold text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                        <option value="SEA" {{ old('transport_mode', 'SEA') === 'SEA' ? 'selected' : '' }}>Ocean Freight (SEA)</option>
                        <option value="AIR" {{ old('transport_mode') === 'AIR' ? 'selected' : '' }}>Air Freight (AIR)</option>
                        <option value="ROAD" {{ old('transport_mode') === 'ROAD' ? 'selected' : '' }}>Road Freight (ROAD)</option>
                    </select>
                    @error('transport_mode')
                        <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Section 2: Mode-Dependent Reference Fields -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    2. Mode Consignment References
                </span>

                <!-- SEA Fields -->
                <div id="fields-sea" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mode-field-group">
                    <div class="space-y-1.5">
                        <label for="container_number" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            Container Number (ISO 6346) *
                        </label>
                        <input type="text" name="container_number" id="container_number" value="{{ old('container_number', 'MSKU 123456 5') }}" placeholder="MSKU 123456 5" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                        <span class="text-[10px] theme-text-muted block">4 letters (owner code + U/J/Z) + 6 digits + 1 check digit</span>
                        @error('container_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="bill_of_lading_number" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            Bill of Lading Number (B/L)
                        </label>
                        <input type="text" name="bill_of_lading_number" id="bill_of_lading_number" value="{{ old('bill_of_lading_number') }}" placeholder="B/L-MAEU-2026-99" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                        @error('bill_of_lading_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- AIR Fields -->
                <div id="fields-air" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mode-field-group hidden">
                    <div class="space-y-1.5">
                        <label for="awb_number" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            Air Waybill Number (AWB) *
                        </label>
                        <input type="text" name="awb_number" id="awb_number" value="{{ old('awb_number') }}" placeholder="126-12345675" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                        <span class="text-[10px] theme-text-muted block">11 digits (3-digit airline prefix + 7-digit serial + 1 check digit equal to serial mod 7)</span>
                        @error('awb_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- ROAD Fields -->
                <div id="fields-road" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mode-field-group hidden">
                    <div class="space-y-1.5">
                        <label for="vehicle_plate" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            Vehicle License Plate *
                        </label>
                        <input type="text" name="vehicle_plate" id="vehicle_plate" value="{{ old('vehicle_plate') }}" placeholder="B 9876 TX" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                        @error('vehicle_plate')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="cmr_number" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            CMR / Consignment Note Number *
                        </label>
                        <input type="text" name="cmr_number" id="cmr_number" value="{{ old('cmr_number') }}" placeholder="CMR-2026-0089" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                        @error('cmr_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Carrier & Route (Filtered by Mode) -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    3. Carrier, Customer & Route
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <!-- Carrier -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Carrier Line *</label>
                        <select name="carrier_id" id="carrier_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <!-- Populated dynamically via JS -->
                        </select>
                        @error('carrier_id')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Customer -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Consignee Customer</label>
                        <select name="customer_id" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id') == $cust->id ? 'selected' : '' }}>
                                    {{ $cust->company_name ?? $cust->name }} ({{ $cust->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Origin -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Origin Location *</label>
                        <select name="origin_location_id" id="origin_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <!-- Populated dynamically via JS -->
                        </select>
                        @error('origin_location_id')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Destination -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Destination Location *</label>
                        <select name="destination_location_id" id="destination_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <!-- Populated dynamically via JS -->
                        </select>
                        @error('destination_location_id')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 4: Cargo Specifications & Valuation -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    4. Cargo Specifications, Valuation & Schedule
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Cargo Type *</label>
                        <select name="cargo_type" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <option value="SPICES_BULK">Spices (Bulk / Dry Cargo)</option>
                            <option value="ESSENTIAL_OILS">Essential Oils (Fragrance Grade)</option>
                            <option value="FCL">FCL (Full Container Load)</option>
                            <option value="LCL">LCL (Less Container Load)</option>
                            <option value="AIR_FREIGHT">Priority Air Express</option>
                            <option value="REEFER">Reefer (Temperature-Controlled)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Declared Cargo Value *</label>
                        <input type="number" step="0.01" name="declared_value" id="declared_value" value="{{ old('declared_value', 50000) }}" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Currency Code *</label>
                        <select name="currency_code" id="currency_code" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="INR">INR - Indian Rupee</option>
                            <option value="IDR">IDR - Indonesian Rupiah</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="AED">AED - UAE Dirham</option>
                            <option value="GBP">GBP - British Pound</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Shipping Cost</label>
                        <input type="number" step="0.01" name="shipping_cost" value="{{ old('shipping_cost', 2500) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Expected Departure *</label>
                        <input type="datetime-local" name="expected_departure_at" required value="{{ old('expected_departure_at', now()->addDay()->format('Y-m-d\TH:i')) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none transition font-mono text-xs">
                        @error('expected_departure_at')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Expected Arrival (ETA) *</label>
                        <input type="datetime-local" name="expected_arrival_at" required value="{{ old('expected_arrival_at', now()->addDays(10)->format('Y-m-d\TH:i')) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border focus:ring-1 focus:ring-blue-500 focus:outline-none transition font-mono text-xs">
                        @error('expected_arrival_at')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">PO / Customer Ref</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="PO-2026-XYZ" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Section 5: Cargo Line Items -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b theme-border pb-2">
                    <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block">
                        5. Cargo Line Items (Manifest)
                    </span>
                    <button type="button" onclick="addItemRow()" class="px-3 py-1.5 rounded-xl bg-blue-600/15 text-blue-600 dark:text-cyan-400 text-xs font-semibold hover:bg-blue-600/25 border border-blue-500/30 transition">
                        + Add Item Row
                    </button>
                </div>

                <div id="items-container" class="space-y-3">
                    <div class="p-4 rounded-xl theme-card-elevated border theme-border grid grid-cols-1 sm:grid-cols-6 gap-3 text-xs item-row">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Description *</label>
                            <input type="text" name="items[0][description]" value="{{ old('items.0.description', 'Nutmeg & Clove Essential Oils') }}" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">HS Code / SKU</label>
                            <input type="text" name="items[0][sku]" value="{{ old('items.0.sku', '3301.29') }}" placeholder="3301.29" class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Qty *</label>
                            <input type="number" name="items[0][quantity]" value="{{ old('items.0.quantity', 10) }}" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Weight (kg) *</label>
                            <input type="number" step="0.01" name="items[0][weight_kg]" value="{{ old('items.0.weight_kg', 1200) }}" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Volume (CBM) *</label>
                            <input type="number" step="0.01" name="items[0][volume_cbm]" value="{{ old('items.0.volume_cbm', 4.5) }}" required class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
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
    const carriersData = @json($carriers);
    const locationsData = @json($locations);
    const oldCarrierId = @json(old('carrier_id'));
    const oldOriginId = @json(old('origin_location_id'));
    const oldDestId = @json(old('destination_location_id'));

    function isLocationValidForMode(loc, mode) {
        const type = (loc.type || '').toUpperCase();
        if (mode === 'SEA') {
            return type === 'PORT' || type === 'SEAPORT';
        }
        if (mode === 'AIR') {
            return type === 'AIRPORT';
        }
        if (mode === 'ROAD') {
            return ['DEPOT', 'BORDER', 'HUB', 'WAREHOUSE', 'CUSTOMS', 'PORT', 'SEAPORT', 'AIRPORT'].includes(type);
        }
        return true;
    }

    function updateModeUI() {
        const mode = document.getElementById('transport_mode').value;

        // Toggle reference field visibility
        document.querySelectorAll('.mode-field-group').forEach(el => el.classList.add('hidden'));
        if (mode === 'SEA') {
            document.getElementById('fields-sea')?.classList.remove('hidden');
        } else if (mode === 'AIR') {
            document.getElementById('fields-air')?.classList.remove('hidden');
        } else if (mode === 'ROAD') {
            document.getElementById('fields-road')?.classList.remove('hidden');
        }

        // Filter Carriers
        const carrierSelect = document.getElementById('carrier_id');
        const currentCarrier = carrierSelect.value || oldCarrierId;
        carrierSelect.innerHTML = '';
        const matchingCarriers = carriersData.filter(c => (c.mode || '').toUpperCase() === mode);
        matchingCarriers.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.name} (${c.code})`;
            if (String(c.id) === String(currentCarrier)) {
                opt.selected = true;
            }
            carrierSelect.appendChild(opt);
        });

        // Filter Locations
        const matchingLocs = locationsData.filter(l => isLocationValidForMode(l, mode));

        const originSelect = document.getElementById('origin_location_id');
        const currentOrigin = originSelect.value || oldOriginId;
        originSelect.innerHTML = '';
        matchingLocs.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = `${l.name} (${l.display_code || l.unlocode}) [${l.type}]`;
            if (String(l.id) === String(currentOrigin)) {
                opt.selected = true;
            }
            originSelect.appendChild(opt);
        });

        const destSelect = document.getElementById('destination_location_id');
        const currentDest = destSelect.value || oldDestId;
        destSelect.innerHTML = '';
        matchingLocs.forEach((l, idx) => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = `${l.name} (${l.display_code || l.unlocode}) [${l.type}]`;
            if (String(l.id) === String(currentDest) || (!currentDest && idx === 1)) {
                opt.selected = true;
            }
            destSelect.appendChild(opt);
        });
    }

    document.addEventListener('DOMContentLoaded', updateModeUI);

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
                <label class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">HS Code / SKU</label>
                <input type="text" name="items[${itemIdx}][sku]" placeholder="0908.11" class="w-full px-3 py-1.5 rounded-lg theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none">
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

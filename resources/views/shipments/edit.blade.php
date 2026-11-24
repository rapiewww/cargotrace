@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-5xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs theme-text-muted font-mono">
            <a href="{{ route('shipments.index') }}" class="hover:text-blue-600 dark:hover:text-cyan-400 transition-colors">Shipments</a>
            <span>&rarr;</span>
            <a href="{{ route('shipments.show', $shipment) }}" class="hover:text-blue-600 dark:hover:text-cyan-400 font-mono">{{ $shipment->tracking_number }}</a>
            <span>&rarr;</span>
            <span class="text-blue-600 dark:text-cyan-400 font-semibold">Edit Manifest</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight theme-heading font-sans">
            Edit Cargo Manifest: <span class="font-mono text-blue-600 dark:text-cyan-400">{{ $shipment->tracking_number }}</span>
        </h1>
        <p class="text-xs sm:text-sm theme-text-secondary">
            Update routing, schedule windows, status progression, and re-evaluate ETA calculations.
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

    <x-card class="space-y-6">
        <form action="{{ route('shipments.update', $shipment) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Mode Select First -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    1. Primary Transport Mode
                </span>
                <div class="max-w-xs space-y-1.5">
                    <label for="transport_mode" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">Select Mode *</label>
                    <select name="transport_mode" id="transport_mode" onchange="updateModeUI()" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-semibold text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                        <option value="SEA" {{ old('transport_mode', $shipment->transport_mode ?? 'SEA') === 'SEA' ? 'selected' : '' }}>🚢 Ocean Freight (SEA)</option>
                        <option value="AIR" {{ old('transport_mode', $shipment->transport_mode ?? 'SEA') === 'AIR' ? 'selected' : '' }}>✈️ Air Freight (AIR)</option>
                        <option value="ROAD" {{ old('transport_mode', $shipment->transport_mode ?? 'SEA') === 'ROAD' ? 'selected' : '' }}>🚚 Road Freight (ROAD)</option>
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
                            Container Number (ISO 6346)
                        </label>
                        <input type="text" name="container_number" id="container_number" value="{{ old('container_number', $shipment->formatted_container_number ?? $shipment->container_number) }}" placeholder="MSKU 123456 5" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                        <span class="text-[10px] theme-text-muted block">ISO 6346 compliant format</span>
                        @error('container_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="bill_of_lading_number" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            Bill of Lading Number (B/L)
                        </label>
                        <input type="text" name="bill_of_lading_number" id="bill_of_lading_number" value="{{ old('bill_of_lading_number', $shipment->bill_of_lading_number) }}" placeholder="B/L-MAEU-2026-99" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                        @error('bill_of_lading_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- AIR Fields -->
                <div id="fields-air" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mode-field-group hidden">
                    <div class="space-y-1.5">
                        <label for="awb_number" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            Air Waybill Number (AWB)
                        </label>
                        <input type="text" name="awb_number" id="awb_number" value="{{ old('awb_number', $shipment->formatted_awb_number ?? $shipment->awb_number) }}" placeholder="126-12345675" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                        <span class="text-[10px] theme-text-muted block">11-digit AWB (airline prefix + serial + mod 7 check digit)</span>
                        @error('awb_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- ROAD Fields -->
                <div id="fields-road" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs mode-field-group hidden">
                    <div class="space-y-1.5">
                        <label for="vehicle_plate" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            Vehicle License Plate
                        </label>
                        <input type="text" name="vehicle_plate" id="vehicle_plate" value="{{ old('vehicle_plate', $shipment->vehicle_plate) }}" placeholder="B 9876 TX" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                        @error('vehicle_plate')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label for="cmr_number" class="uppercase text-[10px] tracking-wider theme-text-muted font-semibold block">
                            CMR / Consignment Note Number
                        </label>
                        <input type="text" name="cmr_number" id="cmr_number" value="{{ old('cmr_number', $shipment->cmr_number) }}" placeholder="CMR-2026-0089" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                        @error('cmr_number')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Carrier & Route Corridors -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    3. Carrier, Customer & Route Corridors
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <!-- Carrier -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Carrier Line *</label>
                        <select name="carrier_id" id="carrier_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <!-- Populated dynamically via JS -->
                        </select>
                        @error('carrier_id')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Customer -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Consignee Customer</label>
                        <select name="customer_id" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($customers as $cust)
                                <option value="{{ $cust->id }}" {{ old('customer_id', $shipment->customer_id) == $cust->id ? 'selected' : '' }}>
                                    {{ $cust->company_name ?? $cust->name }} ({{ $cust->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Origin -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Origin Location *</label>
                        <select name="origin_location_id" id="origin_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <!-- Populated dynamically via JS -->
                        </select>
                        @error('origin_location_id')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Destination -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Destination Location *</label>
                        <select name="destination_location_id" id="destination_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <!-- Populated dynamically via JS -->
                        </select>
                        @error('destination_location_id')
                            <span class="text-[11px] text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 4: Status Progression & Checkpoint -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    4. Status Progression & Current Terminal
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Lifecycle Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $st)
                                <option value="{{ $st }}" {{ old('status', $shipment->status) === $st ? 'selected' : '' }}>
                                    {{ \App\Models\Shipment::statusDisplayLabel($st, $shipment->transport_mode ?? 'SEA') }} ({{ $st }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Current Checkpoint Location</label>
                        <select name="current_location_id" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            <option value="">No Checkpoint Selected</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('current_location_id', $shipment->current_location_id) == $loc->id ? 'selected' : '' }}>
                                    {{ $loc->city }} ({{ $loc->unlocode }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Cargo Type *</label>
                        <select name="cargo_type" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach(['SPICES_BULK', 'ESSENTIAL_OILS', 'FCL', 'LCL', 'AIR_FREIGHT', 'REEFER', 'BULK'] as $ct)
                                <option value="{{ $ct }}" {{ old('cargo_type', $shipment->cargo_type) === $ct ? 'selected' : '' }}>{{ $ct }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 5: Schedule Windows & Valuation -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    5. Schedule Windows & Valuation
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Expected Departure *</label>
                        <input type="datetime-local" name="expected_departure_at" required value="{{ old('expected_departure_at', $shipment->expected_departure_at?->format('Y-m-d\TH:i')) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Expected Arrival Window *</label>
                        <input type="datetime-local" name="expected_arrival_at" required value="{{ old('expected_arrival_at', $shipment->expected_arrival_at?->format('Y-m-d\TH:i')) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Estimated Arrival (ETA)</label>
                        <input type="datetime-local" name="estimated_arrival_at" value="{{ old('estimated_arrival_at', $shipment->estimated_arrival_at?->format('Y-m-d\TH:i')) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Declared Cargo Value</label>
                        <input type="number" step="0.01" name="declared_value" value="{{ old('declared_value', $shipment->declared_value) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Currency Code</label>
                        <select name="currency_code" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                            @foreach(['USD', 'EUR', 'IDR', 'SGD', 'CNY', 'GBP'] as $cur)
                                <option value="{{ $cur }}" {{ old('currency_code', $shipment->currency_code) === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Shipping Cost</label>
                        <input type="number" step="0.01" name="shipping_cost" value="{{ old('shipping_cost', $shipment->shipping_cost) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Customer Reference</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $shipment->reference_number) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Operational Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border text-xs focus:ring-1 focus:ring-blue-500">{{ old('notes', $shipment->notes) }}</textarea>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="pt-4 border-t theme-border flex items-center justify-between">
                <a href="{{ route('shipments.show', $shipment) }}" class="text-xs theme-text-secondary hover:theme-heading transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition shadow-md shadow-blue-600/20">
                    Save Changes & Re-evaluate Schedule &rarr;
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
    const initialCarrierId = @json(old('carrier_id', $shipment->carrier_id));
    const initialOriginId = @json(old('origin_location_id', $shipment->origin_location_id));
    const initialDestId = @json(old('destination_location_id', $shipment->destination_location_id));

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
        const currentCarrier = carrierSelect.value || initialCarrierId;
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
        const currentOrigin = originSelect.value || initialOriginId;
        originSelect.innerHTML = '';
        matchingLocs.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = `${l.city} (${l.unlocode}) • [${l.type}]`;
            if (String(l.id) === String(currentOrigin)) {
                opt.selected = true;
            }
            originSelect.appendChild(opt);
        });

        const destSelect = document.getElementById('destination_location_id');
        const currentDest = destSelect.value || initialDestId;
        destSelect.innerHTML = '';
        matchingLocs.forEach(l => {
            const opt = document.createElement('option');
            opt.value = l.id;
            opt.textContent = `${l.city} (${l.unlocode}) • [${l.type}]`;
            if (String(l.id) === String(currentDest)) {
                opt.selected = true;
            }
            destSelect.appendChild(opt);
        });
    }

    document.addEventListener('DOMContentLoaded', updateModeUI);
</script>
@endpush

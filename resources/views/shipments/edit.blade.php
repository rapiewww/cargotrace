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

            <!-- Section 1: Carrier & Route -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    1. Carrier, Customer & Route Corridors
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                    <!-- Carrier -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Carrier Line *</label>
                        <select name="carrier_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($carriers as $c)
                                <option value="{{ $c->id }}" {{ old('carrier_id', $shipment->carrier_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->code }})
                                </option>
                            @endforeach
                        </select>
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
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Origin Port / Gate *</label>
                        <select name="origin_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" {{ old('origin_location_id', $shipment->origin_location_id) == $l->id ? 'selected' : '' }}>
                                    {{ $l->city }} ({{ $l->unlocode }}) &bull; {{ $l->country_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Destination -->
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Destination Port / Hub *</label>
                        <select name="destination_location_id" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" {{ old('destination_location_id', $shipment->destination_location_id) == $l->id ? 'selected' : '' }}>
                                    {{ $l->city }} ({{ $l->unlocode }}) &bull; {{ $l->country_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Mode, Lifecycle & Current Checkpoint -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    2. Status Progression & Current Terminal
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Lifecycle Status</label>
                        <select name="status" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $st)
                                <option value="{{ $st }}" {{ old('status', $shipment->status) === $st ? 'selected' : '' }}>{{ $st }}</option>
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
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Transport Mode *</label>
                        <select name="cargo_type" required class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500 focus:outline-none transition">
                            @foreach(['FCL', 'LCL', 'AIR_FREIGHT', 'REEFER', 'BULK'] as $ct)
                                <option value="{{ $ct }}" {{ old('cargo_type', $shipment->cargo_type) === $ct ? 'selected' : '' }}>{{ $ct }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 3: Schedule Windows & Valuation -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider theme-text-muted font-semibold block border-b theme-border pb-2">
                    3. Schedule Windows & Valuation
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
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Reference Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $shipment->reference_number) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider theme-text-secondary font-semibold block">Container Number (ISO 6346)</label>
                        <input type="text" name="container_number" placeholder="MSKU 123456 5" value="{{ old('container_number', $shipment->items->first()?->formatted_container_number) }}" class="w-full px-3.5 py-2.5 rounded-xl theme-input border theme-border font-mono text-xs focus:ring-1 focus:ring-blue-500">
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


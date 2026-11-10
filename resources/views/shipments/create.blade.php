@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 space-y-6 max-w-4xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="space-y-1.5">
        <div class="flex items-center space-x-2 text-xs text-slate-400 font-mono">
            <a href="{{ route('shipments.index') }}" class="hover:text-teal-300 transition-colors">Shipments</a>
            <span>&rarr;</span>
            <span class="text-teal-300">New Booking</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Create New Global Shipment</h1>
        <p class="text-xs sm:text-sm text-slate-400">Register freight manifest, schedule departures, and trigger auto currency conversion.</p>
    </div>

    <!-- Form Container -->
    <div class="p-6 sm:p-8 rounded-2xl bg-[#0B1728]/85 backdrop-blur-xl border border-white/10 shadow-2xl space-y-6">
        <form action="{{ route('shipments.store') }}" method="POST" class="space-y-6" id="shipment-form">
            @csrf

            <!-- Section 1: Carrier & Route -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider text-slate-500 font-semibold block border-b border-white/10 pb-2">1. Carrier & Route Routing</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Carrier Partner *</label>
                        <select name="carrier_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                            @foreach($carriers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }}) &bull; {{ $c->mode }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Origin Port / Terminal *</label>
                        <select name="origin_location_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->unlocode }}) &bull; {{ $l->country }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Destination Port / Hub *</label>
                        <select name="destination_location_id" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                            @foreach($locations as $l)
                                <option value="{{ $l->id }}" {{ $loop->iteration == 2 ? 'selected' : '' }}>{{ $l->name }} ({{ $l->unlocode }}) &bull; {{ $l->country }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Mode, Currency & Schedule -->
            <div class="space-y-4">
                <span class="uppercase text-xs tracking-wider text-slate-500 font-semibold block border-b border-white/10 pb-2">2. Mode, Valuation & Schedule</span>
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Transport Mode *</label>
                        <select name="cargo_type" required class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                            <option value="FCL">FCL (Full Container Load)</option>
                            <option value="LCL">LCL (Less than Container Load)</option>
                            <option value="AIR_FREIGHT">AIR FREIGHT</option>
                            <option value="REEFER">REEFER (Cold Chain)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Declared Value</label>
                        <input type="number" step="0.01" name="declared_value" id="declared_value" value="50000" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Currency Code</label>
                        <select name="currency_code" id="currency_code" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                            <option value="USD">USD - US Dollar</option>
                            <option value="EUR">EUR - Euro</option>
                            <option value="IDR">IDR - Indonesian Rupiah</option>
                            <option value="SGD">SGD - Singapore Dollar</option>
                            <option value="CNY">CNY - Chinese Yuan</option>
                            <option value="AED">AED - UAE Dirham</option>
                            <option value="AUD">AUD - Australian Dollar</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Shipping Cost</label>
                        <input type="number" step="0.01" name="shipping_cost" value="2500" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Expected Departure *</label>
                        <input type="datetime-local" name="expected_departure_at" required value="{{ now()->addDay()->format('Y-m-d\TH:i') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Expected Arrival *</label>
                        <input type="datetime-local" name="expected_arrival_at" required value="{{ now()->addDays(14)->format('Y-m-d\TH:i') }}" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Reference / PO Number</label>
                        <input type="text" name="reference_number" placeholder="PO-2026-XYZ" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950/70 border border-slate-800 text-white font-mono focus:ring-2 focus:ring-amber-500/50 focus:outline-none transition-all">
                    </div>
                </div>
            </div>

            <!-- Section 3: Shipment Cargo Items -->
            <div class="space-y-4">
                <div class="flex items-center justify-between border-b border-white/10 pb-2">
                    <span class="uppercase text-xs tracking-wider text-slate-500 font-semibold block">3. Cargo Line Items (Manifest)</span>
                    <button type="button" onclick="addItemRow()" class="px-3 py-1 rounded-xl bg-teal-500/20 text-teal-300 text-xs font-semibold hover:bg-teal-500/30 border border-teal-500/30 transition-all duration-200 hover:scale-[1.02]">
                        + Add Item
                    </button>
                </div>

                <div id="items-container" class="space-y-3">
                    <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-6 gap-3 text-xs item-row">
                        <div class="sm:col-span-2 space-y-1">
                            <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Description *</label>
                            <input type="text" name="items[0][description]" value="Commercial Industrial Goods" required class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white focus:ring-1 focus:ring-teal-400 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">SKU Code</label>
                            <input type="text" name="items[0][sku]" value="SKU-COM-01" class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Qty *</label>
                            <input type="number" name="items[0][quantity]" value="10" required class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Weight (kg) *</label>
                            <input type="number" step="0.01" name="items[0][weight_kg]" value="1200" required class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
                        </div>
                        <div class="space-y-1">
                            <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Volume (CBM) *</label>
                            <input type="number" step="0.01" name="items[0][volume_cbm]" value="4.5" required class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                <a href="{{ route('shipments.index') }}" class="text-xs text-slate-400 hover:text-white transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-bold text-xs uppercase tracking-wider transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-amber-500/25">
                    Save & Initialize Telemetry &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let itemIdx = 1;
    function addItemRow() {
        const container = document.getElementById('items-container');
        const row = document.createElement('div');
        row.className = "p-4 rounded-xl bg-slate-950/60 border border-slate-800 grid grid-cols-1 sm:grid-cols-6 gap-3 text-xs item-row";
        row.innerHTML = `
            <div class="sm:col-span-2 space-y-1">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Description *</label>
                <input type="text" name="items[${itemIdx}][description]" required placeholder="Cargo Item Name" class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white focus:ring-1 focus:ring-teal-400 focus:outline-none">
            </div>
            <div class="space-y-1">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">SKU Code</label>
                <input type="text" name="items[${itemIdx}][sku]" placeholder="SKU-XXXX" class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
            </div>
            <div class="space-y-1">
                <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Qty *</label>
                <input type="number" name="items[${itemIdx}][quantity]" value="1" required class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
            </div>
            <div class="space-y-1">
                <label class="uppercase text-[10px] tracking-wider text-slate-400 font-semibold block">Weight (kg) *</label>
                <input type="number" step="0.01" name="items[${itemIdx}][weight_kg]" value="100" required class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
            </div>
            <div class="space-y-1 flex items-end">
                <div class="w-full">
                    <label class="uppercase text-[10px] tracking-wider text-slate-500 font-semibold block">Volume (CBM)</label>
                    <input type="number" step="0.01" name="items[${itemIdx}][volume_cbm]" value="0.5" required class="w-full px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-white font-mono focus:ring-1 focus:ring-teal-400 focus:outline-none">
                </div>
                <button type="button" onclick="this.closest('.item-row').remove()" class="ml-2 mb-1 text-rose-400 hover:text-rose-300 font-bold">&times;</button>
            </div>
        `;
        container.appendChild(row);
        itemIdx++;
    }
</script>
@endpush

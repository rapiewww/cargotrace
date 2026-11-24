<?php

namespace App\Http\Requests\Shipment;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Rules\AwbRule;
use App\Rules\Iso6346Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Shipment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transport_mode' => ['nullable', 'string', 'in:SEA,AIR,ROAD'],
            'carrier_id' => ['required', 'exists:carriers,id'],
            'origin_location_id' => ['required', 'exists:locations,id'],
            'destination_location_id' => ['required', 'exists:locations,id', 'different:origin_location_id'],
            'cargo_type' => ['required', 'string', 'max:50'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'expected_departure_at' => ['required', 'date'],
            'expected_arrival_at' => ['required', 'date', 'after_or_equal:expected_departure_at'],
            'estimated_arrival_at' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],

            // Multimodal references
            'container_number' => ['nullable', 'string', new Iso6346Rule],
            'bill_of_lading_number' => ['nullable', 'string', 'max:100'],
            'awb_number' => ['nullable', 'string', new AwbRule],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'cmr_number' => ['nullable', 'string', 'max:100'],

            'customer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.container_number' => ['nullable', 'string', new Iso6346Rule],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.hs_code' => ['nullable', 'string', 'max:20'],
            'items.*.packaging' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.weight_kg' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.volume_cbm' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.is_hazardous' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Perform mode-dependent requirements validation.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $mode = strtoupper($this->input('transport_mode') ?? 'SEA');

            // 1. Mode-dependent reference requirements
            if ($mode === 'SEA') {
                $container = $this->input('container_number');
                // Check if at least container_number on shipment or first item exists
                $itemContainer = $this->input('items.0.container_number');
                $targetContainer = $container ?: $itemContainer;

                if (! $targetContainer) {
                    $validator->errors()->add('container_number', 'The container number field is required for SEA shipments.');
                }
            } elseif ($mode === 'AIR') {
                if (! $this->filled('awb_number')) {
                    $validator->errors()->add('awb_number', 'The AWB number field is required for AIR shipments.');
                }
            } elseif ($mode === 'ROAD') {
                if (! $this->filled('vehicle_plate')) {
                    $validator->errors()->add('vehicle_plate', 'The vehicle plate field is required for ROAD shipments.');
                }
                if (! $this->filled('cmr_number')) {
                    $validator->errors()->add('cmr_number', 'The CMR number field is required for ROAD shipments.');
                }
            }

            // 2. Carrier mode must equal shipment mode
            $carrierId = $this->input('carrier_id');
            if ($carrierId) {
                $carrier = Carrier::find($carrierId);
                if ($carrier && strtoupper($carrier->mode) !== $mode) {
                    $validator->errors()->add(
                        'carrier_id',
                        "The selected carrier mode ({$carrier->mode}) must match the shipment transport mode ({$mode})."
                    );
                }
            }

            // 3. Origin and destination location validation per mode
            // SEA: seaports (PORT / SEAPORT)
            // AIR: airports (AIRPORT)
            // ROAD: depots/borders (DEPOT, BORDER, HUB, WAREHOUSE, CUSTOMS) or a seaport/airport as handover point
            $originId = $this->input('origin_location_id');
            $destId = $this->input('destination_location_id');

            if ($originId) {
                $origin = Location::find($originId);
                if ($origin && ! $this->isLocationValidForMode($origin, $mode)) {
                    $validator->errors()->add(
                        'origin_location_id',
                        "The origin location type ({$origin->type}) does not match shipment transport mode ({$mode})."
                    );
                }
            }

            if ($destId) {
                $dest = Location::find($destId);
                if ($dest && ! $this->isLocationValidForMode($dest, $mode)) {
                    $validator->errors()->add(
                        'destination_location_id',
                        "The destination location type ({$dest->type}) does not match shipment transport mode ({$mode})."
                    );
                }
            }
        });
    }

    /**
     * Check if location type is compatible with mode.
     */
    protected function isLocationValidForMode(Location $loc, string $mode): bool
    {
        $type = strtoupper($loc->type);

        return match ($mode) {
            'SEA' => in_array($type, ['PORT', 'SEAPORT'], true),
            'AIR' => in_array($type, ['AIRPORT'], true),
            'ROAD' => in_array($type, ['DEPOT', 'BORDER', 'HUB', 'WAREHOUSE', 'CUSTOMS', 'PORT', 'SEAPORT', 'AIRPORT'], true),
            default => true,
        };
    }
}

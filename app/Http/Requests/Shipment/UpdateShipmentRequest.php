<?php

namespace App\Http\Requests\Shipment;

use App\Models\Carrier;
use App\Models\Location;
use App\Rules\AwbRule;
use App\Rules\Iso6346Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('shipment')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transport_mode' => ['nullable', 'string', 'in:SEA,AIR,ROAD'],
            'carrier_id' => ['nullable', 'exists:carriers,id'],
            'origin_location_id' => ['nullable', 'exists:locations,id'],
            'destination_location_id' => ['nullable', 'exists:locations,id'],
            'status' => ['nullable', 'string', 'in:CREATED,BOOKED,PICKED_UP,IN_TRANSIT,DELIVERED'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'estimated_arrival_at' => ['nullable', 'date'],
            'delay_reason' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'cargo_type' => ['nullable', 'string', 'max:50'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'expected_departure_at' => ['nullable', 'date'],
            'expected_arrival_at' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'customer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],

            // Multimodal references
            'container_number' => ['nullable', 'string', new Iso6346Rule],
            'bill_of_lading_number' => ['nullable', 'string', 'max:100'],
            'awb_number' => ['nullable', 'string', new AwbRule],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'cmr_number' => ['nullable', 'string', 'max:100'],

            'items' => ['nullable', 'array'],
            'items.*.container_number' => ['nullable', 'string', new Iso6346Rule],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.hs_code' => ['nullable', 'string', 'max:20'],
            'items.*.packaging' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Mode-dependent validation during update.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $shipment = $this->route('shipment');
            $mode = strtoupper($this->input('transport_mode') ?? ($shipment?->transport_mode ?? 'SEA'));

            // If carrier is provided, check mode
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

            // Origin / Destination if provided
            $originId = $this->input('origin_location_id');
            if ($originId) {
                $origin = Location::find($originId);
                if ($origin && ! $this->isLocationValidForMode($origin, $mode)) {
                    $validator->errors()->add(
                        'origin_location_id',
                        "The origin location type ({$origin->type}) does not match shipment transport mode ({$mode})."
                    );
                }
            }

            $destId = $this->input('destination_location_id');
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

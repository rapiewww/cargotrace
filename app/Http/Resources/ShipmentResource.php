<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'transport_mode' => $this->transport_mode ?? 'SEA',
            'reference_number' => $this->reference_number,
            'container_number' => $this->container_number,
            'formatted_container_number' => $this->formatted_container_number,
            'bill_of_lading_number' => $this->bill_of_lading_number,
            'awb_number' => $this->awb_number,
            'formatted_awb_number' => $this->formatted_awb_number,
            'vehicle_plate' => $this->vehicle_plate,
            'cmr_number' => $this->cmr_number,
            'primary_reference' => $this->primary_reference,
            'primary_reference_label' => $this->primary_reference_label,
            'labeled_primary_reference' => $this->labeled_primary_reference,
            'customer_id' => $this->customer_id,
            'carrier_id' => $this->carrier_id,
            'origin_location_id' => $this->origin_location_id,
            'destination_location_id' => $this->destination_location_id,
            'current_location_id' => $this->current_location_id,
            'cargo_type' => $this->cargo_type,
            'total_weight_kg' => (float) $this->total_weight_kg,
            'total_volume_cbm' => (float) $this->total_volume_cbm,

            // Currency & Values
            'declared_value' => (float) $this->declared_value,
            'currency_code' => $this->currency_code,
            'shipping_cost' => (float) $this->shipping_cost,
            'exchange_rate_to_usd' => (float) $this->exchange_rate_to_usd,
            'converted_value_usd' => (float) $this->converted_value_usd,
            'currency_snapshot' => $this->currency_snapshot,

            // Status, Schedule & Delay
            'status' => $this->status,
            'status_label' => $this->status_display_label,
            'expected_departure_at' => $this->expected_departure_at?->toIso8601String(),
            'expected_arrival_at' => $this->expected_arrival_at?->toIso8601String(),
            'estimated_arrival_at' => $this->estimated_arrival_at?->toIso8601String(),
            'actual_arrival_at' => $this->actual_arrival_at?->toIso8601String(),
            'is_delayed' => (bool) $this->is_delayed,
            'delay_hours' => (float) $this->delay_hours,
            'delay_severity' => $this->delay_severity,
            'delay_reason' => $this->delay_reason,
            'route_news_snapshot' => $this->route_news_snapshot,
            'notes' => $this->notes,

            // Relations
            'customer' => new UserResource($this->whenLoaded('customer')),
            'carrier' => new CarrierResource($this->whenLoaded('carrier')),
            'origin_location' => new LocationResource($this->whenLoaded('originLocation')),
            'destination_location' => new LocationResource($this->whenLoaded('destinationLocation')),
            'current_location' => new LocationResource($this->whenLoaded('currentLocation')),
            'items' => ShipmentItemResource::collection($this->whenLoaded('items')),
            'tracking_events' => TrackingEventResource::collection($this->whenLoaded('trackingEvents')),
            'exceptions' => ShipmentExceptionResource::collection($this->whenLoaded('exceptions')),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

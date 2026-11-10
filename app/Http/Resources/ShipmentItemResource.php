<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentItemResource extends JsonResource
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
            'shipment_id' => $this->shipment_id,
            'sku' => $this->sku,
            'description' => $this->description,
            'quantity' => (int) $this->quantity,
            'unit_value' => (float) $this->unit_value,
            'currency_code' => $this->currency_code,
            'weight_kg' => (float) $this->weight_kg,
            'volume_cbm' => (float) $this->volume_cbm,
            'is_hazardous' => (bool) $this->is_hazardous,
            'total_value' => round((float) $this->unit_value * (int) $this->quantity, 2),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}


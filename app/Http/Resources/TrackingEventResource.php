<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackingEventResource extends JsonResource
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
            'location_id' => $this->location_id,
            'status' => $this->status,
            'event_type' => $this->event_type,
            'title' => $this->title,
            'description' => $this->description,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'weather_snapshot' => $this->weather_snapshot,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'role' => $this->creator->role,
            ]),
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

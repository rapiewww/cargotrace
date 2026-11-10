<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentExceptionResource extends JsonResource
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
            'exception_code' => $this->exception_code,
            'severity' => $this->severity,
            'status' => $this->status,
            'title' => $this->title,
            'description' => $this->description,
            'related_news_url' => $this->related_news_url,
            'resolution_notes' => $this->resolution_notes,
            'detected_at' => $this->detected_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'reported_by' => $this->reported_by,
            'reporter' => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'role' => $this->reporter->role,
            ]),
            'resolved_by' => $this->resolved_by,
            'resolver' => $this->whenLoaded('resolver', fn () => [
                'id' => $this->resolver->id,
                'name' => $this->resolver->name,
                'role' => $this->resolver->role,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}


<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSeaport = in_array(strtoupper((string) $this->type), ['PORT', 'SEAPORT'], true);

        return [
            'id' => $this->id,
            'code' => $this->display_code,
            'code_type' => $this->code_type_label,
            'short_name' => $this->short_name,
            'unlocode' => $isSeaport ? $this->unlocode : null,
            'iata_code' => $this->iata_code,
            'name' => $this->name,
            'city' => $this->city,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'currency_code' => $this->currency_code,
            'type' => $this->type,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'timezone' => $this->timezone,
            'weather_snapshot' => $this->weather_snapshot,
            'news_snapshot' => $this->news_snapshot,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

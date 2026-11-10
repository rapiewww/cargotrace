<?php

namespace App\Http\Requests\Shipment;

use App\Models\Shipment;
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
            'customer_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.sku' => ['nullable', 'string', 'max:100'],
            'items.*.description' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.weight_kg' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.volume_cbm' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.is_hazardous' => ['nullable', 'boolean'],
        ];
    }
}

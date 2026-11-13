<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        $trackingNumber = 'CTG-SEA-'.date('Y').'-'.fake()->unique()->numerify('#####');
        $departure = Carbon::now()->subDays(fake()->numberBetween(5, 45));
        $arrival = (clone $departure)->addDays(fake()->numberBetween(7, 21));

        return [
            'tracking_number' => $trackingNumber,
            'reference_number' => 'B/L-'.strtoupper(fake()->lexify('????')).'-'.fake()->numerify('######'),
            'customer_id' => User::factory(),
            'carrier_id' => Carrier::factory(),
            'origin_location_id' => Location::factory(),
            'destination_location_id' => Location::factory(),
            'current_location_id' => null,
            'cargo_type' => fake()->randomElement(['FCL', 'LCL', 'REEFER', 'HAZARDOUS']),
            'total_weight_kg' => fake()->randomFloat(2, 5000, 30000),
            'total_volume_cbm' => fake()->randomFloat(2, 20, 75),
            'declared_value' => fake()->randomFloat(2, 10000, 500000),
            'currency_code' => 'USD',
            'shipping_cost' => fake()->randomFloat(2, 1500, 15000),
            'exchange_rate_to_usd' => 1.0,
            'converted_value_usd' => fake()->randomFloat(2, 10000, 500000),
            'currency_snapshot' => null,
            'status' => 'IN_TRANSIT',
            'expected_departure_at' => $departure,
            'expected_arrival_at' => $arrival,
            'estimated_arrival_at' => $arrival,
            'actual_arrival_at' => null,
            'is_delayed' => false,
            'delay_hours' => 0.0,
            'delay_severity' => 'NONE',
            'delay_reason' => null,
            'route_news_snapshot' => null,
            'notes' => fake()->sentence(),
            'created_at' => (clone $departure)->subDays(3),
            'updated_at' => (clone $departure)->subDays(3),
        ];
    }

    public function delayed(float $hours = 12.0, string $severity = 'MEDIUM', string $reason = 'Port congestion'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_delayed' => true,
            'delay_hours' => $hours,
            'delay_severity' => $severity,
            'delay_reason' => $reason,
        ]);
    }

    public function delivered(?Carbon $arrivedAt = null): static
    {
        return $this->state(function (array $attributes) use ($arrivedAt) {
            $arrival = $arrivedAt ?? Carbon::now()->subDays(2);

            return [
                'status' => 'DELIVERED',
                'actual_arrival_at' => $arrival,
                'updated_at' => $arrival,
            ];
        });
    }
}


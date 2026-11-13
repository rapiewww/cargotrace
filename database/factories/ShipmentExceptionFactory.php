<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentException>
 */
class ShipmentExceptionFactory extends Factory
{
    protected $model = ShipmentException::class;

    public function definition(): array
    {
        $detected = Carbon::now()->subDays(fake()->numberBetween(2, 20));

        return [
            'shipment_id' => Shipment::factory(),
            'exception_code' => fake()->randomElement([
                'CUSTOMS_HOLD',
                'WEATHER_DELAY',
                'PORT_CONGESTION',
                'DAMAGED',
                'MISROUTED',
                'DOCUMENTATION_ISSUE',
            ]),
            'severity' => fake()->randomElement(['LOW', 'MEDIUM', 'HIGH']),
            'status' => 'OPEN',
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'related_news_url' => null,
            'resolution_notes' => null,
            'detected_at' => $detected,
            'resolved_at' => null,
            'reported_by' => User::factory(),
            'resolved_by' => null,
        ];
    }

    public function resolved(int $hoursLater = 24): static
    {
        return $this->state(function (array $attributes) use ($hoursLater) {
            $detected = isset($attributes['detected_at']) ? Carbon::parse($attributes['detected_at']) : Carbon::now()->subDays(5);
            $resolved = (clone $detected)->addHours($hoursLater);

            return [
                'status' => 'RESOLVED',
                'detected_at' => $detected,
                'resolved_at' => $resolved,
                'resolution_notes' => fake()->sentence(8),
                'resolved_by' => User::factory(),
            ];
        });
    }
}

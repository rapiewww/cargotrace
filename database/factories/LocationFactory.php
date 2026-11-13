<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $countryCode = strtoupper(fake()->lexify('??'));
        $locode = $countryCode.strtoupper(fake()->unique()->lexify('???'));

        return [
            'name' => 'Port of '.fake()->city(),
            'unlocode' => $locode,
            'city' => fake()->city(),
            'country' => fake()->country(),
            'country_code' => $countryCode,
            'type' => 'PORT',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'timezone' => 'UTC',
            'currency_code' => 'USD',
            'weather_snapshot' => [
                'temperature_c' => 24.0,
                'condition' => 'Clear',
                'humidity' => 60,
                'wind_kph' => 15.0,
            ],
            'news_snapshot' => null,
        ];
    }
}

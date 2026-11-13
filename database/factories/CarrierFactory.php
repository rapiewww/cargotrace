<?php

namespace Database\Factories;

use App\Models\Carrier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrier>
 */
class CarrierFactory extends Factory
{
    protected $model = Carrier::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???U'));

        return [
            'name' => fake()->company().' Shipping Line',
            'code' => $code,
            'headquarters_country' => fake()->countryCode(),
            'contact_email' => fake()->safeEmail(),
            'website' => 'https://www.'.strtolower($code).'-shipping.com',
            'mode' => 'SEA',
            'is_active' => true,
        ];
    }
}

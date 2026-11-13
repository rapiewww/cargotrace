<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Support\Iso6346;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentItem>
 */
class ShipmentItemFactory extends Factory
{
    protected $model = ShipmentItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ownerPrefixes = ['MSK', 'CMA', 'HLX', 'MSC', 'ONE', 'COS', 'EMC'];
        $owner = fake()->randomElement($ownerPrefixes);
        $serial = fake()->unique()->numberBetween(100000, 999999);
        $containerNumber = Iso6346::generate($owner, $serial);

        return [
            'shipment_id' => Shipment::factory(),
            'container_number' => $containerNumber,
            'sku' => $containerNumber,
            'description' => fake()->randomElement([
                '40ft HC Container: Electronic Sub-Assemblies & Circuit Modules',
                '20ft Dry Container: Precision CNC Machined Mechanical Parts',
                '40ft Reefer Container: Temperature-Controlled Pharmaceuticals',
                '40ft Dry Container: Industrial Ceramic Tiles & Glazes',
                '20ft Heavy Container: High-Purity Copper Cathodes Grade A',
            ]),
            'quantity' => fake()->numberBetween(10, 500),
            'unit_value' => fake()->randomFloat(2, 50, 2500),
            'currency_code' => 'USD',
            'weight_kg' => fake()->randomFloat(2, 5000, 26000),
            'volume_cbm' => fake()->randomFloat(2, 25, 68),
            'is_hazardous' => fake()->boolean(10),
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('shipments are searchable by container number on web index', function () {
    $operator = User::where('role', 'operator')->first();
    $shipment = Shipment::first();

    // Assign a known valid ISO 6346 container number
    $containerNumber = 'MSKU1234565';
    $shipment->items()->create([
        'container_number' => $containerNumber,
        'sku' => $containerNumber,
        'description' => 'Test container consignment',
        'quantity' => 1,
        'weight_kg' => 15000,
        'volume_cbm' => 45,
    ]);

    // Search with raw unformatted container number
    $response = $this->actingAs($operator)->get('/shipments?search=MSKU1234565');
    $response->assertStatus(200);
    $response->assertSee($shipment->tracking_number);

    // Search with formatted container number ("MSKU 123456 5")
    $responseFormatted = $this->actingAs($operator)->get('/shipments?search=MSKU%20123456%205');
    $responseFormatted->assertStatus(200);
    $responseFormatted->assertSee($shipment->tracking_number);
});

test('shipments are searchable by container number via JSON API', function () {
    $operator = User::where('role', 'operator')->first();
    $shipment = Shipment::first();

    $containerNumber = 'CSQU3054383';
    $shipment->items()->create([
        'container_number' => $containerNumber,
        'sku' => $containerNumber,
        'description' => 'Test API container search',
        'quantity' => 1,
        'weight_kg' => 20000,
        'volume_cbm' => 55,
    ]);

    $response = $this->actingAs($operator, 'sanctum')->getJson('/api/shipments?search=CSQU3054383');
    $response->assertStatus(200);
    $response->assertJsonFragment(['tracking_number' => $shipment->tracking_number]);
});

test('public tracking page can resolve consignment by container number', function () {
    $shipment = Shipment::first();
    $containerNumber = 'HLXU1000007';

    $shipment->items()->create([
        'container_number' => $containerNumber,
        'sku' => $containerNumber,
        'description' => 'Public tracking test container',
        'quantity' => 1,
        'weight_kg' => 12000,
        'volume_cbm' => 40,
    ]);

    // Visit public tracking with container number
    $response = $this->get('/track/HLXU1000007');
    $response->assertStatus(200);
    $response->assertSee($shipment->tracking_number);
});

test('shipment creation accepts valid ISO 6346 container number and rejects invalid check digit', function () {
    $operator = User::where('role', 'operator')->first();
    $carrier = Carrier::first();
    $origin = Location::where('unlocode', 'IDJKT')->first();
    $dest = Location::where('unlocode', 'NLRTM')->first();

    $basePayload = [
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $dest->id,
        'cargo_type' => 'FCL',
        'expected_departure_at' => Carbon::now()->addDays(1)->toDateTimeString(),
        'expected_arrival_at' => Carbon::now()->addDays(20)->toDateTimeString(),
    ];

    // 1. Invalid check digit: MSKU1234560 (check digit should be 5)
    $invalidPayload = array_merge($basePayload, [
        'items' => [
            [
                'description' => 'Electronics',
                'container_number' => 'MSKU1234560',
                'quantity' => 1,
                'weight_kg' => 5000,
                'volume_cbm' => 20,
            ],
        ],
    ]);

    $respInvalid = $this->actingAs($operator)->post('/shipments', $invalidPayload);
    $respInvalid->assertSessionHasErrors('items.0.container_number');

    // 2. Malformed format: MSKA1234565 (category 'A' is not U, J, or Z)
    $malformedPayload = array_merge($basePayload, [
        'items' => [
            [
                'description' => 'Electronics',
                'container_number' => 'MSKA1234565',
                'quantity' => 1,
                'weight_kg' => 5000,
                'volume_cbm' => 20,
            ],
        ],
    ]);

    $respMalformed = $this->actingAs($operator)->post('/shipments', $malformedPayload);
    $respMalformed->assertSessionHasErrors('items.0.container_number');

    // 3. Valid container number: MSKU1234565
    $validPayload = array_merge($basePayload, [
        'items' => [
            [
                'description' => 'Electronics',
                'container_number' => 'MSKU 123456 5',
                'quantity' => 1,
                'weight_kg' => 5000,
                'volume_cbm' => 20,
            ],
        ],
    ]);

    $respValid = $this->actingAs($operator)->post('/shipments', $validPayload);
    $respValid->assertSessionHasNoErrors();
    $respValid->assertRedirect();

    $this->assertDatabaseHas('shipment_items', [
        'container_number' => 'MSKU1234565',
    ]);
});

test('shipment detail displays free time countdown and over-limit badge', function () {
    $operator = User::where('role', 'operator')->first();
    $shipment = Shipment::where('status', 'DELIVERED')->first();

    // Set arrival 10 days ago (with 5-day limit, 5 days over)
    $shipment->update([
        'actual_arrival_at' => Carbon::now()->subDays(10),
    ]);

    $response = $this->actingAs($operator)->get('/shipments/'.$shipment->id);
    $response->assertStatus(200);
    $response->assertSee('over free time limit');
});

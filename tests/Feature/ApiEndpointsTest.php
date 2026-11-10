<?php

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('POST /api/register creates customer user and returns Sanctum token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Global Logistics Buyer Inc.',
        'email' => 'buyer@globallogistics.test',
        'password' => 'secretPassword123!',
        'password_confirmation' => 'secretPassword123!',
        'company_name' => 'Global Logistics Buyer Corp',
        'phone' => '+1-555-900-1122',
        'preferred_currency' => 'USD',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'token',
            'user' => ['id', 'name', 'email', 'role', 'company_name', 'phone', 'preferred_currency'],
        ])
        ->assertJson([
            'status' => 'success',
            'user' => [
                'email' => 'buyer@globallogistics.test',
                'role' => 'customer',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'buyer@globallogistics.test',
        'role' => 'customer',
    ]);
});

test('POST /api/login validates credentials and issues token', function () {
    // Valid login
    $response = $this->postJson('/api/login', [
        'email' => 'operator@cargotrace.test',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['status', 'token', 'user'])
        ->assertJson([
            'status' => 'success',
            'user' => [
                'email' => 'operator@cargotrace.test',
                'role' => 'operator',
            ],
        ]);

    // Invalid credentials
    $this->postJson('/api/login', [
        'email' => 'operator@cargotrace.test',
        'password' => 'wrong-password',
    ])->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Invalid email or password credentials.',
        ]);
});

test('POST /api/logout and GET /api/user require Sanctum authentication', function () {
    $user = User::where('email', 'admin@cargotrace.test')->first();
    $token = $user->createToken('test-token')->plainTextToken;

    // Unauthenticated
    $this->getJson('/api/user')->assertStatus(401);
    $this->postJson('/api/logout')->assertStatus(401);

    // Authenticated
    $this->withToken($token)
        ->getJson('/api/user')
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'user' => [
                'email' => 'admin@cargotrace.test',
                'role' => 'admin',
            ],
        ]);

    // Logout
    $this->withToken($token)
        ->postJson('/api/logout')
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
});

test('GET /api/shipments enforces customer isolation and server-side filtering', function () {
    $customer1 = User::where('email', 'customer@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();

    // Customer only sees their shipments
    $response = $this->actingAs($customer1, 'sanctum')->getJson('/api/shipments');
    $response->assertStatus(200);

    $customer1ShipmentCount = Shipment::where('customer_id', $customer1->id)->count();
    expect($response->json('data'))->toHaveCount($customer1ShipmentCount);

    foreach ($response->json('data') as $item) {
        expect($item['customer_id'])->toBe($customer1->id);
    }

    // Operator sees all shipments with filters
    $opResponse = $this->actingAs($operator, 'sanctum')->getJson('/api/shipments?status=IN_TRANSIT');
    $opResponse->assertStatus(200);

    foreach ($opResponse->json('data') as $item) {
        expect($item['status'])->toBe('IN_TRANSIT');
    }
});

test('POST /api/shipments creates shipment with items and currency conversion', function () {
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $customer1 = User::where('email', 'customer@cargotrace.test')->first();
    $carrier = Carrier::first();
    $origin = Location::where('unlocode', 'IDJKT')->first();
    $destination = Location::where('unlocode', 'NLRTM')->first();

    $payload = [
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destination->id,
        'customer_id' => $customer1->id,
        'cargo_type' => 'FCL',
        'currency_code' => 'EUR',
        'declared_value' => 250000,
        'shipping_cost' => 8500,
        'expected_departure_at' => Carbon::now()->addDays(1)->toIso8601String(),
        'expected_arrival_at' => Carbon::now()->addDays(25)->toIso8601String(),
        'reference_number' => 'PO-TEST-9988',
        'items' => [
            [
                'sku' => 'SKU-TEST-A',
                'description' => 'Precision Medical Pumps',
                'quantity' => 50,
                'unit_value' => 5000,
                'weight_kg' => 2500,
                'volume_cbm' => 12.5,
                'is_hazardous' => false,
            ],
        ],
    ];

    // Customer forbidden to create
    $this->actingAs($customer1, 'sanctum')
        ->postJson('/api/shipments', $payload)
        ->assertStatus(403);

    // Operator allowed to create
    $response = $this->actingAs($operator, 'sanctum')
        ->postJson('/api/shipments', $payload);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'tracking_number',
                'status',
                'declared_value',
                'converted_value_usd',
                'items',
            ],
        ])
        ->assertJson([
            'status' => 'success',
            'data' => [
                'status' => 'CREATED',
                'declared_value' => 250000,
            ],
        ]);

    expect($response->json('data.converted_value_usd'))->toBeGreaterThan(0);
});

test('GET /api/shipments/{id} blocks unauthorized customer and allows authorized parties', function () {
    $customer1 = User::where('email', 'customer@cargotrace.test')->first();
    $customer2 = User::where('email', 'customer2@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();

    $shipmentOfCustomer2 = Shipment::where('customer_id', $customer2->id)->first();

    // Customer 1 accessing Customer 2's shipment -> 403 Forbidden
    $this->actingAs($customer1, 'sanctum')
        ->getJson("/api/shipments/{$shipmentOfCustomer2->id}")
        ->assertStatus(403);

    // Customer 2 accessing their own shipment -> 200 OK
    $this->actingAs($customer2, 'sanctum')
        ->getJson("/api/shipments/{$shipmentOfCustomer2->id}")
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'id' => $shipmentOfCustomer2->id,
                'customer_id' => $customer2->id,
            ],
        ]);

    // Operator accessing any shipment -> 200 OK
    $this->actingAs($operator, 'sanctum')
        ->getJson("/api/shipments/{$shipmentOfCustomer2->id}")
        ->assertStatus(200);
});

test('PUT/PATCH /api/shipments/{id} and DELETE /api/shipments/{id} enforce role authorizations', function () {
    $customer = User::where('email', 'customer@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $admin = User::where('email', 'admin@cargotrace.test')->first();

    $shipment = Shipment::where('status', 'CREATED')->first();

    // Customer cannot update
    $this->actingAs($customer, 'sanctum')
        ->patchJson("/api/shipments/{$shipment->id}", ['status' => 'BOOKED'])
        ->assertStatus(403);

    // Operator can update status (CREATED -> BOOKED)
    $this->actingAs($operator, 'sanctum')
        ->patchJson("/api/shipments/{$shipment->id}", [
            'status' => 'BOOKED',
            'description' => 'Carrier booking assigned.',
        ])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => ['status' => 'BOOKED'],
        ]);

    // Operator cannot delete -> 403
    $this->actingAs($operator, 'sanctum')
        ->deleteJson("/api/shipments/{$shipment->id}")
        ->assertStatus(403);

    // Admin can delete -> 200
    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/shipments/{$shipment->id}")
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);

    $this->assertDatabaseMissing('shipments', ['id' => $shipment->id]);
});

test('Tracking and Exception API endpoints function with validation', function () {
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $shipment = Shipment::first();
    $location = Location::first();

    // GET Tracking
    $this->actingAs($operator, 'sanctum')
        ->getJson("/api/shipments/{$shipment->id}/tracking")
        ->assertStatus(200)
        ->assertJsonStructure(['status', 'events']);

    // POST Tracking
    $response = $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$shipment->id}/tracking", [
            'title' => 'Vessel Docked at Berth 4',
            'location_id' => $location->id,
            'description' => 'Container offloading underway.',
            'occurred_at' => Carbon::now()->addHours(2)->toIso8601String(),
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'data' => ['id', 'title', 'weather_snapshot'],
        ]);

    // POST Exception
    $excResponse = $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$shipment->id}/exceptions", [
            'exception_code' => 'PORT_CONGESTION',
            'severity' => 'MEDIUM',
            'title' => 'Berth Queue Congestion',
            'description' => 'Waiting for pilotage.',
        ]);

    $excResponse->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'exception_code' => 'PORT_CONGESTION',
                'severity' => 'MEDIUM',
                'status' => 'OPEN',
            ],
        ]);

    $exceptionId = $excResponse->json('data.id');

    // PATCH Exception Status
    $this->actingAs($operator, 'sanctum')
        ->patchJson("/api/exceptions/{$exceptionId}", [
            'status' => 'RESOLVED',
            'resolution_notes' => 'Berth cleared and vessel moored.',
        ])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'success',
            'data' => [
                'status' => 'RESOLVED',
                'resolution_notes' => 'Berth cleared and vessel moored.',
            ],
        ]);
});

test('Master data and External integration API endpoints return verified responses', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();

    // GET Carriers & Locations
    $this->actingAs($operator, 'sanctum')->getJson('/api/carriers')->assertStatus(200);
    $this->actingAs($operator, 'sanctum')->getJson('/api/locations')->assertStatus(200);

    // POST Carrier by Admin
    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/carriers', [
            'code' => 'CMA',
            'name' => 'CMA CGM Group',
            'mode' => 'SEA',
            'headquarters_country' => 'France',
        ])
        ->assertStatus(201)
        ->assertJson([
            'status' => 'success',
            'data' => ['code' => 'CMA'],
        ]);

    // Weather endpoint
    $this->actingAs($operator, 'sanctum')
        ->getJson('/api/integrations/weather?lat=1.26&lon=103.82')
        ->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => ['temperature_c', 'condition', 'wind_kph', 'source'],
        ]);

    // Currency endpoint
    $this->actingAs($operator, 'sanctum')
        ->getJson('/api/integrations/currency?amount=1000&from=EUR&to=USD')
        ->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => ['amount', 'from_currency', 'to_currency', 'exchange_rate', 'converted_amount'],
        ]);

    // News endpoint
    $this->actingAs($operator, 'sanctum')
        ->getJson('/api/integrations/news?query=shipping')
        ->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'query',
            'data',
        ]);
});

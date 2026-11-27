<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('guest cannot access dashboard map data endpoint', function () {
    $response = $this->getJson('/dashboard/map-data');

    $response->assertStatus(401);
});

test('dashboard map data returns expected structure and config tile metadata', function () {
    $operator = User::where('role', 'operator')->firstOrFail();

    $response = $this->actingAs($operator)->getJson('/dashboard/map-data');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'shipments' => [
                    '*' => [
                        'id',
                        'tracking_number',
                        'mode',
                        'mode_label',
                        'status',
                        'status_label',
                        'is_delayed',
                        'delay_hours',
                        'delay_severity',
                        'has_exception',
                        'open_exceptions_count',
                        'origin' => ['id', 'name', 'short_name', 'code', 'lat', 'lng'],
                        'destination' => ['id', 'name', 'short_name', 'code', 'lat', 'lng'],
                        'latest_tracking' => ['location_name', 'lat', 'lng', 'occurred_at', 'occurred_at_formatted'],
                        'url',
                    ],
                ],
                'locations' => [
                    '*' => ['id', 'name', 'code', 'latitude', 'longitude'],
                ],
                'filters' => ['mode', 'filter'],
                'meta' => ['total', 'role', 'tile_url', 'tile_attribution'],
            ],
        ]);

    $payload = $response->json('data');

    expect($payload['meta']['tile_url'])->toBe(config('cargotrace.map.tile_url'))
        ->and($payload['meta']['tile_attribution'])->toContain('OpenStreetMap')
        ->and($payload['meta']['total'])->toBe(count($payload['shipments']))
        ->and($payload['meta']['total'])->toBeGreaterThan(0);
});

test('customer only receives their own shipments in map data endpoint', function () {
    $customer1 = User::where('role', 'customer')->orderBy('id')->firstOrFail();
    $customer2 = User::where('role', 'customer')->where('id', '!=', $customer1->id)->firstOrFail();
    $admin = User::where('role', 'admin')->firstOrFail();

    $customer1ActiveIds = Shipment::where('customer_id', $customer1->id)
        ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
        ->pluck('tracking_number')
        ->all();

    $customer2ActiveTracking = Shipment::where('customer_id', $customer2->id)
        ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
        ->pluck('tracking_number')
        ->all();

    expect($customer1ActiveIds)->not->toBeEmpty()
        ->and($customer2ActiveTracking)->not->toBeEmpty();

    $customerRes = $this->actingAs($customer1)->getJson('/dashboard/map-data');
    $customerRes->assertStatus(200);

    $returnedTracking = collect($customerRes->json('data.shipments'))->pluck('tracking_number')->all();

    expect(count($returnedTracking))->toBe(count($customer1ActiveIds));

    foreach ($customer2ActiveTracking as $otherTracking) {
        expect($returnedTracking)->not->toContain($otherTracking);
    }

    $adminRes = $this->actingAs($admin)->getJson('/dashboard/map-data');
    $adminRes->assertStatus(200);
    $adminTracking = collect($adminRes->json('data.shipments'))->pluck('tracking_number')->all();

    expect(count($adminTracking))->toBeGreaterThan(count($returnedTracking));
});

test('map data endpoint filters by transport mode', function () {
    $operator = User::where('role', 'operator')->firstOrFail();

    foreach (['SEA', 'AIR', 'ROAD'] as $mode) {
        $res = $this->actingAs($operator)->getJson('/dashboard/map-data?mode='.$mode);
        $res->assertStatus(200);

        $shipments = $res->json('data.shipments');
        expect($shipments)->not->toBeEmpty();
        expect($res->json('data.filters.mode'))->toBe($mode);

        foreach ($shipments as $item) {
            expect($item['mode'])->toBe($mode);
        }
    }
});

test('map data endpoint filters by delayed and exceptions', function () {
    $operator = User::where('role', 'operator')->firstOrFail();

    $delayedRes = $this->actingAs($operator)->getJson('/dashboard/map-data?filter=DELAYED');
    $delayedRes->assertStatus(200);
    $delayedShipments = $delayedRes->json('data.shipments');
    expect($delayedShipments)->not->toBeEmpty()
        ->and($delayedRes->json('data.filters.filter'))->toBe('DELAYED');

    foreach ($delayedShipments as $item) {
        expect($item['is_delayed'])->toBeTrue();
    }

    $exceptionsRes = $this->actingAs($operator)->getJson('/dashboard/map-data?filter=EXCEPTIONS');
    $exceptionsRes->assertStatus(200);
    $exceptionShipments = $exceptionsRes->json('data.shipments');
    expect($exceptionShipments)->not->toBeEmpty()
        ->and($exceptionsRes->json('data.filters.filter'))->toBe('EXCEPTIONS');

    foreach ($exceptionShipments as $item) {
        expect($item['has_exception'])->toBeTrue()
            ->and($item['open_exceptions_count'])->toBeGreaterThan(0);
    }

    $seaDelayedRes = $this->actingAs($operator)->getJson('/dashboard/map-data?mode=SEA&filter=DELAYED');
    $seaDelayedRes->assertStatus(200);
    foreach ($seaDelayedRes->json('data.shipments') as $item) {
        expect($item['mode'])->toBe('SEA')
            ->and($item['is_delayed'])->toBeTrue();
    }
});

test('map data endpoint returns empty arrays when no shipment matches', function () {
    $emptyCustomer = User::factory()->create([
        'role' => 'customer',
        'is_active' => true,
    ]);

    $res = $this->actingAs($emptyCustomer)->getJson('/dashboard/map-data?mode=AIR&filter=EXCEPTIONS');

    $res->assertStatus(200)
        ->assertJsonPath('data.shipments', [])
        ->assertJsonPath('data.locations', [])
        ->assertJsonPath('data.meta.total', 0)
        ->assertJsonPath('data.filters.mode', 'AIR')
        ->assertJsonPath('data.filters.filter', 'EXCEPTIONS');
});

test('dashboard page contains 2d leaflet container, config tile meta, empty state, and scoped data source', function () {
    $customer1 = User::where('role', 'customer')->orderBy('id')->firstOrFail();
    $customer2 = User::where('role', 'customer')->where('id', '!=', $customer1->id)->firstOrFail();

    $customer2Tracking = Shipment::where('customer_id', $customer2->id)
        ->pluck('tracking_number')
        ->first();

    $res = $this->actingAs($customer1)->get('/dashboard');

    $res->assertStatus(200)
        ->assertSee('id="globe-2d-canvas"', false)
        ->assertSee('id="globe-3d-canvas"', false)
        ->assertSee('data-map-data-url="'.route('dashboard.map-data').'"', false)
        ->assertSee('id="map-empty-state"', false)
        ->assertSee('No routes match this filter')
        ->assertSee('<meta name="map-tile-url" content="'.config('cargotrace.map.tile_url').'">', false)
        ->assertSee('OpenStreetMap');

    if ($customer2Tracking) {
        $res->assertDontSee($customer2Tracking);
    }
});

test('map data endpoint excludes zero or invalid coordinates from shipments, locations, and tracking points', function () {
    $operator = User::where('role', 'operator')->firstOrFail();
    $customer = User::where('role', 'customer')->firstOrFail();
    $carrier = Carrier::firstOrFail();
    $validOrigin = Location::where('unlocode', 'IDJKT')->firstOrFail();
    $validDestination = Location::where('unlocode', 'NLRTM')->firstOrFail();

    $nullIslandLoc = Location::create([
        'unlocode' => 'ZZ000',
        'name' => 'Null Island Invalid Port',
        'city' => 'Null Island',
        'country' => 'Atlantic',
        'country_code' => 'ZZ',
        'currency_code' => 'USD',
        'type' => 'PORT',
        'latitude' => 0.0,
        'longitude' => 0.0,
    ]);

    $invalidRouteShipment = Shipment::create([
        'tracking_number' => 'CTG-SEA-2026-99001',
        'transport_mode' => 'SEA',
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $validOrigin->id,
        'destination_location_id' => $nullIslandLoc->id,
        'current_location_id' => $nullIslandLoc->id,
        'cargo_type' => 'FCL',
        'total_weight_kg' => 1000,
        'total_volume_cbm' => 10,
        'declared_value' => 5000,
        'currency_code' => 'USD',
        'shipping_cost' => 1000,
        'exchange_rate_to_usd' => 1,
        'converted_value_usd' => 5000,
        'status' => 'IN_TRANSIT',
        'expected_departure_at' => now()->subDays(2),
        'expected_arrival_at' => now()->addDays(5),
    ]);

    $invalidEventShipment = Shipment::create([
        'tracking_number' => 'CTG-SEA-2026-99002',
        'transport_mode' => 'SEA',
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $validOrigin->id,
        'destination_location_id' => $validDestination->id,
        'current_location_id' => $nullIslandLoc->id,
        'cargo_type' => 'FCL',
        'total_weight_kg' => 1200,
        'total_volume_cbm' => 12,
        'declared_value' => 6000,
        'currency_code' => 'USD',
        'shipping_cost' => 1100,
        'exchange_rate_to_usd' => 1,
        'converted_value_usd' => 6000,
        'status' => 'IN_TRANSIT',
        'expected_departure_at' => now()->subDays(2),
        'expected_arrival_at' => now()->addDays(6),
    ]);

    TrackingEvent::create([
        'shipment_id' => $invalidEventShipment->id,
        'location_id' => $nullIslandLoc->id,
        'status' => 'IN_TRANSIT',
        'event_type' => 'NORMAL',
        'title' => 'Invalid Checkpoint',
        'latitude' => 0.0,
        'longitude' => 0.0,
        'occurred_at' => now(),
    ]);

    $res = $this->actingAs($operator)->getJson('/dashboard/map-data');
    $res->assertStatus(200);

    $payload = $res->json('data');
    $shipments = collect($payload['shipments']);
    $locations = collect($payload['locations']);

    expect($payload['meta']['total'])->toBe($shipments->count())
        ->and($shipments->pluck('tracking_number')->all())->not->toContain($invalidRouteShipment->tracking_number)
        ->and($locations->pluck('code')->all())->not->toContain('ZZ000');

    $matched = $shipments->firstWhere('tracking_number', $invalidEventShipment->tracking_number);
    expect($matched)->not->toBeNull()
        ->and($matched['latest_tracking'])->toBeNull()
        ->and($matched['current'])->toBeNull();
});

test('landing page excludes zero or invalid coordinates from world map payload', function () {
    $customer = User::where('role', 'customer')->firstOrFail();
    $carrier = Carrier::firstOrFail();
    $validOrigin = Location::where('unlocode', 'IDJKT')->firstOrFail();

    $nullIslandLoc = Location::create([
        'unlocode' => 'ZZ999',
        'name' => 'Null Island Landing Port',
        'city' => 'Null Island',
        'country' => 'Atlantic',
        'country_code' => 'ZZ',
        'currency_code' => 'USD',
        'type' => 'PORT',
        'latitude' => 0.0,
        'longitude' => 0.0,
    ]);

    $invalidLandingShipment = Shipment::create([
        'tracking_number' => 'CTG-SEA-2026-99003',
        'transport_mode' => 'SEA',
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $validOrigin->id,
        'destination_location_id' => $nullIslandLoc->id,
        'cargo_type' => 'FCL',
        'total_weight_kg' => 900,
        'total_volume_cbm' => 9,
        'declared_value' => 4000,
        'currency_code' => 'USD',
        'shipping_cost' => 900,
        'exchange_rate_to_usd' => 1,
        'converted_value_usd' => 4000,
        'status' => 'IN_TRANSIT',
        'expected_departure_at' => now()->subDays(1),
        'expected_arrival_at' => now()->addDays(4),
    ]);

    $res = $this->get('/');
    $res->assertStatus(200)
        ->assertDontSee('ZZ999')
        ->assertDontSee($invalidLandingShipment->tracking_number);
});

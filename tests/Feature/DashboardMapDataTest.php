<?php

namespace Tests\Feature;

use App\Models\Shipment;
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

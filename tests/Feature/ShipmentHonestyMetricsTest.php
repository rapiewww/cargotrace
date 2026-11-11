<?php

use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('great circle distance attribute accurately computes nautical miles between ports', function () {
    $shipment = Shipment::with(['originLocation', 'destinationLocation'])->first();

    $orig = $shipment->originLocation;
    $dest = $shipment->destinationLocation;

    expect($orig)->not->toBeNull();
    expect($dest)->not->toBeNull();

    $nm = $shipment->great_circle_distance_nm;
    expect($nm)->toBeInt()->toBeGreaterThan(500);
});

test('great circle distance returns null when location coordinates are missing or relations null', function () {
    $shipment = new Shipment;
    expect($shipment->great_circle_distance_nm)->toBeNull();

    $orig = new Location(['latitude' => null, 'longitude' => null]);
    $dest = new Location(['latitude' => 51.9, 'longitude' => 4.0]);
    $shipment->setRelation('originLocation', $orig);
    $shipment->setRelation('destinationLocation', $dest);

    expect($shipment->great_circle_distance_nm)->toBeNull();
});

test('dashboard view shows computed exception rate and truthful metrics without AIS claims', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertDontSee('Real-time AIS vessel telemetry');
    $response->assertDontSee('LIVE AIS TRACKING');
    $response->assertDontSee('Risk of Cargo Loss');
    $response->assertDontSee('28,450 TEU');
    $response->assertSee('Operator-recorded checkpoints');
    $response->assertSee('Exception Rate');
});

<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('shipments list renders mode filter chips and filters by transport mode', function () {
    $operator = User::where('role', 'operator')->first();

    // Verify filter chips appear on index
    $res = $this->actingAs($operator)->get('/shipments');
    $res->assertStatus(200)
        ->assertSee('All modes')
        ->assertSee('Sea')
        ->assertSee('Air')
        ->assertSee('Road')
        ->assertSee('Reference');

    // Filter by AIR
    $airShipment = Shipment::where('transport_mode', 'AIR')->first();
    expect($airShipment)->not->toBeNull();

    $resAir = $this->actingAs($operator)->get('/shipments?mode=AIR');
    $resAir->assertStatus(200)
        ->assertSee($airShipment->tracking_number);

    // Filter by ROAD
    $roadShipment = Shipment::where('transport_mode', 'ROAD')->first();
    expect($roadShipment)->not->toBeNull();

    $resRoad = $this->actingAs($operator)->get('/shipments?mode=ROAD');
    $resRoad->assertStatus(200)
        ->assertSee($roadShipment->tracking_number);
});

test('shipments list search box matches any reference field including AWB and vehicle plate', function () {
    $operator = User::where('role', 'operator')->first();
    $airShipment = Shipment::where('transport_mode', 'AIR')->first();
    $roadShipment = Shipment::where('transport_mode', 'ROAD')->first();

    // Search by AWB
    $resAwb = $this->actingAs($operator)->get('/shipments?search='.$airShipment->awb_number);
    $resAwb->assertStatus(200)
        ->assertSee($airShipment->tracking_number);

    // Search by vehicle plate
    $resPlate = $this->actingAs($operator)->get('/shipments?search='.$roadShipment->vehicle_plate);
    $resPlate->assertStatus(200)
        ->assertSee($roadShipment->tracking_number);
});

test('shipment create form displays mode selector and initial mode fields', function () {
    $operator = User::where('role', 'operator')->first();

    $res = $this->actingAs($operator)->get('/shipments/create');
    $res->assertStatus(200)
        ->assertSee('Primary Transport Mode')
        ->assertSee('Ocean Freight (SEA)')
        ->assertSee('Air Freight (AIR)')
        ->assertSee('Road Freight (ROAD)')
        ->assertSee('Container Number (ISO 6346)')
        ->assertSee('Air Waybill Number (AWB)')
        ->assertSee('Vehicle License Plate')
        ->assertSee('CMR / Consignment Note Number');
});

test('shipment edit form displays mode selector and mode references', function () {
    $operator = User::where('role', 'operator')->first();
    $airShipment = Shipment::where('transport_mode', 'AIR')->first();

    $res = $this->actingAs($operator)->get('/shipments/'.$airShipment->id.'/edit');
    $res->assertStatus(200)
        ->assertSee('Edit Cargo Manifest')
        ->assertSee($airShipment->tracking_number)
        ->assertSee('Primary Transport Mode')
        ->assertSee('Air Waybill Number (AWB)');
});

test('shipment detail view renders mode badge, reference block, and per-mode status label', function () {
    $operator = User::where('role', 'operator')->first();

    // 1. AIR shipment detail
    $airShipment = Shipment::where('transport_mode', 'AIR')->first();
    $airShipment->update(['status' => 'PICKED_UP']);

    $resAir = $this->actingAs($operator)->get('/shipments/'.$airShipment->id);
    $resAir->assertStatus(200)
        ->assertSee('Air Waybill (AWB)')
        ->assertSee('Received at Origin Airport')
        ->assertSee('Flight Space Booked')
        ->assertSee('FLIGHT_OFFLOAD');

    // 2. ROAD shipment detail
    $roadShipment = Shipment::where('transport_mode', 'ROAD')->first();
    $roadShipment->update(['status' => 'PICKED_UP']);

    $resRoad = $this->actingAs($operator)->get('/shipments/'.$roadShipment->id);
    $resRoad->assertStatus(200)
        ->assertSee('Vehicle License Plate')
        ->assertSee('Loaded at Depot / Warehouse')
        ->assertSee('BORDER_QUEUE')
        ->assertSee('VEHICLE_BREAKDOWN');
});

test('admin settings page displays three mode cards with unit in hours and saves changes', function () {
    $admin = User::where('role', 'admin')->first();

    $res = $this->actingAs($admin)->get('/admin/settings');
    $res->assertStatus(200)
        ->assertSee('Sea Freight (SEA)')
        ->assertSee('Air Freight (AIR)')
        ->assertSee('Road Freight (ROAD)')
        ->assertSee('Low threshold (hours)')
        ->assertSee('Medium threshold (hours)');

    // Update settings
    $postRes = $this->actingAs($admin)->post('/admin/settings', [
        'delay_sea_low_hours' => 14.0,
        'delay_sea_medium_hours' => 52.0,
        'delay_air_low_hours' => 4.0,
        'delay_air_medium_hours' => 14.0,
        'delay_road_low_hours' => 8.0,
        'delay_road_medium_hours' => 28.0,
        'free_days_after_arrival' => 7,
    ]);

    $postRes->assertRedirect('/admin/settings')
        ->assertSessionHas('success');

    expect((float) Setting::get('delay.sea.low_hours'))->toBe(14.0)
        ->and((float) Setting::get('delay.air.low_hours'))->toBe(4.0)
        ->and((float) Setting::get('delay.road.low_hours'))->toBe(8.0)
        ->and((int) Setting::get('free_days_after_arrival'))->toBe(7);
});

test('dashboard view renders mode cards and mode tabs on map', function () {
    $operator = User::where('role', 'operator')->first();

    $res = $this->actingAs($operator)->get('/dashboard');
    $res->assertStatus(200)
        ->assertSee('Ocean freight (Sea)')
        ->assertSee('Air freight (Air)')
        ->assertSee('Road freight (Road)')
        ->assertSee('Freight activities')
        ->assertSee('tab-filter-sea', false)
        ->assertSee('tab-filter-air', false)
        ->assertSee('tab-filter-road', false);
});

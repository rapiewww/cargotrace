<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('admin can create and update carrier', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->post('/carriers', [
        'code' => 'TESTC',
        'name' => 'Test Maritime Freight Lines',
        'mode' => 'SEA',
        'headquarters_country' => 'Norway',
        'contact_email' => 'ops@testc.com',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('carriers', ['code' => 'TESTC']);

    $carrier = Carrier::where('code', 'TESTC')->first();

    $updateResp = $this->actingAs($admin)->put('/carriers/' . $carrier->id, [
        'code' => 'TESTC',
        'name' => 'Test Maritime Freight Lines Global',
        'mode' => 'MULTIMODAL',
        'headquarters_country' => 'Norway',
    ]);

    $updateResp->assertSessionHasNoErrors();
    $this->assertDatabaseHas('carriers', [
        'id' => $carrier->id,
        'name' => 'Test Maritime Freight Lines Global',
        'mode' => 'MULTIMODAL',
    ]);
});

test('admin cannot delete carrier assigned to existing shipments', function () {
    $admin = User::where('role', 'admin')->first();
    $carrierWithShipments = Carrier::has('shipments')->first();

    $response = $this->actingAs($admin)->delete('/carriers/' . $carrierWithShipments->id);

    $response->assertSessionHasErrors('carrier');
    $this->assertDatabaseHas('carriers', ['id' => $carrierWithShipments->id]);
});

test('admin can delete unreferenced carrier', function () {
    $admin = User::where('role', 'admin')->first();
    $unusedCarrier = Carrier::create([
        'code' => 'UNUSED',
        'name' => 'Unused Carrier Lines',
        'mode' => 'LAND',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete('/carriers/' . $unusedCarrier->id);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseMissing('carriers', ['id' => $unusedCarrier->id]);
});

test('admin cannot delete location referenced in shipment routes', function () {
    $admin = User::where('role', 'admin')->first();
    $activeLocation = Location::has('originShipments')->first();

    $response = $this->actingAs($admin)->delete('/locations/' . $activeLocation->id);

    $response->assertSessionHasErrors('location');
    $this->assertDatabaseHas('locations', ['id' => $activeLocation->id]);
});

test('operator cannot delete carrier or location', function () {
    $operator = User::where('role', 'operator')->first();
    $carrier = Carrier::first();
    $location = Location::first();

    $this->actingAs($operator)->delete('/carriers/' . $carrier->id)->assertStatus(403);
    $this->actingAs($operator)->delete('/locations/' . $location->id)->assertStatus(403);
});

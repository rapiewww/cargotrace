<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('operator can view edit page for shipment', function () {
    $operator = User::where('role', 'operator')->first();
    $shipment = Shipment::first();

    $response = $this->actingAs($operator)->get('/shipments/'.$shipment->id.'/edit');

    $response->assertStatus(200);
    $response->assertSee('Edit Cargo Manifest');
    $response->assertSee($shipment->tracking_number);
});

test('customer cannot access shipment edit page', function () {
    $customer = User::where('role', 'customer')->first();
    $shipment = Shipment::first();

    $this->actingAs($customer)->get('/shipments/'.$shipment->id.'/edit')->assertStatus(403);
});

test('operator can update shipment and audit log is recorded', function () {
    $operator = User::where('role', 'operator')->first();
    $shipment = Shipment::where('status', '!=', 'DELIVERED')->first();
    $dest = Location::where('id', '!=', $shipment->origin_location_id)->first();

    $response = $this->actingAs($operator)->put('/shipments/'.$shipment->id, [
        'carrier_id' => $shipment->carrier_id,
        'origin_location_id' => $shipment->origin_location_id,
        'destination_location_id' => $dest->id,
        'cargo_type' => 'FCL',
        'declared_value' => 75000,
        'currency_code' => 'USD',
        'shipping_cost' => 4500,
        'expected_departure_at' => now()->toDateTimeString(),
        'expected_arrival_at' => now()->addDays(7)->toDateTimeString(),
        'reference_number' => 'REF-EDITED-999',
        'notes' => 'Updated via Web Interface test',
    ]);

    $response->assertRedirect('/shipments/'.$shipment->id);
    $this->assertDatabaseHas('shipments', [
        'id' => $shipment->id,
        'reference_number' => 'REF-EDITED-999',
        'declared_value' => 75000,
        'notes' => 'Updated via Web Interface test',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'SHIPMENT_UPDATED',
        'auditable_id' => $shipment->id,
    ]);
});

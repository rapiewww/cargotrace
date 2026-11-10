<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('customer only sees their own shipments and scoped exceptions in dashboard', function () {
    $customer1 = User::where('role', 'customer')->first();
    $customer2 = User::where('role', 'customer')->where('id', '!=', $customer1->id)->first();

    // Verify customer 1 sees dashboard successfully
    $response = $this->actingAs($customer1)->get('/dashboard');
    $response->assertStatus(200);

    // Get customer 1 shipments count vs all shipments
    $customer1ShipmentsCount = Shipment::where('customer_id', $customer1->id)->count();
    $allShipmentsCount = Shipment::count();

    expect($customer1ShipmentsCount)->toBeLessThan($allShipmentsCount);

    // Customer 1 open exceptions count should only be from their own shipments
    $customer1OpenExceptions = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])
        ->whereHas('shipment', fn($q) => $q->where('customer_id', $customer1->id))
        ->count();

    $response->assertViewHas('totalShipments', $customer1ShipmentsCount);
    $response->assertViewHas('openExceptions', $customer1OpenExceptions);
});

test('public tracking route is accessible without login and masks sensitive customer info', function () {
    $shipment = Shipment::with('customer')->first();

    $response = $this->get('/track/' . $shipment->tracking_number);

    $response->assertStatus(200);
    $response->assertSee($shipment->tracking_number);
    $response->assertSee($shipment->originLocation->unlocode);
    $response->assertSee($shipment->destinationLocation->unlocode);

    // Sensitive customer info should not be exposed on public tracking
    if ($shipment->customer) {
        $response->assertDontSee($shipment->customer->email);
        if ($shipment->customer->phone) {
            $response->assertDontSee($shipment->customer->phone);
        }
    }
});

test('public tracking route returns 404 for invalid tracking number', function () {
    $response = $this->get('/track/NON-EXISTENT-RESI-999');

    $response->assertStatus(404);
});

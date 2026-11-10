<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('operator can export shipments as csv', function () {
    $operator = User::where('role', 'operator')->first();

    $response = $this->actingAs($operator)->get('/shipments/export/csv');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))->toContain('cargotrace-shipments-');

    $content = $response->streamedContent();
    expect($content)->toContain('Tracking Number')
        ->and($content)->toContain('Origin UNLOCODE')
        ->and($content)->toContain('Converted Value USD');
});

test('customer only exports their own shipments in csv', function () {
    $customer1 = User::where('role', 'customer')->first();
    $customer2 = User::where('role', 'customer')->where('id', '!=', $customer1->id)->first();

    $shipmentOfCustomer2 = Shipment::where('customer_id', $customer2->id)->first();

    $response = $this->actingAs($customer1)->get('/shipments/export/csv');

    $response->assertStatus(200);
    $content = $response->streamedContent();

    expect($content)->not->toContain($shipmentOfCustomer2->tracking_number);
});

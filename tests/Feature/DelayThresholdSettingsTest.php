<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use App\Services\EtaService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('admin can access settings page', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->get('/admin/settings');

    $response->assertStatus(200);
    $response->assertSee('ETA Delay Severity Thresholds');
});

test('operator and customer cannot access settings page', function () {
    $operator = User::where('role', 'operator')->first();
    $customer = User::where('role', 'customer')->first();

    $this->actingAs($operator)->get('/admin/settings')->assertStatus(403);
    $this->actingAs($customer)->get('/admin/settings')->assertStatus(403);
});

test('admin can update delay thresholds', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->post('/admin/settings', [
        'delay_low_hours' => 8,
        'delay_medium_hours' => 36,
    ]);

    $response->assertSessionHasNoErrors();
    expect(Setting::get('delay_low_hours'))->toBe('8')
        ->and(Setting::get('delay_medium_hours'))->toBe('36');
});

test('eta service correctly evaluates boundary conditions against settings', function () {
    Setting::set('delay_low_hours', 6);
    Setting::set('delay_medium_hours', 24);

    $etaService = app(EtaService::class);
    $shipment = Shipment::first();
    $baseTime = now();

    // 0h delay
    $shipment->expected_arrival_at = $baseTime;
    $shipment->estimated_arrival_at = $baseTime;
    $etaService->evaluateDelay($shipment);
    expect($shipment->is_delayed)->toBeFalse()
        ->and($shipment->delay_severity)->toBe('NONE');

    // 6h delay (Boundary: <= low)
    $shipment->estimated_arrival_at = $baseTime->copy()->addHours(6);
    $etaService->evaluateDelay($shipment);
    expect($shipment->is_delayed)->toBeTrue()
        ->and($shipment->delay_severity)->toBe('LOW');

    // 6.01h delay (Boundary: > low, <= medium)
    $shipment->estimated_arrival_at = $baseTime->copy()->addHours(6)->addSeconds(36);
    $etaService->evaluateDelay($shipment);
    expect($shipment->is_delayed)->toBeTrue()
        ->and($shipment->delay_severity)->toBe('MEDIUM');

    // 24h delay (Boundary: <= medium)
    $shipment->estimated_arrival_at = $baseTime->copy()->addHours(24);
    $etaService->evaluateDelay($shipment);
    expect($shipment->is_delayed)->toBeTrue()
        ->and($shipment->delay_severity)->toBe('MEDIUM');

    // 24.01h delay (Boundary: > medium => HIGH)
    $shipment->estimated_arrival_at = $baseTime->copy()->addHours(24)->addSeconds(36);
    $etaService->evaluateDelay($shipment);
    expect($shipment->is_delayed)->toBeTrue()
        ->and($shipment->delay_severity)->toBe('HIGH');
});

test('admin can reevaluate active shipments via settings post', function () {
    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->post('/admin/settings/reevaluate');

    $response->assertSessionHas('success');
});

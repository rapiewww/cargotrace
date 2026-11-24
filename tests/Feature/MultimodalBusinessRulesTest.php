<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\EtaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('db:seed');
});

test('Form Request enforces mode-dependent requirements for SEA, AIR, and ROAD', function () {
    $operator = User::where('role', 'operator')->first();
    $customer = User::where('role', 'customer')->first();

    $seaCarrier = Carrier::where('mode', 'SEA')->first();
    $airCarrier = Carrier::where('mode', 'AIR')->first();
    $roadCarrier = Carrier::where('mode', 'ROAD')->first();

    $seaport1 = Location::where('type', 'PORT')->first();
    $seaport2 = Location::where('type', 'PORT')->skip(1)->first();
    $airport1 = Location::where('type', 'AIRPORT')->first();
    $airport2 = Location::where('type', 'AIRPORT')->skip(1)->first();
    $depot = Location::where('type', 'DEPOT')->first();

    // 1. SEA requires valid container_number and matching locations & carrier
    $seaPayload = [
        'transport_mode' => 'SEA',
        'carrier_id' => $seaCarrier->id,
        'origin_location_id' => $seaport1->id,
        'destination_location_id' => $seaport2->id,
        'customer_id' => $customer->id,
        'cargo_type' => 'FCL',
        'expected_departure_at' => now()->addDay()->toDateTimeString(),
        'expected_arrival_at' => now()->addDays(10)->toDateTimeString(),
    ];

    // Fails without container_number
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $seaPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['container_number']);

    // Fails with invalid ISO 6346 container_number
    $seaPayload['container_number'] = 'INVALID123';
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $seaPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['container_number']);

    // Fails if carrier mode does not match SEA
    $seaPayload['container_number'] = 'MSKU1234565';
    $seaPayload['carrier_id'] = $airCarrier->id;
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $seaPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['carrier_id']);

    // Passes when SEA is valid
    $seaPayload['carrier_id'] = $seaCarrier->id;
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $seaPayload)
        ->assertStatus(201)
        ->assertJsonPath('data.transport_mode', 'SEA')
        ->assertJsonPath('data.container_number', 'MSKU1234565');

    // 2. AIR requires valid awb_number, airports, and AIR carrier
    $airPayload = [
        'transport_mode' => 'AIR',
        'carrier_id' => $airCarrier->id,
        'origin_location_id' => $airport1->id,
        'destination_location_id' => $airport2->id,
        'customer_id' => $customer->id,
        'cargo_type' => 'AIR_FREIGHT',
        'expected_departure_at' => now()->addDay()->toDateTimeString(),
        'expected_arrival_at' => now()->addDays(2)->toDateTimeString(),
    ];

    // Fails without awb_number
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $airPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['awb_number']);

    // Fails with invalid check-digit AWB (12612345672 instead of check 5)
    $airPayload['awb_number'] = '12612345672';
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $airPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['awb_number']);

    // Fails if seaport is used as origin for AIR
    $airPayload['awb_number'] = '12612345675';
    $airPayload['origin_location_id'] = $seaport1->id;
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $airPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['origin_location_id']);

    // Passes with valid AWB and airports
    $airPayload['origin_location_id'] = $airport1->id;
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $airPayload)
        ->assertStatus(201)
        ->assertJsonPath('data.transport_mode', 'AIR')
        ->assertJsonPath('data.awb_number', '12612345675');

    // 3. ROAD requires vehicle_plate, cmr_number, and ROAD carrier
    $roadPayload = [
        'transport_mode' => 'ROAD',
        'carrier_id' => $roadCarrier->id,
        'origin_location_id' => $depot->id,
        'destination_location_id' => $seaport1->id, // seaport allowed as handover point
        'customer_id' => $customer->id,
        'cargo_type' => 'ROAD',
        'expected_departure_at' => now()->addDay()->toDateTimeString(),
        'expected_arrival_at' => now()->addDays(3)->toDateTimeString(),
    ];

    // Fails without vehicle_plate and cmr_number
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $roadPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['vehicle_plate', 'cmr_number']);

    // Passes when vehicle_plate and cmr_number are provided
    $roadPayload['vehicle_plate'] = 'B 9876 XYZ';
    $roadPayload['cmr_number'] = 'CMR-2026-9999';
    $this->actingAs($operator, 'sanctum')->postJson('/api/shipments', $roadPayload)
        ->assertStatus(201)
        ->assertJsonPath('data.transport_mode', 'ROAD')
        ->assertJsonPath('data.vehicle_plate', 'B 9876 XYZ')
        ->assertJsonPath('data.cmr_number', 'CMR-2026-9999');
});

test('exception types are rejected if incompatible with shipment mode', function () {
    $operator = User::where('role', 'operator')->first();

    $seaShipment = Shipment::where('transport_mode', 'SEA')->first();
    $airShipment = Shipment::where('transport_mode', 'AIR')->first();
    $roadShipment = Shipment::where('transport_mode', 'ROAD')->first();

    // FLIGHT_OFFLOAD is rejected on SEA shipment
    $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$seaShipment->id}/exceptions", [
            'exception_code' => 'FLIGHT_OFFLOAD',
            'severity' => 'HIGH',
            'title' => 'Cargo Bumped',
            'description' => 'Container bumped from vessel.',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['exception_code']);

    // BORDER_QUEUE is rejected on AIR shipment
    $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$airShipment->id}/exceptions", [
            'exception_code' => 'BORDER_QUEUE',
            'severity' => 'MEDIUM',
            'title' => 'Customs Queue',
            'description' => 'Air cargo queue.',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['exception_code']);

    // FLIGHT_OFFLOAD is accepted on AIR shipment
    $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$airShipment->id}/exceptions", [
            'exception_code' => 'FLIGHT_OFFLOAD',
            'severity' => 'HIGH',
            'title' => 'Cargo Bumped',
            'description' => 'Pallet bumped from flight GIA-880.',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.exception_code', 'FLIGHT_OFFLOAD');

    // BORDER_QUEUE and VEHICLE_BREAKDOWN are accepted on ROAD shipment
    $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$roadShipment->id}/exceptions", [
            'exception_code' => 'VEHICLE_BREAKDOWN',
            'severity' => 'MEDIUM',
            'title' => 'Flat tire on highway',
            'description' => 'Haulier delayed by 4 hours.',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.exception_code', 'VEHICLE_BREAKDOWN');

    // PORT_CONGESTION is universally accepted across all modes
    $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$seaShipment->id}/exceptions", [
            'exception_code' => 'PORT_CONGESTION',
            'severity' => 'LOW',
            'title' => 'Vessel berth delay',
            'description' => 'Berth queue at port.',
        ])
        ->assertStatus(201);

    $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$airShipment->id}/exceptions", [
            'exception_code' => 'PORT_CONGESTION',
            'severity' => 'LOW',
            'title' => 'Tarmac congestion',
            'description' => 'Ramp congestion at airport cargo terminal.',
        ])
        ->assertStatus(201);

    $this->actingAs($operator, 'sanctum')
        ->postJson("/api/shipments/{$roadShipment->id}/exceptions", [
            'exception_code' => 'PORT_CONGESTION',
            'severity' => 'LOW',
            'title' => 'Gate congestion',
            'description' => 'Truck queue at seaport entry gate.',
        ])
        ->assertStatus(201);
});

test('per-mode delay thresholds evaluate exact boundaries at, just under, and just over each limit', function () {
    $etaService = app(EtaService::class);
    $baseTime = Carbon::parse('2026-11-20 12:00:00');

    // 1. AIR Freight: default 3h low / 12h medium
    $airShipment = Shipment::where('transport_mode', 'AIR')->first();
    $airShipment->expected_arrival_at = $baseTime;

    // At low threshold (3.0h) => LOW
    $airShipment->estimated_arrival_at = $baseTime->copy()->addHours(3);
    $etaService->evaluateDelay($airShipment);
    expect($airShipment->delay_severity)->toBe('LOW');

    // Just under low threshold (2.99h) => LOW
    $airShipment->estimated_arrival_at = $baseTime->copy()->addMinutes(179);
    $etaService->evaluateDelay($airShipment);
    expect($airShipment->delay_severity)->toBe('LOW');

    // Just over low threshold (3.01h) => MEDIUM
    $airShipment->estimated_arrival_at = $baseTime->copy()->addHours(3)->addSeconds(36);
    $etaService->evaluateDelay($airShipment);
    expect($airShipment->delay_severity)->toBe('MEDIUM');

    // At medium threshold (12.0h) => MEDIUM
    $airShipment->estimated_arrival_at = $baseTime->copy()->addHours(12);
    $etaService->evaluateDelay($airShipment);
    expect($airShipment->delay_severity)->toBe('MEDIUM');

    // Just over medium threshold (12.01h) => HIGH
    $airShipment->estimated_arrival_at = $baseTime->copy()->addHours(12)->addSeconds(36);
    $etaService->evaluateDelay($airShipment);
    expect($airShipment->delay_severity)->toBe('HIGH');

    // 2. ROAD Freight: default 6h low / 24h medium
    $roadShipment = Shipment::where('transport_mode', 'ROAD')->first();
    $roadShipment->expected_arrival_at = $baseTime;

    // At low threshold (6.0h) => LOW
    $roadShipment->estimated_arrival_at = $baseTime->copy()->addHours(6);
    $etaService->evaluateDelay($roadShipment);
    expect($roadShipment->delay_severity)->toBe('LOW');

    // Just over low threshold (6.01h) => MEDIUM
    $roadShipment->estimated_arrival_at = $baseTime->copy()->addHours(6)->addSeconds(36);
    $etaService->evaluateDelay($roadShipment);
    expect($roadShipment->delay_severity)->toBe('MEDIUM');

    // At medium threshold (24.0h) => MEDIUM
    $roadShipment->estimated_arrival_at = $baseTime->copy()->addHours(24);
    $etaService->evaluateDelay($roadShipment);
    expect($roadShipment->delay_severity)->toBe('MEDIUM');

    // Just over medium threshold (24.01h) => HIGH
    $roadShipment->estimated_arrival_at = $baseTime->copy()->addHours(24)->addSeconds(36);
    $etaService->evaluateDelay($roadShipment);
    expect($roadShipment->delay_severity)->toBe('HIGH');

    // 3. SEA Freight: default 12h low / 48h medium
    $seaShipment = Shipment::where('transport_mode', 'SEA')->first();
    $seaShipment->expected_arrival_at = $baseTime;

    // At low threshold (12.0h) => LOW
    $seaShipment->estimated_arrival_at = $baseTime->copy()->addHours(12);
    $etaService->evaluateDelay($seaShipment);
    expect($seaShipment->delay_severity)->toBe('LOW');

    // Just over low threshold (12.01h) => MEDIUM
    $seaShipment->estimated_arrival_at = $baseTime->copy()->addHours(12)->addSeconds(36);
    $etaService->evaluateDelay($seaShipment);
    expect($seaShipment->delay_severity)->toBe('MEDIUM');

    // At medium threshold (48.0h) => MEDIUM
    $seaShipment->estimated_arrival_at = $baseTime->copy()->addHours(48);
    $etaService->evaluateDelay($seaShipment);
    expect($seaShipment->delay_severity)->toBe('MEDIUM');

    // Just over medium threshold (48.01h) => HIGH
    $seaShipment->estimated_arrival_at = $baseTime->copy()->addHours(48)->addSeconds(36);
    $etaService->evaluateDelay($seaShipment);
    expect($seaShipment->delay_severity)->toBe('HIGH');
});

test('Shipment status display label helper returns mode-specific titles', function () {
    expect(Shipment::statusDisplayLabel('PICKED_UP', 'AIR'))->toBe('Received at Origin Airport')
        ->and(Shipment::statusDisplayLabel('PICKED_UP', 'SEA'))->toBe('Gated in at Origin Terminal')
        ->and(Shipment::statusDisplayLabel('PICKED_UP', 'ROAD'))->toBe('Loaded at Depot / Warehouse')
        ->and(Shipment::statusDisplayLabel('IN_TRANSIT', 'AIR'))->toBe('In Flight / In Transit')
        ->and(Shipment::statusDisplayLabel('IN_TRANSIT', 'SEA'))->toBe('At Sea / In Transit')
        ->and(Shipment::statusDisplayLabel('IN_TRANSIT', 'ROAD'))->toBe('On Road / In Transit');
});

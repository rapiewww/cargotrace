<?php

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\User;
use App\Policies\ShipmentPolicy;
use App\Services\AuditService;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\ExternalIntegrationService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('AuditService creates audit log entry with user and IP', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $this->actingAs($admin);

    $service = app(AuditService::class);
    $carrier = Carrier::first();

    $log = $service->log('CARRIER_UPDATED', $carrier, ['is_active' => true], ['is_active' => false]);

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe('CARRIER_UPDATED')
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->auditable_type)->toBe($carrier->getMorphClass())
        ->and($log->auditable_id)->toBe($carrier->id)
        ->and($log->old_values)->toBe(['is_active' => true])
        ->and($log->new_values)->toBe(['is_active' => false]);
});

test('EtaService evaluates delay thresholds accurately and notifies customer', function () {
    $etaService = app(EtaService::class);
    $shipment = Shipment::where('status', '!=', 'DELIVERED')->first();
    $shipment->transport_mode = 'SEA';
    $customer = $shipment->customer;

    // 1. On time (ETA <= Expected)
    $shipment->expected_arrival_at = Carbon::parse('2026-11-20 12:00:00');
    $shipment->estimated_arrival_at = Carbon::parse('2026-11-20 10:00:00');
    $etaService->evaluateDelay($shipment);

    expect($shipment->is_delayed)->toBeFalse()
        ->and((float) $shipment->delay_hours)->toBe(0.0)
        ->and($shipment->delay_severity)->toBe('NONE');

    // 2. LOW Delay (under or at SEA low_hours of 12.0)
    $shipment->estimated_arrival_at = Carbon::parse('2026-11-20 15:00:00');
    $etaService->evaluateDelay($shipment);

    expect($shipment->is_delayed)->toBeTrue()
        ->and((float) $shipment->delay_hours)->toBe(3.0)
        ->and($shipment->delay_severity)->toBe('LOW');

    $notification = Notification::where('user_id', $customer->id)
        ->where('type', 'SHIPMENT_DELAY_ALERT')
        ->latest()
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toContain('LOW');

    // 3. MEDIUM Delay under SEA mode (12/48 hours: 20 hours is > 12h and <= 48h -> MEDIUM)
    $shipment->estimated_arrival_at = Carbon::parse('2026-11-21 08:00:00');
    $etaService->evaluateDelay($shipment);

    expect($shipment->is_delayed)->toBeTrue()
        ->and((float) $shipment->delay_hours)->toBe(20.0)
        ->and($shipment->delay_severity)->toBe('MEDIUM');

    // Assert that the exact same delay hours (20.0h) produce different severities under AIR and ROAD
    // AIR thresholds (3/12): 20h > 12h -> HIGH
    $airShipment = clone $shipment;
    $airShipment->transport_mode = 'AIR';
    $etaService->evaluateDelay($airShipment);
    expect($airShipment->delay_severity)->toBe('HIGH');

    // ROAD thresholds (6/24): 20h is > 6h and <= 24h -> MEDIUM; with 28.0h delay, ROAD is HIGH while SEA is still MEDIUM
    $roadShipment = clone $shipment;
    $roadShipment->transport_mode = 'ROAD';
    $roadShipment->estimated_arrival_at = Carbon::parse('2026-11-21 16:00:00'); // 28 hours delay
    $etaService->evaluateDelay($roadShipment);
    expect((float) $roadShipment->delay_hours)->toBe(28.0)
        ->and($roadShipment->delay_severity)->toBe('HIGH');

    // 4. HIGH Delay for SEA (> 48 hours)
    $shipment->estimated_arrival_at = Carbon::parse('2026-11-22 18:00:00');
    $etaService->evaluateDelay($shipment);

    expect($shipment->is_delayed)->toBeTrue()
        ->and((float) $shipment->delay_hours)->toBe(54.0)
        ->and($shipment->delay_severity)->toBe('HIGH');
});

test('ExternalIntegrationService handles weather, currency, news, and geocoding safely', function () {
    $service = app(ExternalIntegrationService::class);

    // Weather
    $weather = $service->getWeatherByCoordinates(-6.1038190, 106.8824240, 'Port of Tanjung Priok');
    expect($weather)->toBeArray()
        ->and($weather)->toHaveKeys(['temperature_c', 'condition', 'humidity', 'wind_kph', 'source']);

    // Currency conversion (IDR to USD)
    $conversion = $service->convertCurrency(15873000, 'IDR', 'USD');
    expect($conversion)->toBeArray()
        ->and($conversion['from_currency'])->toBe('IDR')
        ->and($conversion['to_currency'])->toBe('USD')
        ->and($conversion['converted_amount'])->toBeGreaterThan(0)
        ->and($conversion['exchange_rate'])->toBeGreaterThan(0);

    // Same currency 1:1
    $same = $service->convertCurrency(500, 'USD', 'USD');
    expect($same['converted_amount'])->toBe(500.0)
        ->and($same['exchange_rate'])->toBe(1.0);

    // News
    $news = $service->getLogisticsNews();
    expect($news)->toBeArray()
        ->and(count($news))->toBeGreaterThan(0)
        ->and($news[0])->toHaveKeys(['headline', 'summary', 'source']);

    // Geocoding
    $geo = $service->geocodeLocation('Port of Singapore');
    expect($geo)->not->toBeNull()
        ->and($geo)->toHaveKeys(['latitude', 'longitude', 'display_name']);
});

test('ShipmentService enforces strict status transitions and handles currency conversion', function () {
    $shipmentService = app(ShipmentService::class);
    $customer = User::where('email', 'customer@cargotrace.test')->first();
    $carrier = Carrier::first();
    $origin = Location::where('unlocode', 'IDJKT')->first();
    $destination = Location::where('unlocode', 'NLRTM')->first();

    // Create Shipment
    $shipment = $shipmentService->createShipment([
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destination->id,
        'cargo_type' => 'FCL',
        'currency_code' => 'IDR',
        'declared_value' => 15873000, // 15.873 Million IDR
        'shipping_cost' => 1500000,
        'expected_departure_at' => Carbon::now(),
        'expected_arrival_at' => Carbon::now()->addDays(20),
    ], [
        [
            'sku' => 'TEST-SKU-01',
            'description' => 'Test Goods',
            'quantity' => 10,
            'unit_value' => 1587300,
            'weight_kg' => 500,
            'volume_cbm' => 2.5,
            'is_hazardous' => false,
        ],
    ]);

    expect($shipment)->not->toBeNull()
        ->and($shipment->status)->toBe('CREATED')
        ->and($shipment->converted_value_usd)->toBeGreaterThan(0)
        ->and($shipment->items)->toHaveCount(1)
        ->and($shipment->trackingEvents)->toHaveCount(1);

    // Status transition: CREATED -> BOOKED
    $shipmentService->updateStatus($shipment, 'BOOKED');
    expect($shipment->status)->toBe('BOOKED');

    // Status transition: BOOKED -> PICKED_UP
    $shipmentService->updateStatus($shipment, 'PICKED_UP');
    expect($shipment->status)->toBe('PICKED_UP');

    // Status transition: PICKED_UP -> IN_TRANSIT
    $shipmentService->updateStatus($shipment, 'IN_TRANSIT');
    expect($shipment->status)->toBe('IN_TRANSIT');

    // Status transition: IN_TRANSIT -> DELIVERED
    $shipmentService->updateStatus($shipment, 'DELIVERED');
    expect($shipment->status)->toBe('DELIVERED')
        ->and($shipment->actual_arrival_at)->not->toBeNull();

    // DELIVERED is terminal: cannot transition back to IN_TRANSIT or any other status
    expect(fn () => $shipmentService->updateStatus($shipment, 'IN_TRANSIT'))
        ->toThrow(ValidationException::class);

    // Illegal skip transition test (new shipment CREATED -> DELIVERED)
    $newShipment = $shipmentService->createShipment([
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destination->id,
        'cargo_type' => 'AIR_FREIGHT',
        'currency_code' => 'USD',
        'declared_value' => 5000,
        'expected_departure_at' => Carbon::now(),
        'expected_arrival_at' => Carbon::now()->addDays(2),
    ]);

    expect(fn () => $shipmentService->updateStatus($newShipment, 'DELIVERED'))
        ->toThrow(ValidationException::class);
});

test('TrackingService validates chronological timestamps and attaches live weather', function () {
    $trackingService = app(TrackingService::class);
    $shipment = Shipment::where('status', 'CREATED')->first();
    $location = Location::where('unlocode', 'DXB')->first();

    // 1. Valid event
    $event1 = $trackingService->addTrackingEvent($shipment, [
        'location_id' => $location->id,
        'status' => 'BOOKED',
        'title' => 'Cargo Loaded at Terminal',
        'description' => 'Container inspected and staged.',
        'occurred_at' => Carbon::now()->addHours(1)->toIso8601String(),
    ]);

    expect($event1)->not->toBeNull()
        ->and($event1->weather_snapshot)->toBeArray()
        ->and($shipment->fresh()->status)->toBe('BOOKED');

    // 2. Chronological violation (older timestamp than latest event)
    expect(fn () => $trackingService->addTrackingEvent($shipment, [
        'location_id' => $location->id,
        'status' => 'PICKED_UP',
        'title' => 'Time traveler event',
        'occurred_at' => Carbon::now()->subDays(2)->toIso8601String(),
    ]))->toThrow(ValidationException::class);
});

test('ExceptionService reports, records tracking event, and handles resolution', function () {
    $exceptionService = app(ExceptionService::class);
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $this->actingAs($operator);

    $shipment = Shipment::first();

    // Report exception
    $exception = $exceptionService->reportException($shipment, [
        'exception_code' => 'CUSTOMS_HOLD',
        'severity' => 'HIGH',
        'title' => 'Missing Phytosanitary Certificate',
        'description' => 'Customs held shipment for quarantine inspection.',
        'detected_at' => now(),
    ]);

    expect($exception)->not->toBeNull()
        ->and($exception->status)->toBe('OPEN')
        ->and($exception->exception_code)->toBe('CUSTOMS_HOLD')
        ->and($exception->severity)->toBe('HIGH');

    // Verified tracking event created
    $trackingEvent = $shipment->trackingEvents()->where('event_type', 'EXCEPTION')->latest()->first();
    expect($trackingEvent)->not->toBeNull()
        ->and($trackingEvent->title)->toContain('Missing Phytosanitary Certificate');

    // Update status to INVESTIGATING
    $exceptionService->updateExceptionStatus($exception, 'INVESTIGATING', 'Contacted shipper for revised doc.');
    expect($exception->fresh()->status)->toBe('INVESTIGATING');

    // Resolve exception
    $exceptionService->updateExceptionStatus($exception, 'RESOLVED', 'Certificate received and stamped.');
    expect($exception->fresh()->status)->toBe('RESOLVED')
        ->and($exception->fresh()->resolved_at)->not->toBeNull()
        ->and($exception->fresh()->resolved_by)->toBe($operator->id);
});

test('EnsureUserHasRole middleware protects routes by role', function () {
    Route::get('/test-admin-only', fn () => response()->json(['message' => 'admin-ok']))
        ->middleware(['role:admin']);

    Route::get('/test-operator-only', fn () => response()->json(['message' => 'operator-ok']))
        ->middleware(['role:operator']);

    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $customer = User::where('email', 'customer@cargotrace.test')->first();

    // Unauthenticated
    $this->getJson('/test-admin-only')->assertStatus(401);

    // Customer accessing operator route -> 403 Forbidden
    $this->actingAs($customer)->getJson('/test-operator-only')->assertStatus(403);

    // Operator accessing operator route -> 200 OK
    $this->actingAs($operator)->getJson('/test-operator-only')->assertOk();

    // Admin has universal bypass -> 200 OK for any route
    $this->actingAs($admin)->getJson('/test-operator-only')->assertOk();
    $this->actingAs($admin)->getJson('/test-admin-only')->assertOk();
});

test('ShipmentPolicy isolates customer shipments and empowers operators and admins', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $customer1 = User::where('email', 'customer@cargotrace.test')->first();
    $customer2 = User::where('email', 'customer2@cargotrace.test')->first();

    $policy = new ShipmentPolicy;

    $shipmentOfCustomer1 = Shipment::where('customer_id', $customer1->id)->first();
    $shipmentOfCustomer2 = Shipment::where('customer_id', $customer2->id)->first();

    // Customer 1 viewing own shipment -> true
    expect($policy->view($customer1, $shipmentOfCustomer1))->toBeTrue();

    // Customer 1 viewing Customer 2 shipment -> false
    expect($policy->view($customer1, $shipmentOfCustomer2))->toBeFalse();

    // Customer attempting to update or create -> false
    expect($policy->create($customer1))->toBeFalse();
    expect($policy->update($customer1, $shipmentOfCustomer1))->toBeFalse();
    expect($policy->addTrackingEvent($customer1, $shipmentOfCustomer1))->toBeFalse();
    expect($policy->reportException($customer1, $shipmentOfCustomer1))->toBeFalse();

    // Operator viewing and managing -> true
    expect($policy->view($operator, $shipmentOfCustomer1))->toBeTrue();
    expect($policy->create($operator))->toBeTrue();
    expect($policy->update($operator, $shipmentOfCustomer1))->toBeTrue();
    expect($policy->addTrackingEvent($operator, $shipmentOfCustomer1))->toBeTrue();
    expect($policy->reportException($operator, $shipmentOfCustomer1))->toBeTrue();

    // Admin before hook allows everything
    expect($policy->before($admin, 'delete'))->toBeTrue();
    expect($policy->before($admin, 'update'))->toBeTrue();
});

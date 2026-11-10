<?php

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('demo users are seeded with correct roles and credentials', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe('admin')
        ->and($admin->preferred_currency)->toBe('USD')
        ->and($admin->is_active)->toBeTrue();

    $operator = User::where('email', 'operator@cargotrace.test')->first();
    expect($operator)->not->toBeNull()
        ->and($operator->role)->toBe('operator')
        ->and($operator->preferred_currency)->toBe('SGD');

    $customer1 = User::where('email', 'customer@cargotrace.test')->first();
    expect($customer1)->not->toBeNull()
        ->and($customer1->role)->toBe('customer')
        ->and($customer1->preferred_currency)->toBe('IDR');

    $customer2 = User::where('email', 'customer2@cargotrace.test')->first();
    expect($customer2)->not->toBeNull()
        ->and($customer2->role)->toBe('customer')
        ->and($customer2->preferred_currency)->toBe('EUR');
});

test('six global carriers are seeded properly', function () {
    expect(Carrier::count())->toBeGreaterThanOrEqual(6);

    $maersk = Carrier::where('code', 'MAEU')->first();
    expect($maersk)->not->toBeNull()
        ->and($maersk->mode)->toBe('SEA')
        ->and($maersk->is_active)->toBeTrue();

    $cmaCgm = Carrier::where('code', 'CMDU')->first();
    expect($cmaCgm)->not->toBeNull()
        ->and($cmaCgm->mode)->toBe('SEA');
});

test('eight global locations are seeded with coordinates and external integration snapshots', function () {
    expect(Location::count())->toBeGreaterThanOrEqual(8);

    $jkt = Location::where('unlocode', 'IDJKT')->first();
    expect($jkt)->not->toBeNull()
        ->and($jkt->currency_code)->toBe('IDR')
        ->and($jkt->type)->toBe('PORT')
        ->and((float) $jkt->latitude)->toEqualWithDelta(-6.1038190, 0.0001)
        ->and((float) $jkt->longitude)->toEqualWithDelta(106.8824240, 0.0001)
        ->and($jkt->weather_snapshot)->toBeArray()
        ->and($jkt->weather_snapshot)->toHaveKey('temperature_c')
        ->and($jkt->news_snapshot)->toBeArray()
        ->and($jkt->news_snapshot)->toHaveKey('headline');
});

test('six shipments exist with currency conversion and json snapshots', function () {
    expect(Shipment::count())->toBeGreaterThanOrEqual(6);

    $shipment1 = Shipment::where('tracking_number', 'CTG-SEA-2026-00189')->first();
    expect($shipment1)->not->toBeNull()
        ->and($shipment1->currency_code)->toBe('IDR')
        ->and((float) $shipment1->declared_value)->toBe(1450000000.0)
        ->and((float) $shipment1->converted_value_usd)->toBe(91350.0)
        ->and($shipment1->currency_snapshot)->toBeArray()
        ->and($shipment1->route_news_snapshot)->toBeArray()
        ->and($shipment1->expected_departure_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($shipment1->is_delayed)->toBeTrue()
        ->and($shipment1->customer)->toBeInstanceOf(User::class)
        ->and($shipment1->carrier)->toBeInstanceOf(Carrier::class)
        ->and($shipment1->originLocation)->toBeInstanceOf(Location::class)
        ->and($shipment1->destinationLocation)->toBeInstanceOf(Location::class)
        ->and($shipment1->items)->toHaveCount(2)
        ->and($shipment1->trackingEvents->count())->toBeGreaterThanOrEqual(4);
});

test('exceptions and tracking events relationships work properly', function () {
    $exception = ShipmentException::first();
    expect($exception)->not->toBeNull()
        ->and($exception->shipment)->toBeInstanceOf(Shipment::class)
        ->and($exception->detected_at)->toBeInstanceOf(CarbonInterface::class);

    $event = TrackingEvent::first();
    expect($event)->not->toBeNull()
        ->and($event->shipment)->toBeInstanceOf(Shipment::class)
        ->and($event->occurred_at)->toBeInstanceOf(CarbonInterface::class);
});

test('notifications and audit logs models and casts function as expected', function () {
    $notification = Notification::first();
    expect($notification)->not->toBeNull()
        ->and($notification->user)->toBeInstanceOf(User::class);

    $auditLog = AuditLog::first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->created_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($auditLog->auditable)->not->toBeNull();
});

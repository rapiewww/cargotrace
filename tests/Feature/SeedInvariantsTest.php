<?php

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Support\Iso6346;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('seed dataset contains exactly 45 shipments across realistic status distribution', function () {
    expect(Shipment::count())->toBe(45);

    $createdCount = Shipment::whereIn('status', ['CREATED', 'BOOKED'])->count();
    $inTransitCount = Shipment::where('status', 'IN_TRANSIT')->count();
    $deliveredCount = Shipment::where('status', 'DELIVERED')->count();

    expect($createdCount)->toBe(6)
        ->and($inTransitCount)->toBe(20)
        ->and($deliveredCount)->toBe(19);
});

test('seed dataset contains 20+ global container ports and 8-10 carriers', function () {
    expect(Location::count())->toBeGreaterThanOrEqual(20)
        ->and(Carrier::count())->toBeGreaterThanOrEqual(8)
        ->and(Carrier::count())->toBeLessThanOrEqual(10);

    // Verify all carriers operate in SEA mode
    $nonSeaCarriers = Carrier::where('mode', '!=', 'SEA')->count();
    expect($nonSeaCarriers)->toBe(0);

    // Verify locations have valid UN/LOCODE, coordinates and timezone
    foreach (Location::all() as $loc) {
        expect($loc->unlocode)->toMatch('/^[A-Z]{2}[A-Z0-9]{3}$/')
            ->and($loc->latitude)->not->toBeNull()
            ->and($loc->longitude)->not->toBeNull()
            ->and($loc->timezone)->not->toBeNull();
    }
});

test('terminal state invariants hold across all shipments', function () {
    $deliveredShipments = Shipment::where('status', 'DELIVERED')->get();
    expect($deliveredShipments)->toHaveCount(19);

    foreach ($deliveredShipments as $shipment) {
        // DELIVERED shipments must have a non-null actual_arrival_at
        expect($shipment->actual_arrival_at)->not->toBeNull();

        // Must have a terminal tracking event where occurred_at == actual_arrival_at
        $terminalEvent = $shipment->trackingEvents()
            ->where('status', 'DELIVERED')
            ->where('occurred_at', $shipment->actual_arrival_at)
            ->first();

        expect($terminalEvent)->not->toBeNull()
            ->and($terminalEvent->location_id)->toBe($shipment->destination_location_id);
    }

    // Non-delivered shipments must not have actual_arrival_at
    $nonDelivered = Shipment::where('status', '!=', 'DELIVERED')->get();
    foreach ($nonDelivered as $shipment) {
        expect($shipment->actual_arrival_at)->toBeNull();
    }
});

test('tracking events maintain chronological validity and no events precede departure', function () {
    $shipments = Shipment::with('trackingEvents')->get();

    foreach ($shipments as $shipment) {
        $events = $shipment->trackingEvents()->reorder('occurred_at', 'asc')->get();
        expect($events->count())->toBeGreaterThanOrEqual(1);

        // For in-transit or delivered shipments, no event before expected_departure_at
        if (in_array($shipment->status, ['IN_TRANSIT', 'DELIVERED'])) {
            $firstEvent = $events->first();
            expect($firstEvent->occurred_at->timestamp)
                ->toBeGreaterThanOrEqual($shipment->expected_departure_at->timestamp);
        }

        // Chronologically strictly non-decreasing
        $prevTime = null;
        foreach ($events as $event) {
            if ($prevTime !== null) {
                expect($event->occurred_at->timestamp)->toBeGreaterThanOrEqual($prevTime->timestamp);
            }
            $prevTime = $event->occurred_at;
        }
    }
});

test('exceptions adhere strictly to taxonomy, count and status timestamps', function () {
    expect(ShipmentException::count())->toBe(12);

    $codes = ['CUSTOMS_HOLD', 'WEATHER_DELAY', 'PORT_CONGESTION', 'DAMAGED', 'MISROUTED', 'DOCUMENTATION_ISSUE'];
    foreach ($codes as $code) {
        expect(ShipmentException::where('exception_code', $code)->count())->toBe(2);
    }

    expect(ShipmentException::where('severity', 'LOW')->count())->toBe(4)
        ->and(ShipmentException::where('severity', 'MEDIUM')->count())->toBe(4)
        ->and(ShipmentException::where('severity', 'HIGH')->count())->toBe(4);

    expect(ShipmentException::where('status', 'OPEN')->count())->toBe(4)
        ->and(ShipmentException::where('status', 'INVESTIGATING')->count())->toBe(4)
        ->and(ShipmentException::where('status', 'RESOLVED')->count())->toBe(4);

    // Resolved exceptions must have resolved_at > detected_at, resolution notes and resolver
    $resolvedExceptions = ShipmentException::where('status', 'RESOLVED')->get();
    foreach ($resolvedExceptions as $ex) {
        expect($ex->resolved_at)->not->toBeNull()
            ->and($ex->resolved_at->timestamp)->toBeGreaterThan($ex->detected_at->timestamp)
            ->and($ex->resolved_by)->not->toBeNull()
            ->and(strlen($ex->resolution_notes ?? ''))->toBeGreaterThan(10);
    }

    // Non-resolved exceptions must not have resolved_at, resolved_by, or resolution_notes
    $openExceptions = ShipmentException::where('status', '!=', 'RESOLVED')->get();
    foreach ($openExceptions as $ex) {
        expect($ex->resolved_at)->toBeNull()
            ->and($ex->resolved_by)->toBeNull()
            ->and($ex->resolution_notes)->toBeNull();
    }
});

test('canonical sample shipments retain expected exception and delay profiles', function () {
    $s189 = Shipment::where('tracking_number', 'CTG-SEA-2026-00189')->first();
    expect($s189)->not->toBeNull()
        ->and($s189->status)->toBe('DELIVERED')
        ->and($s189->is_delayed)->toBeTrue()
        ->and($s189->exceptions()->where('exception_code', 'WEATHER_DELAY')->where('status', 'RESOLVED')->count())->toBe(1);

    $s244 = Shipment::where('tracking_number', 'CTG-SEA-2026-00244')->first();
    expect($s244)->not->toBeNull()
        ->and($s244->status)->toBe('IN_TRANSIT')
        ->and($s244->is_delayed)->toBeTrue()
        ->and($s244->exceptions()->where('exception_code', 'CUSTOMS_HOLD')->where('severity', 'HIGH')->where('status', 'INVESTIGATING')->count())->toBe(1);

    $s678 = Shipment::where('tracking_number', 'CTG-SEA-2026-00678')->first();
    expect($s678)->not->toBeNull()
        ->and($s678->status)->toBe('IN_TRANSIT')
        ->and($s678->is_delayed)->toBeTrue()
        ->and($s678->exceptions()->where('exception_code', 'WEATHER_DELAY')->where('severity', 'LOW')->where('status', 'OPEN')->count())->toBe(1);
});

test('all container numbers comply with ISO 6346 standard and check digits', function () {
    $items = ShipmentItem::all();
    expect($items->count())->toBeGreaterThanOrEqual(45);

    foreach ($items as $item) {
        expect(Iso6346::isValid($item->sku))->toBeTrue(
            "Container number {$item->sku} failed ISO 6346 validation"
        );
        expect(strlen($item->sku))->toBe(11)
            ->and($item->sku[3])->toBe('U');
    }
});

test('role scoping works accurately with 45-shipment volume', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $customer1 = User::where('email', 'customer@cargotrace.test')->first();
    $customer2 = User::where('email', 'customer2@cargotrace.test')->first();

    // In ShipmentPolicy, admin & operator can view all shipments
    $sampleShipments = Shipment::all();
    expect($sampleShipments)->toHaveCount(45);

    foreach ($sampleShipments->take(10) as $s) {
        expect($admin->can('view', $s))->toBeTrue()
            ->and($operator->can('view', $s))->toBeTrue();
    }

    // Customers only see their own assigned shipments
    $c1Shipments = Shipment::where('customer_id', $customer1->id)->get();
    $c2Shipments = Shipment::where('customer_id', $customer2->id)->get();

    expect($c1Shipments)->toHaveCount(15)
        ->and($c2Shipments)->toHaveCount(14);

    // Customer 1 can view their shipments, but Customer 2 cannot
    foreach ($c1Shipments as $s) {
        expect($customer1->can('view', $s))->toBeTrue()
            ->and($customer2->can('view', $s))->toBeFalse();
    }

    // Customer 2 can view their shipments, but Customer 1 cannot
    foreach ($c2Shipments as $s) {
        expect($customer2->can('view', $s))->toBeTrue()
            ->and($customer1->can('view', $s))->toBeFalse();
    }

    // Verify customers do not overlap
    $c1Ids = $c1Shipments->pluck('id');
    $c2Ids = $c2Shipments->pluck('id');
    expect($c1Ids->intersect($c2Ids))->toBeEmpty();
});

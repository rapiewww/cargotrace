<?php

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\FreeTimeService;
use App\Support\Awb;
use App\Support\Iso6346;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('seed dataset contains 48 spice shipments with exact mode and status distribution', function () {
    expect(Shipment::count())->toBe(48);

    expect(Shipment::where('transport_mode', 'SEA')->count())->toBe(22)
        ->and(Shipment::where('transport_mode', 'AIR')->count())->toBe(14)
        ->and(Shipment::where('transport_mode', 'ROAD')->count())->toBe(12);

    $createdOrBooked = Shipment::whereIn('status', ['CREATED', 'BOOKED'])->count();
    $inTransit = Shipment::where('status', 'IN_TRANSIT')->count();
    $delivered = Shipment::where('status', 'DELIVERED')->count();

    expect($createdOrBooked)->toBeGreaterThanOrEqual(5)->toBeLessThanOrEqual(7)
        ->and($inTransit)->toBeGreaterThanOrEqual(20)->toBeLessThanOrEqual(24)
        ->and($delivered)->toBeGreaterThanOrEqual(18)->toBeLessThanOrEqual(22)
        ->and($createdOrBooked + $inTransit + $delivered)->toBe(48);

    // Every shipment created within the last 10 weeks
    $tenWeeksAgo = Carbon::now()->subWeeks(10)->startOfDay();
    foreach (Shipment::all() as $shipment) {
        expect($shipment->created_at->timestamp)->toBeGreaterThanOrEqual($tenWeeksAgo->timestamp);
    }
});

test('seed dataset contains 8 users, 28 locations, and 15 carriers across SEA, AIR, and ROAD', function () {
    expect(User::count())->toBe(8)
        ->and(User::where('role', 'customer')->count())->toBe(6)
        ->and(Location::count())->toBe(28)
        ->and(Carrier::count())->toBe(15);

    expect(Carrier::where('mode', 'SEA')->count())->toBe(5)
        ->and(Carrier::where('mode', 'AIR')->count())->toBe(5)
        ->and(Carrier::where('mode', 'ROAD')->count())->toBe(5);

    $forbiddenPseudoCodes = ['SGCHG', 'IDCGK', 'IDKNO', 'IDBTJ', 'AEDXB', 'NLAMS', 'FRCDG', 'DEFRA', 'INBOM', 'USJFK', 'IDLSW', 'IDMES', 'IDACE', 'INNSC', 'FRGRS', 'NLRDD', 'NLVEN'];
    foreach ($forbiddenPseudoCodes as $badCode) {
        expect(Location::where('unlocode', $badCode)->exists())->toBeFalse("Pseudo code {$badCode} should not exist");
    }

    $expectedIata = ['CGK', 'DXB', 'JFK', 'SIN', 'KNO', 'BTJ', 'AMS', 'CDG', 'FRA', 'BOM'];
    $airports = Location::where('type', 'AIRPORT')->pluck('unlocode')->all();
    foreach ($expectedIata as $iata) {
        expect($airports)->toContain($iata);
    }

    foreach (Location::all() as $loc) {
        $type = strtoupper($loc->type);
        if (in_array($type, ['PORT', 'SEAPORT'], true)) {
            expect($loc->unlocode)->toMatch('/^[A-Z]{2}[A-Z0-9]{3}$/')
                ->and($loc->iata_code)->toBeNull()
                ->and($loc->display_code)->toBe($loc->unlocode);
        } elseif ($type === 'AIRPORT') {
            expect($loc->unlocode)->toMatch('/^[A-Z]{3}$/')
                ->and($loc->iata_code)->toMatch('/^[A-Z]{3}$/')
                ->and($loc->display_code)->toBe($loc->iata_code);
        } else {
            expect($loc->unlocode)->toMatch('/^(DEP|BRD)-[A-Z0-9]{3}$/')
                ->and($loc->iata_code)->toBeNull()
                ->and($loc->display_code)->toBe($loc->unlocode);
        }

        expect($loc->latitude)->not->toBeNull()
            ->and($loc->longitude)->not->toBeNull()
            ->and($loc->timezone)->not->toBeNull()
            ->and($loc->country)->not->toBeEmpty();
    }
});

test('carrier and location types strictly match each shipment transport mode', function () {
    $shipments = Shipment::with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation'])->get();

    foreach ($shipments as $shipment) {
        $mode = strtoupper($shipment->transport_mode);
        expect(strtoupper($shipment->carrier->mode))->toBe($mode);

        $locs = array_filter([
            $shipment->originLocation,
            $shipment->destinationLocation,
            $shipment->currentLocation,
        ]);

        foreach ($locs as $loc) {
            $type = strtoupper($loc->type);
            if ($mode === 'SEA') {
                expect($type)->toBeIn(['PORT', 'SEAPORT']);
            } elseif ($mode === 'AIR') {
                expect($type)->toBe('AIRPORT');
            } else {
                expect($type)->toBeIn(['DEPOT', 'WAREHOUSE', 'BORDER', 'HUB', 'CUSTOMS', 'PORT', 'SEAPORT', 'AIRPORT']);
            }
        }
    }
});

test('terminal state and free-time invariants hold across all 48 shipments', function () {
    $freeTimeService = app(FreeTimeService::class);
    $freeDaysAllowed = (int) (Setting::get('free_days_after_arrival') ?? config('cargotrace.free_time.days', 5));

    $deliveredShipments = Shipment::where('status', 'DELIVERED')->get();
    expect($deliveredShipments)->toHaveCount(20);

    foreach ($deliveredShipments as $shipment) {
        expect($shipment->actual_arrival_at)->not->toBeNull();

        $terminalEvent = $shipment->trackingEvents()
            ->where('status', 'DELIVERED')
            ->where('occurred_at', $shipment->actual_arrival_at)
            ->first();

        expect($terminalEvent)->not->toBeNull()
            ->and($terminalEvent->location_id)->toBe($shipment->destination_location_id);
    }

    $pendingShipments = Shipment::whereIn('status', ['CREATED', 'BOOKED'])->get();
    expect($pendingShipments)->toHaveCount(6);
    foreach ($pendingShipments as $shipment) {
        expect($shipment->actual_arrival_at)->toBeNull();
    }

    // Active (non-DELIVERED) free-time rules:
    // - Shipments whose arrival/discharge event is older than freeDays + 6 days must be DELIVERED
    // - Only 2 to 4 active shipments may be over the free-time limit, each by 1 to 6 days
    // - Shipments arrived within the last 5 days stay active and under the limit
    $activeArrived = Shipment::where('status', '!=', 'DELIVERED')
        ->whereNotNull('actual_arrival_at')
        ->get();

    $overLimit = [];
    $withinLimit = [];
    foreach ($activeArrived as $shipment) {
        $eval = $freeTimeService->calculate($shipment, null, $freeDaysAllowed);
        if ($eval['is_over_limit']) {
            $overLimit[] = $eval;
            expect($eval['days_over'])->toBeGreaterThanOrEqual(1)
                ->and($eval['days_over'])->toBeLessThanOrEqual(6);
        } else {
            $withinLimit[] = $eval;
            expect($eval['days_elapsed'])->toBeLessThanOrEqual($freeDaysAllowed);
        }
    }

    expect(count($overLimit))->toBeGreaterThanOrEqual(2)
        ->and(count($overLimit))->toBeLessThanOrEqual(4)
        ->and(count($withinLimit))->toBeGreaterThanOrEqual(3);

    // Verify no non-DELIVERED shipment has an old arrival/discharge event beyond free-time + 6 days
    $cutoff = Carbon::now()->subDays($freeDaysAllowed + 6);
    $oldArrivalEventsOnActive = TrackingEvent::whereHas('shipment', fn ($q) => $q->where('status', '!=', 'DELIVERED'))
        ->where(function ($q) {
            $q->where('title', 'like', '%Arrived%')
                ->orWhere('title', 'like', '%Discharged%');
        })
        ->where('occurred_at', '<', $cutoff)
        ->count();

    expect($oldArrivalEventsOnActive)->toBe(0);
});

test('tracking events maintain chronological order, no events before departure, and mode-valid locations', function () {
    $shipments = Shipment::with(['trackingEvents.location'])->get();

    foreach ($shipments as $shipment) {
        $mode = strtoupper($shipment->transport_mode);
        $events = $shipment->trackingEvents()->reorder('occurred_at', 'asc')->get();
        expect($events->count())->toBeGreaterThanOrEqual(1);

        if (in_array($shipment->status, ['IN_TRANSIT', 'DELIVERED'], true)) {
            $firstEvent = $events->first();
            expect($firstEvent->occurred_at->timestamp)
                ->toBeGreaterThanOrEqual($shipment->expected_departure_at->timestamp);
        }

        $prevTime = null;
        foreach ($events as $event) {
            if ($prevTime !== null) {
                expect($event->occurred_at->timestamp)->toBeGreaterThanOrEqual($prevTime->timestamp);
            }
            $prevTime = $event->occurred_at;

            if ($event->location) {
                $locType = strtoupper($event->location->type);
                if ($mode === 'SEA') {
                    expect($locType)->toBeIn(['PORT', 'SEAPORT']);
                } elseif ($mode === 'AIR') {
                    expect($locType)->toBe('AIRPORT');
                } else {
                    expect($locType)->toBeIn(['DEPOT', 'WAREHOUSE', 'BORDER', 'HUB', 'CUSTOMS', 'PORT', 'SEAPORT', 'AIRPORT']);
                }
            }
        }
    }
});

test('all SEA container numbers comply with ISO 6346, all AIR AWBs pass mod-7, and all ROAD refs are populated', function () {
    $seaShipments = Shipment::where('transport_mode', 'SEA')->get();
    foreach ($seaShipments as $shipment) {
        expect(Iso6346::isValid((string) $shipment->container_number))->toBeTrue(
            "Shipment {$shipment->tracking_number} container {$shipment->container_number} failed ISO 6346 validation"
        );
    }

    $seaItems = ShipmentItem::whereHas('shipment', fn ($q) => $q->where('transport_mode', 'SEA'))->get();
    expect($seaItems->count())->toBeGreaterThanOrEqual(22);
    foreach ($seaItems as $item) {
        expect(Iso6346::isValid((string) $item->container_number))->toBeTrue(
            "Item container {$item->container_number} failed ISO 6346 validation"
        );
    }

    $airShipments = Shipment::where('transport_mode', 'AIR')->get();
    foreach ($airShipments as $shipment) {
        expect(Awb::isValid($shipment->awb_number))->toBeTrue(
            "Shipment {$shipment->tracking_number} AWB {$shipment->awb_number} failed mod-7 check"
        );
    }

    $roadShipments = Shipment::where('transport_mode', 'ROAD')->get();
    foreach ($roadShipments as $shipment) {
        expect($shipment->vehicle_plate)->not->toBeEmpty()
            ->and($shipment->cmr_number)->not->toBeEmpty();
    }
});

test('per-mode delay severities match thresholds and cover LOW, MEDIUM, and HIGH for every mode', function () {
    $etaService = app(EtaService::class);

    foreach (Shipment::all() as $shipment) {
        $thresholds = $etaService->getThresholdsForMode($shipment->transport_mode);
        $hours = (float) $shipment->delay_hours;

        if (! $shipment->is_delayed) {
            expect($hours)->toBe(0.0)
                ->and($shipment->delay_severity)->toBe('NONE');
        } else {
            $expectedSeverity = match (true) {
                $hours <= $thresholds['low'] => 'LOW',
                $hours <= $thresholds['medium'] => 'MEDIUM',
                default => 'HIGH',
            };
            expect($shipment->delay_severity)->toBe($expectedSeverity);
        }
    }

    foreach (['SEA', 'AIR', 'ROAD'] as $mode) {
        foreach (['LOW', 'MEDIUM', 'HIGH'] as $sev) {
            expect(Shipment::where('transport_mode', $mode)->where('delay_severity', $sev)->count())
                ->toBeGreaterThanOrEqual(1, "Missing {$sev} delay case for mode {$mode}");
        }
    }

    // Mix of delivered late and delivered on-time
    expect(Shipment::where('status', 'DELIVERED')->where('is_delayed', true)->count())->toBeGreaterThanOrEqual(4)
        ->and(Shipment::where('status', 'DELIVERED')->where('is_delayed', false)->count())->toBeGreaterThanOrEqual(8);
});

test('14 exceptions cover all nine codes, all severities and statuses, mode rules, and at most 6 open on in-transit', function () {
    expect(ShipmentException::count())->toBe(14);

    $openCount = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])->count();
    expect($openCount)->toBeLessThanOrEqual(6)
        ->and($openCount)->toBeGreaterThanOrEqual(1);

    $allNineCodes = [
        'CUSTOMS_HOLD',
        'WEATHER_DELAY',
        'PORT_CONGESTION',
        'DAMAGED',
        'MISROUTED',
        'DOCUMENTATION_ISSUE',
        'FLIGHT_OFFLOAD',
        'BORDER_QUEUE',
        'VEHICLE_BREAKDOWN',
    ];

    foreach ($allNineCodes as $code) {
        expect(ShipmentException::where('exception_code', $code)->count())->toBeGreaterThanOrEqual(1);
    }

    foreach (['LOW', 'MEDIUM', 'HIGH'] as $sev) {
        expect(ShipmentException::where('severity', $sev)->count())->toBeGreaterThanOrEqual(1);
    }

    foreach (['OPEN', 'INVESTIGATING', 'RESOLVED'] as $status) {
        expect(ShipmentException::where('status', $status)->count())->toBeGreaterThanOrEqual(1);
    }

    foreach (ShipmentException::with('shipment')->get() as $ex) {
        $mode = strtoupper($ex->shipment->transport_mode);
        $allowed = ExceptionService::MODE_ALLOWED_EXCEPTIONS[$mode] ?? [];
        expect($allowed)->toContain($ex->exception_code);

        if ($ex->status === 'RESOLVED') {
            expect($ex->resolved_at)->not->toBeNull()
                ->and($ex->resolved_at->timestamp)->toBeGreaterThan($ex->detected_at->timestamp)
                ->and($ex->resolved_by)->not->toBeNull()
                ->and(strlen($ex->resolution_notes ?? ''))->toBeGreaterThan(10);
        } else {
            expect($ex->shipment->status)->toBe('IN_TRANSIT')
                ->and($ex->resolved_at)->toBeNull()
                ->and($ex->resolved_by)->toBeNull()
                ->and($ex->resolution_notes)->toBeNull();
        }
    }
});

test('canonical sample shipments retain expected tracking numbers, modes, and states', function () {
    $s189 = Shipment::where('tracking_number', 'CTG-SEA-2026-00189')->first();
    expect($s189)->not->toBeNull()
        ->and($s189->transport_mode)->toBe('SEA')
        ->and($s189->status)->toBe('DELIVERED')
        ->and($s189->is_delayed)->toBeTrue()
        ->and($s189->exceptions()->where('exception_code', 'WEATHER_DELAY')->where('status', 'RESOLVED')->count())->toBe(1);

    $s244 = Shipment::where('tracking_number', 'CTG-SEA-2026-00244')->first();
    expect($s244)->not->toBeNull()
        ->and($s244->transport_mode)->toBe('SEA')
        ->and($s244->status)->toBe('IN_TRANSIT')
        ->and($s244->is_delayed)->toBeTrue()
        ->and($s244->exceptions()->where('exception_code', 'CUSTOMS_HOLD')->where('severity', 'HIGH')->where('status', 'INVESTIGATING')->count())->toBe(1);

    $s678 = Shipment::where('tracking_number', 'CTG-AIR-2026-00678')->first();
    expect($s678)->not->toBeNull()
        ->and($s678->transport_mode)->toBe('AIR')
        ->and($s678->status)->toBe('IN_TRANSIT')
        ->and($s678->is_delayed)->toBeTrue()
        ->and($s678->exceptions()->where('exception_code', 'WEATHER_DELAY')->where('severity', 'LOW')->where('status', 'OPEN')->count())->toBe(1);
});

test('customer scoping works accurately across all 6 buyers with 48-shipment volume', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();
    $customers = User::where('role', 'customer')->orderBy('email')->get();

    expect($customers)->toHaveCount(6)
        ->and(Shipment::count())->toBe(48);

    foreach (Shipment::take(10)->get() as $s) {
        expect($admin->can('view', $s))->toBeTrue()
            ->and($operator->can('view', $s))->toBeTrue();
    }

    $seenIds = collect();
    foreach ($customers as $customer) {
        $owned = Shipment::where('customer_id', $customer->id)->get();
        expect($owned)->toHaveCount(8);

        expect($seenIds->intersect($owned->pluck('id')))->toBeEmpty();
        $seenIds = $seenIds->merge($owned->pluck('id'));

        $other = Shipment::where('customer_id', '!=', $customer->id)->first();
        expect($customer->can('view', $owned->first()))->toBeTrue()
            ->and($customer->can('view', $other))->toBeFalse();
    }
});

test('seeding twice from fresh produces identical counts and notifications/audit logs are populated', function () {
    expect(Notification::count())->toBeGreaterThanOrEqual(8)
        ->and(AuditLog::count())->toBeGreaterThanOrEqual(8);

    $countsFirst = [
        'users' => User::count(),
        'locations' => Location::count(),
        'carriers' => Carrier::count(),
        'shipments' => Shipment::count(),
        'items' => ShipmentItem::count(),
        'events' => TrackingEvent::count(),
        'exceptions' => ShipmentException::count(),
        'notifications' => Notification::count(),
        'audits' => AuditLog::count(),
    ];

    // Re-running seeders idempotently does not duplicate records
    $this->seed();
    $countsIdempotent = [
        'users' => User::count(),
        'locations' => Location::count(),
        'carriers' => Carrier::count(),
        'shipments' => Shipment::count(),
        'items' => ShipmentItem::count(),
        'events' => TrackingEvent::count(),
        'exceptions' => ShipmentException::count(),
        'notifications' => Notification::count(),
        'audits' => AuditLog::count(),
    ];
    expect($countsIdempotent)->toBe($countsFirst);

    // Clear all seeded tables and re-seed from empty state to verify deterministic fresh seeding
    AuditLog::query()->delete();
    Notification::query()->delete();
    ShipmentException::query()->delete();
    TrackingEvent::query()->delete();
    ShipmentItem::query()->delete();
    Shipment::query()->delete();
    Carrier::query()->delete();
    Location::query()->delete();
    User::query()->delete();

    $this->seed();
    $countsSecondFresh = [
        'users' => User::count(),
        'locations' => Location::count(),
        'carriers' => Carrier::count(),
        'shipments' => Shipment::count(),
        'items' => ShipmentItem::count(),
        'events' => TrackingEvent::count(),
        'exceptions' => ShipmentException::count(),
        'notifications' => Notification::count(),
        'audits' => AuditLog::count(),
    ];
    expect($countsSecondFresh)->toBe($countsFirst);
});

test('tracking numbers per mode are non-sequential and ETA/event minutes are well spread', function () {
    foreach (['SEA', 'AIR', 'ROAD'] as $mode) {
        $suffixes = Shipment::where('transport_mode', $mode)
            ->pluck('tracking_number')
            ->map(fn ($tn) => (int) substr($tn, strrpos($tn, '-') + 1))
            ->sort()
            ->values()
            ->all();

        // No two tracking numbers in the same mode may be consecutive (+1)
        for ($i = 1; $i < count($suffixes); $i++) {
            expect($suffixes[$i] - $suffixes[$i - 1])->toBeGreaterThan(1, "Consecutive tracking numbers in mode {$mode}: {$suffixes[$i - 1]} and {$suffixes[$i]}");
        }
    }

    $distinctEtaMinutes = Shipment::whereNotNull('estimated_arrival_at')
        ->pluck('estimated_arrival_at')
        ->map(fn ($dt) => $dt->minute)
        ->unique()
        ->count();

    $distinctEventMinutes = TrackingEvent::pluck('occurred_at')
        ->map(fn ($dt) => $dt->minute)
        ->unique()
        ->count();

    expect($distinctEtaMinutes)->toBeGreaterThanOrEqual(15)
        ->and($distinctEventMinutes)->toBeGreaterThanOrEqual(20);
});

test('web pages and API endpoints return 200 with realistic codes, route names, and English labels', function () {
    $operator = User::where('role', 'operator')->first();

    // 1. Public welcome page
    $this->get('/')
        ->assertOk()
        ->assertSee('CTG-SEA-2026-00189')
        ->assertSee('CTG-SEA-2026-00244')
        ->assertSee('CTG-AIR-2026-00678')
        ->assertDontSee('Resi');

    // 2. Dashboard: over free-time count is 2..4, no +41d, no pseudo codes
    $dash = $this->actingAs($operator)->get('/dashboard');
    $dash->assertOk()
        ->assertDontSee('+41d')
        ->assertDontSee('SGCHG')
        ->assertDontSee('IDCGK');
    $overFreeTime = $dash->viewData('overFreeTimeShipments');
    expect($overFreeTime->count())->toBeGreaterThanOrEqual(2)
        ->and($overFreeTime->count())->toBeLessThanOrEqual(4);

    // 3. Shipments index (default + ROAD filter)
    $this->actingAs($operator)->get('/shipments')
        ->assertOk()
        ->assertSee('Tracking no.')
        ->assertDontSee('Waybill / Resi')
        ->assertDontSee('SGCHG')
        ->assertDontSee('IDCGK');

    $this->actingAs($operator)->get('/shipments?mode=ROAD')
        ->assertOk()
        ->assertSee('Medan depot to Belawan port')
        ->assertSee('DEP-MES');

    // 4. Exceptions index
    $this->actingAs($operator)->get('/exceptions')
        ->assertOk()
        ->assertSee('Tracking no.')
        ->assertDontSee('Shipment Resi');

    // 5. Locations index
    $this->actingAs($operator)->get('/locations')
        ->assertOk()
        ->assertSee('SIN')
        ->assertSee('CGK')
        ->assertSee('DEP-LSW')
        ->assertSee('DEP-MES')
        ->assertSee('DEP-GRS')
        ->assertSee('BRD-VEN')
        ->assertDontSee('SGCHG')
        ->assertDontSee('IDCGK')
        ->assertDontSee('IDMES');

    // 6. Weather hub
    $this->actingAs($operator)->get('/weather')
        ->assertOk();

    // 7. Public tracking for canonical air shipment
    $this->get('/track/CTG-AIR-2026-00678')
        ->assertOk()
        ->assertSee('KNO')
        ->assertSee('CDG')
        ->assertDontSee('IDKNO')
        ->assertDontSee('FRCDG')
        ->assertDontSee('SGCHG');

    // 8. API locations resource exposes IATA/UN/LOCODE/internal code rules
    $apiLocs = $this->actingAs($operator, 'sanctum')->getJson('/api/locations');
    $apiLocs->assertOk();
    $items = collect($apiLocs->json('data'));

    $sin = $items->firstWhere('code', 'SIN');
    $jkt = $items->firstWhere('code', 'IDJKT');
    $mes = $items->firstWhere('code', 'DEP-MES');

    expect($sin)->not->toBeNull()
        ->and($sin['iata_code'])->toBe('SIN')
        ->and($sin['unlocode'])->toBeNull()
        ->and($sin['code_type'])->toBe('IATA')
        ->and($jkt)->not->toBeNull()
        ->and($jkt['unlocode'])->toBe('IDJKT')
        ->and($jkt['code_type'])->toBe('UN/LOCODE')
        ->and($mes)->not->toBeNull()
        ->and($mes['unlocode'])->toBeNull()
        ->and($mes['iata_code'])->toBeNull()
        ->and($mes['code_type'])->toBe('Internal code');
});

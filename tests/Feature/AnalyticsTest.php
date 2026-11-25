<?php

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('guests are redirected to login when accessing analytics or export endpoints', function () {
    $this->get(route('analytics.index'))->assertRedirect(route('login'));
    $this->get(route('analytics.export'))->assertRedirect(route('login'));
});

test('admin and operator can access analytics page and receive global dataset metrics', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $operator = User::where('email', 'operator@cargotrace.test')->first();

    $adminResponse = $this->actingAs($admin)->get(route('analytics.index'));
    $adminResponse->assertOk()
        ->assertSee('Operations and fleet analytics')
        ->assertSee('Fleet-wide operations scope')
        ->assertViewHas('summary', fn ($s) => $s['total_shipments'] === 48);

    $operatorResponse = $this->actingAs($operator)->get(route('analytics.index'));
    $operatorResponse->assertOk()
        ->assertSee('Operations and fleet analytics')
        ->assertViewHas('summary', fn ($s) => $s['total_shipments'] === 48);
});

test('customers see strictly a summary of their own shipments and cannot view global data', function () {
    $customer1 = User::where('email', 'customer@cargotrace.test')->first();
    $c1ShipmentsCount = Shipment::where('customer_id', $customer1->id)->count();

    expect($c1ShipmentsCount)->toBe(8);

    $response = $this->actingAs($customer1)->get(route('analytics.index'));
    $response->assertOk()
        ->assertSee('Consignment analytics summary')
        ->assertSee('Customer consignments scope')
        ->assertDontSee('Fleet-wide operations scope');

    $summary = $response->viewData('summary');
    expect($summary['total_shipments'])->toBe($c1ShipmentsCount);

    // Verify carrier performance in viewData only counts customer's shipments
    $carrierPerformance = $response->viewData('carrierPerformance');
    $totalCarrierShipments = $carrierPerformance->sum('total_shipments');
    expect($totalCarrierShipments)->toBe($c1ShipmentsCount);

    // Verify lane delays in viewData only sums customer's shipments
    $laneDelays = $response->viewData('laneDelays');
    expect($laneDelays->sum('total_shipments'))->toBe($c1ShipmentsCount);
});

test('analytics service calculates aggregate correctness using factory models', function () {
    $service = app(AnalyticsService::class);
    $admin = User::factory()->admin()->create();

    $carrierA = Carrier::factory()->create(['name' => 'Atlantic Feeder Line', 'code' => 'AFLU']);
    $carrierB = Carrier::factory()->create(['name' => 'Baltic Express', 'code' => 'BEXU']);

    $port1 = Location::factory()->create(['unlocode' => 'USMIA', 'name' => 'Port of Miami', 'country_code' => 'US']);
    $port2 = Location::factory()->create(['unlocode' => 'DEBRV', 'name' => 'Port of Bremerhaven', 'country_code' => 'DE']);

    $now = Carbon::now();
    $from = $now->copy()->subDays(30);
    $to = $now->copy()->addDays(1);

    // 2 shipments for Carrier A: 1 on-time, 1 delayed (10h)
    Shipment::factory()->create([
        'carrier_id' => $carrierA->id,
        'origin_location_id' => $port1->id,
        'destination_location_id' => $port2->id,
        'is_delayed' => false,
        'delay_hours' => 0.0,
        'created_at' => $now->copy()->subDays(5),
    ]);
    Shipment::factory()->delayed(10.0, 'MEDIUM')->create([
        'carrier_id' => $carrierA->id,
        'origin_location_id' => $port1->id,
        'destination_location_id' => $port2->id,
        'created_at' => $now->copy()->subDays(4),
    ]);

    // 1 shipment for Carrier B: delayed (20h)
    $shipmentB = Shipment::factory()->delayed(20.0, 'HIGH')->create([
        'carrier_id' => $carrierB->id,
        'origin_location_id' => $port1->id,
        'destination_location_id' => $port2->id,
        'created_at' => $now->copy()->subDays(2),
    ]);

    // Add resolved exception on shipmentB (detected 12 hours ago, resolved 2 hours ago = 10 hours resolution)
    ShipmentException::factory()->create([
        'shipment_id' => $shipmentB->id,
        'exception_code' => 'CUSTOMS_HOLD',
        'severity' => 'HIGH',
        'status' => 'RESOLVED',
        'detected_at' => $now->copy()->subHours(12),
        'resolved_at' => $now->copy()->subHours(2),
        'resolved_by' => $admin->id,
    ]);

    // Test carrier performance aggregates
    $perfA = $service->getCarrierPerformance($admin, $from, $to)->firstWhere('carrier_id', $carrierA->id);
    expect($perfA)->not->toBeNull()
        ->and($perfA->total_shipments)->toBe(2)
        ->and($perfA->on_time_count)->toBe(1)
        ->and($perfA->delayed_count)->toBe(1)
        ->and((float) $perfA->on_time_rate)->toBe(50.0)
        ->and((float) $perfA->avg_delay_hours)->toBe(10.0);

    // Test lane delays aggregates
    $lanes = $service->getLaneDelays($admin, $from, $to);
    $laneMiamiRtm = $lanes->firstWhere('origin_code', 'USMIA');
    expect($laneMiamiRtm)->not->toBeNull()
        ->and($laneMiamiRtm->total_shipments)->toBe(3)
        ->and($laneMiamiRtm->delayed_count)->toBe(2)
        ->and((float) $laneMiamiRtm->avg_delay_hours)->toBe(10.0); // (0 + 10 + 20) / 3 = 10.0

    // Test resolution time aggregates (includes seeded 48h customs hold + new 10h hold => avg 29.0h)
    $resolution = $service->getAverageResolutionTime($admin, $from, $to);
    $customsRes = $resolution['by_type']->firstWhere('exception_code', 'CUSTOMS_HOLD');
    expect($customsRes)->not->toBeNull()
        ->and($customsRes->resolved_count)->toBe(2)
        ->and((float) $customsRes->avg_hours)->toBe(29.0);

    // Test top delayed lanes
    $topDelayed = $service->getTopDelayedLanes($admin, $from, $to, 5);
    expect($topDelayed->count())->toBeGreaterThanOrEqual(1);
    $delays = $topDelayed->pluck('avg_delay_hours')->map(fn ($v) => (float) $v)->toArray();
    $sortedDelays = $delays;
    rsort($sortedDelays);
    expect($delays)->toBe($sortedDelays);
});

test('empty date range renders cleanly with empty state feedback without errors', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();

    // Past range where no records exist
    $response = $this->actingAs($admin)->get(route('analytics.index', [
        'from' => '2019-01-01',
        'to' => '2019-01-10',
    ]));

    $response->assertOk()
        ->assertSee('No shipment activity found in selected date range')
        ->assertSee('Reset to last 90 days');

    $summary = $response->viewData('summary');
    expect($summary['has_data'])->toBeFalse()
        ->and($summary['total_shipments'])->toBe(0)
        ->and($summary['on_time_rate'])->toBe(0.0);
});

test('csv export streams correct table data and enforces role scoping', function () {
    $admin = User::where('email', 'admin@cargotrace.test')->first();
    $customer1 = User::where('email', 'customer@cargotrace.test')->first();

    // Admin exports carriers
    $adminExport = $this->actingAs($admin)->get(route('analytics.export', ['table' => 'carriers']));
    $adminExport->assertOk();
    expect($adminExport->headers->get('content-type'))->toContain('text/csv')
        ->and($adminExport->headers->get('content-disposition'))->toContain('attachment');

    // Customer exports carriers: content must only reflect customer's 8 shipments
    ob_start();
    $response = $this->actingAs($customer1)->get(route('analytics.export', ['table' => 'carriers']));
    $response->sendContent();
    $csvContent = ob_get_clean();

    expect($csvContent)->toContain('Carrier Code')
        ->and($csvContent)->toContain('Carrier Name')
        ->and($csvContent)->toContain('Total Shipments');

    // Parse CSV lines and verify sum of total shipments matches customer1 count (8)
    $lines = array_filter(explode("\n", trim($csvContent)));
    array_shift($lines); // remove header
    $sumShipments = 0;
    foreach ($lines as $line) {
        $cols = str_getcsv($line);
        if (isset($cols[2]) && is_numeric($cols[2])) {
            $sumShipments += (int) $cols[2];
        }
    }
    expect($sumShipments)->toBe(8);
});

<?php

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard KPIs, rates, and chart distributions match database exactly for admin, operator, and customer', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('role', 'admin')->firstOrFail();
    $operator = User::where('role', 'operator')->firstOrFail();
    $customer = User::where('role', 'customer')->orderBy('id')->firstOrFail();

    foreach ([$admin, $operator, $customer] as $user) {
        $isCustomer = $user->role === 'customer';
        $baseQuery = Shipment::query()->when($isCustomer, fn ($q) => $q->where('customer_id', $user->id));

        $expectedTotal = (clone $baseQuery)->count();
        $expectedInTransit = (clone $baseQuery)->where('status', 'IN_TRANSIT')->count();
        $expectedDelivered = (clone $baseQuery)->where('status', 'DELIVERED')->count();
        $expectedDelayed = (clone $baseQuery)->where('is_delayed', true)->where('status', '!=', 'DELIVERED')->count();
        $expectedActive = (clone $baseQuery)->whereIn('status', ['BOOKED', 'PICKED_UP', 'IN_TRANSIT'])->count();
        $expectedOpenExceptions = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->when($isCustomer, fn ($q) => $q->whereHas('shipment', fn ($sq) => $sq->where('customer_id', $user->id)))
            ->count();

        $expectedDelayRate = $expectedActive > 0 ? round(($expectedDelayed / $expectedActive) * 100, 1) : 0.0;
        $expectedExceptionRate = $expectedActive > 0 ? round(($expectedOpenExceptions / $expectedActive) * 100, 1) : 0.0;

        $expectedStatuses = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $res = $this->actingAs($user)->get(route('dashboard'));
        $res->assertOk();

        $res->assertViewHas('totalShipments', $expectedTotal)
            ->assertViewHas('inTransit', $expectedInTransit)
            ->assertViewHas('delivered', $expectedDelivered)
            ->assertViewHas('delayed', $expectedDelayed)
            ->assertViewHas('activeShipmentsCount', $expectedActive)
            ->assertViewHas('openExceptions', $expectedOpenExceptions)
            ->assertViewHas('delayRate', $expectedDelayRate)
            ->assertViewHas('exceptionRate', $expectedExceptionRate);

        $viewStatusCounts = $res->viewData('statusCounts');
        expect(array_sum($viewStatusCounts))->toBe($expectedTotal);
        foreach ($expectedStatuses as $st => $cnt) {
            expect($viewStatusCounts[$st] ?? 0)->toBe($cnt);
        }

        $carrierStats = $res->viewData('carrierStats');
        expect(collect($carrierStats)->sum('count'))->toBe($expectedTotal)
            ->and(count($carrierStats))->toBeLessThanOrEqual(7);

        // Verify KPI links, helper texts, and chart canvases + accessible legend tables render
        $res->assertSee(route('shipments.index'), false)
            ->assertSee(route('shipments.index', ['status' => 'IN_TRANSIT']), false)
            ->assertSee(route('shipments.index', ['status' => 'DELIVERED']), false)
            ->assertSee(route('shipments.index', ['is_delayed' => '1']), false)
            ->assertSee(route('shipments.index', ['has_open_exceptions' => '1']), false)
            ->assertSee('Delay rate: '.number_format($expectedDelayRate, 1).'% of '.$expectedActive.' active')
            ->assertSee('Exception rate: '.number_format($expectedExceptionRate, 1).'% of '.$expectedActive.' active')
            ->assertSee('id="shipmentsStatusChart"', false)
            ->assertSee('id="carrierBarChart"', false)
            ->assertSee('Shipments by status')
            ->assertSee('Shipments by carrier')
            ->assertSee('Shipments by status breakdown')
            ->assertSee('Shipments by carrier breakdown');
    }
});

test('dashboard KPIs and charts enforce strict customer isolation', function () {
    $customerA = User::factory()->customer()->create();
    $customerB = User::factory()->customer()->create();

    $carrierA = Carrier::factory()->create(['name' => 'Nusa Ocean Alpha', 'code' => 'NOA']);
    $carrierB = Carrier::factory()->create(['name' => 'Secret Competitor Line', 'code' => 'SCL']);
    $origin = Location::factory()->create();
    $dest = Location::factory()->create();

    // Customer A: 3 shipments (1 IN_TRANSIT delayed + open exception, 1 BOOKED on-time, 1 DELIVERED)
    $sA1 = Shipment::factory()->delayed(14.0, 'MEDIUM')->create([
        'customer_id' => $customerA->id,
        'carrier_id' => $carrierA->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $dest->id,
        'status' => 'IN_TRANSIT',
    ]);
    ShipmentException::factory()->create([
        'shipment_id' => $sA1->id,
        'status' => 'OPEN',
        'reported_by' => $customerA->id,
    ]);
    Shipment::factory()->create([
        'customer_id' => $customerA->id,
        'carrier_id' => $carrierA->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $dest->id,
        'status' => 'BOOKED',
        'is_delayed' => false,
    ]);
    Shipment::factory()->delivered()->create([
        'customer_id' => $customerA->id,
        'carrier_id' => $carrierA->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $dest->id,
        'is_delayed' => false,
    ]);

    // Customer B: 2 shipments with different carrier & open exception
    $sB1 = Shipment::factory()->delayed(30.0, 'HIGH')->create([
        'customer_id' => $customerB->id,
        'carrier_id' => $carrierB->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $dest->id,
        'status' => 'IN_TRANSIT',
    ]);
    ShipmentException::factory()->create([
        'shipment_id' => $sB1->id,
        'status' => 'INVESTIGATING',
        'reported_by' => $customerB->id,
    ]);
    Shipment::factory()->create([
        'customer_id' => $customerB->id,
        'carrier_id' => $carrierB->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $dest->id,
        'status' => 'PICKED_UP',
        'is_delayed' => false,
    ]);

    $resA = $this->actingAs($customerA)->get(route('dashboard'));
    $resA->assertOk()
        ->assertViewHas('totalShipments', 3)
        ->assertViewHas('inTransit', 1)
        ->assertViewHas('delivered', 1)
        ->assertViewHas('delayed', 1)
        ->assertViewHas('activeShipmentsCount', 2)
        ->assertViewHas('openExceptions', 1)
        ->assertViewHas('delayRate', 50.0)
        ->assertViewHas('exceptionRate', 50.0)
        ->assertSee('Nusa Ocean Alpha')
        ->assertDontSee('Secret Competitor Line');

    expect($resA->viewData('statusCounts'))->toBe([
        'BOOKED' => 1,
        'IN_TRANSIT' => 1,
        'DELIVERED' => 1,
    ]);

    $carrierStatsA = $resA->viewData('carrierStats');
    expect($carrierStatsA)->toHaveCount(1)
        ->and($carrierStatsA[0]['name'])->toBe('Nusa Ocean Alpha')
        ->and($carrierStatsA[0]['count'])->toBe(3);

    // Filtered shipments link for open exceptions must also respect customer scope
    $filtered = $this->actingAs($customerA)->get(route('shipments.index', ['has_open_exceptions' => '1']));
    $filtered->assertOk()
        ->assertSee($sA1->tracking_number)
        ->assertDontSee($sB1->tracking_number);
});

test('carrier bar chart groups top 6 carriers plus Others when more than 6 carriers exist', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $origin = Location::factory()->create();
    $dest = Location::factory()->create();

    // 8 carriers with 8..1 shipments (total = 36; top 6 = 33, Others = 3 across 2 carriers)
    $counts = [8, 7, 6, 5, 4, 3, 2, 1];
    foreach ($counts as $idx => $count) {
        $carrier = Carrier::factory()->create([
            'name' => sprintf('Carrier %02d', $idx + 1),
            'code' => sprintf('CR%02d', $idx + 1),
        ]);
        Shipment::factory()->count($count)->create([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_location_id' => $origin->id,
            'destination_location_id' => $dest->id,
        ]);
    }

    $res = $this->actingAs($admin)->get(route('dashboard'));
    $res->assertOk()
        ->assertSee('Others (2 carriers)');

    $carrierStats = $res->viewData('carrierStats');
    expect($carrierStats)->toHaveCount(7)
        ->and($carrierStats[0]['name'])->toBe('Carrier 01')
        ->and($carrierStats[0]['count'])->toBe(8)
        ->and($carrierStats[5]['name'])->toBe('Carrier 06')
        ->and($carrierStats[5]['count'])->toBe(3)
        ->and($carrierStats[6]['name'])->toBe('Others')
        ->and($carrierStats[6]['count'])->toBe(3)
        ->and($carrierStats[6]['carrier_count'])->toBe(2)
        ->and($carrierStats[6]['is_others'])->toBeTrue()
        ->and(collect($carrierStats)->sum('count'))->toBe(36);
});

test('empty database renders dashboard cleanly with zero KPIs and empty chart state for all roles', function () {
    $admin = User::factory()->admin()->create();
    $operator = User::factory()->operator()->create();
    $customer = User::factory()->customer()->create();

    foreach ([$admin, $operator, $customer] as $user) {
        $res = $this->actingAs($user)->get(route('dashboard'));
        $res->assertOk()
            ->assertViewHas('totalShipments', 0)
            ->assertViewHas('inTransit', 0)
            ->assertViewHas('delivered', 0)
            ->assertViewHas('delayed', 0)
            ->assertViewHas('openExceptions', 0)
            ->assertViewHas('delayRate', 0.0)
            ->assertViewHas('exceptionRate', 0.0)
            ->assertViewHas('statusCounts', [])
            ->assertViewHas('carrierStats', [])
            ->assertSee('id="dashboard-charts-empty-state"', false)
            ->assertSee('No shipments recorded yet')
            ->assertSee('Delay rate: 0.0% of 0 active')
            ->assertSee('Exception rate: 0.0% of 0 active');
    }
});

test('dashboard layout keeps globe as primary focus above charts and lower-priority utility cards', function () {
    $this->seed(DatabaseSeeder::class);
    $operator = User::where('role', 'operator')->firstOrFail();

    $html = $this->actingAs($operator)->get(route('dashboard'))->assertOk()->getContent();

    $posModeCards = strpos($html, 'Ocean freight (Sea)');
    $posKpiRow = strpos($html, 'data-kpi="total"');
    $posGlobe = strpos($html, 'id="dashboard-map-shell"');
    $posCharts = strpos($html, 'id="dashboard-charts-heading"');
    $posActivities = strpos($html, 'Freight activities');
    $posDwell = strpos($html, 'Terminal dwell attention');
    $posWeather = strpos($html, 'Port weather');
    $posCurrency = strpos($html, 'Currency converter');

    expect($posModeCards)->not->toBeFalse()
        ->and($posKpiRow)->toBeGreaterThan($posModeCards)
        ->and($posGlobe)->toBeGreaterThan($posKpiRow)
        ->and($posCharts)->toBeGreaterThan($posGlobe)
        ->and($posActivities)->toBeGreaterThan($posCharts)
        ->and($posDwell)->toBeGreaterThan($posActivities)
        ->and($posWeather)->toBeGreaterThan($posActivities)
        ->and($posCurrency)->toBeGreaterThan($posWeather);
});

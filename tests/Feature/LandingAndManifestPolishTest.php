<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('landing page renders multimodal hero assets, 3-column capability highlight, and no transport emojis', function () {
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('images/backgrounds/hero-multimodal-dark.jpg', false)
        ->assertSee('images/backgrounds/hero-multimodal-light.jpg', false)
        ->assertSee('lg:min-h-[760px]', false)
        ->assertSee('bg-[#EEF5FB] dark:bg-[#060D1A]', false)
        ->assertSee('hero-artwork-frame', false)
        ->assertSee('lg:object-contain lg:object-right', false)
        ->assertSee('transition-opacity duration-300 ease-in-out', false)
        ->assertSee('opacity-0 dark:opacity-100', false)
        ->assertSee('opacity-100 dark:opacity-0', false)
        ->assertSee('Sea Freight')
        ->assertSee('Air Freight')
        ->assertSee('Road Freight')
        ->assertSee('Three transport corridors for Indonesian spice exports')
        ->assertDontSee('🚢')
        ->assertDontSee('✈️')
        ->assertDontSee('🚚');

    $seaTotal = Shipment::where('transport_mode', 'SEA')->count();
    $airTotal = Shipment::where('transport_mode', 'AIR')->count();
    $roadTotal = Shipment::where('transport_mode', 'ROAD')->count();

    $response->assertSee(number_format($seaTotal).' consignments')
        ->assertSee(number_format($airTotal).' consignments')
        ->assertSee(number_format($roadTotal).' consignments');
});

test('landing page respects configured hero image overrides', function () {
    config([
        'cargotrace.hero_images.dark' => 'images/backgrounds/bg-dark.webp',
        'cargotrace.hero_images.light' => 'images/backgrounds/bg-light.webp',
    ]);

    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('images/backgrounds/bg-dark.webp', false)
        ->assertSee('images/backgrounds/bg-light.webp', false);
});

test('dashboard main statistics avoid duplication and show mode mix with svg icons', function () {
    $operator = User::where('role', 'operator')->firstOrFail();

    $res = $this->actingAs($operator)->get(route('dashboard'));
    $res->assertStatus(200)
        ->assertSee('Main statistics')
        ->assertSee('Total cargo valuation')
        ->assertSee('On-time delivery rate')
        ->assertSee('Mode mix')
        ->assertSee('Active shipments by mode')
        ->assertDontSee('🚢')
        ->assertDontSee('✈️')
        ->assertDontSee('🚚')
        ->assertDontSee('🌐')
        ->assertDontSee('🗺️');

    // Exception rate appears once in the top KPI card, not duplicated in Main statistics
    expect(substr_count($res->getContent(), 'Exception rate'))->toBe(1);
});

test('dashboard mode mix strictly scopes counts to authenticated customer', function () {
    $customer1 = User::where('role', 'customer')->orderBy('id')->firstOrFail();

    $expectedSea = Shipment::where('customer_id', $customer1->id)->where('transport_mode', 'SEA')->count();
    $expectedAir = Shipment::where('customer_id', $customer1->id)->where('transport_mode', 'AIR')->count();
    $expectedRoad = Shipment::where('customer_id', $customer1->id)->where('transport_mode', 'ROAD')->count();

    $res = $this->actingAs($customer1)->get(route('dashboard'));
    $res->assertOk();

    $modeMix = $res->viewData('modeMix');
    expect($modeMix['SEA']['count'])->toBe($expectedSea)
        ->and($modeMix['AIR']['count'])->toBe($expectedAir)
        ->and($modeMix['ROAD']['count'])->toBe($expectedRoad);
});

test('shipments manifest supports destination, date range, sorting, query preservation, and nowrap classes', function () {
    $operator = User::where('role', 'operator')->firstOrFail();
    $customer = User::where('role', 'customer')->firstOrFail();
    $carrier = Carrier::firstOrFail();
    $origin = Location::firstOrFail();
    $destA = Location::where('id', '!=', $origin->id)->firstOrFail();
    $destB = Location::whereNotIn('id', [$origin->id, $destA->id])->firstOrFail();

    // Clear seeded shipments to make sort/date/destination assertions deterministic
    Shipment::query()->delete();

    $earlySoon = Shipment::factory()->create([
        'tracking_number' => 'CTG-TEST-SOON',
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destA->id,
        'converted_value_usd' => 12000,
        'is_delayed' => false,
        'delay_hours' => 0,
        'expected_departure_at' => now()->addDays(1),
        'expected_arrival_at' => now()->addDays(4),
        'estimated_arrival_at' => now()->addDays(4),
        'created_at' => now()->subDays(2),
    ]);

    $highValueDelayed = Shipment::factory()->delayed(42.0, 'HIGH')->create([
        'tracking_number' => 'CTG-TEST-HIGHVAL',
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destA->id,
        'converted_value_usd' => 98000,
        'expected_departure_at' => now()->addDays(5),
        'expected_arrival_at' => now()->addDays(12),
        'estimated_arrival_at' => now()->addDays(14),
        'created_at' => now()->subDay(),
    ]);

    $otherDest = Shipment::factory()->create([
        'tracking_number' => 'CTG-TEST-OTHERDEST',
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destB->id,
        'converted_value_usd' => 50000,
        'is_delayed' => true,
        'delay_hours' => 6,
        'expected_departure_at' => now()->addDays(20),
        'expected_arrival_at' => now()->addDays(30),
        'estimated_arrival_at' => now()->addDays(30),
        'created_at' => now(),
    ]);

    // 1. Destination filter
    $destRes = $this->actingAs($operator)->get(route('shipments.index', [
        'destination_location_id' => $destA->id,
    ]));
    $destRes->assertOk()
        ->assertSee('CTG-TEST-SOON')
        ->assertSee('CTG-TEST-HIGHVAL')
        ->assertDontSee('CTG-TEST-OTHERDEST')
        ->assertSee('whitespace-nowrap')
        ->assertSee('tabular-nums')
        ->assertDontSee('🚢')
        ->assertDontSee('✈️')
        ->assertDontSee('🚚');

    // 2. Date range filter
    $dateRes = $this->actingAs($operator)->get(route('shipments.index', [
        'date_from' => now()->toDateString(),
        'date_to' => now()->addDays(6)->toDateString(),
    ]));
    $dateRes->assertOk()
        ->assertSee('CTG-TEST-SOON')
        ->assertDontSee('CTG-TEST-HIGHVAL')
        ->assertDontSee('CTG-TEST-OTHERDEST');

    // 3. Invalid date input does not error
    $invalidDateRes = $this->actingAs($operator)->get(route('shipments.index', [
        'date_from' => 'not-a-date',
        'date_to' => 'invalid',
    ]));
    $invalidDateRes->assertOk();

    // 4. Sorting: eta_soonest, highest_value, most_delayed
    $etaOrder = $this->actingAs($operator)
        ->get(route('shipments.index', ['sort' => 'eta_soonest']))
        ->viewData('shipments')
        ->pluck('tracking_number')
        ->all();
    expect($etaOrder)->toBe(['CTG-TEST-SOON', 'CTG-TEST-HIGHVAL', 'CTG-TEST-OTHERDEST']);

    $valOrder = $this->actingAs($operator)
        ->get(route('shipments.index', ['sort' => 'highest_value']))
        ->viewData('shipments')
        ->pluck('tracking_number')
        ->all();
    expect($valOrder)->toBe(['CTG-TEST-HIGHVAL', 'CTG-TEST-OTHERDEST', 'CTG-TEST-SOON']);

    $delayOrder = $this->actingAs($operator)
        ->get(route('shipments.index', ['sort' => 'most_delayed']))
        ->viewData('shipments')
        ->pluck('tracking_number')
        ->all();
    expect($delayOrder)->toBe(['CTG-TEST-HIGHVAL', 'CTG-TEST-OTHERDEST', 'CTG-TEST-SOON']);

    // 5. CSV export respects destination_location_id & date range filters
    $csvRes = $this->actingAs($operator)->get(route('shipments.export.csv', [
        'destination_location_id' => $destB->id,
    ]));
    $csvRes->assertOk();
    $csv = $csvRes->streamedContent();
    expect($csv)->toContain('CTG-TEST-OTHERDEST')
        ->and($csv)->not->toContain('CTG-TEST-SOON');

    // 6. Pagination links preserve filter & sort query strings
    Shipment::factory()->count(14)->create([
        'customer_id' => $customer->id,
        'carrier_id' => $carrier->id,
        'origin_location_id' => $origin->id,
        'destination_location_id' => $destA->id,
    ]);

    $pagedRes = $this->actingAs($operator)->get(route('shipments.index', [
        'destination_location_id' => $destA->id,
        'sort' => 'highest_value',
    ]));
    $pagedRes->assertOk()
        ->assertSee('destination_location_id='.$destA->id, false)
        ->assertSee('sort=highest_value', false);
});

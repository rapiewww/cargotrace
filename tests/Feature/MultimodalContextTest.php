<?php

use App\Models\Shipment;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\MaritimeNewsService;
use App\Support\Awb;
use App\Support\Iso6346;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Cache::flush();
});

test('every air shipment displays 11-digit AWB label and never a container label or SKU as container', function () {
    $operator = User::where('role', 'operator')->first();

    $airShipments = Shipment::with('items')->where('transport_mode', 'AIR')->get();
    expect($airShipments)->not->toBeEmpty();

    foreach ($airShipments as $air) {
        expect($air->formatted_container_number)->toBeNull()
            ->and($air->formatted_container_numbers)->toBeEmpty()
            ->and(Awb::isValid($air->awb_number))->toBeTrue()
            ->and($air->formatted_awb_number)->toMatch('/^\d{3}-\d{8}$/')
            ->and($air->primary_reference_label)->toBe('AWB')
            ->and($air->labeled_primary_reference)->toBe('AWB: '.$air->formatted_awb_number);

        foreach ($air->items as $item) {
            expect($item->formatted_container_number)->toBeNull();
        }
    }

    // Dashboard free-time card includes over-free-time AIR shipment (CTG-AIR-2026-00593)
    $overFreeTimeAir = Shipment::with('items')
        ->where('transport_mode', 'AIR')
        ->where('status', '!=', 'DELIVERED')
        ->whereNotNull('actual_arrival_at')
        ->first();
    expect($overFreeTimeAir)->not->toBeNull();

    $dash = $this->actingAs($operator)->get(route('dashboard'));
    $dash->assertOk()
        ->assertSee($overFreeTimeAir->tracking_number)
        ->assertSee('AWB:')
        ->assertSee($overFreeTimeAir->formatted_awb_number)
        ->assertDontSee('Container: AIR-')
        ->assertDontSee('AIR-LHC-604044');

    // Public tracking & detail pages for AIR shipment must not show Container label or AIR- SKU as container
    $this->actingAs($operator)->get(route('shipments.show', $overFreeTimeAir))
        ->assertOk()
        ->assertSee('Air Waybill (AWB)')
        ->assertSee($overFreeTimeAir->formatted_awb_number)
        ->assertDontSee('Container Number');

    $this->get(route('public.track', $overFreeTimeAir->tracking_number))
        ->assertOk()
        ->assertSee('AWB:')
        ->assertSee($overFreeTimeAir->formatted_awb_number)
        ->assertDontSee('Container:');
});

test('sea and road shipments show mode-accurate reference labels across web and API', function () {
    $operator = User::where('role', 'operator')->first();

    $sea = Shipment::with('items')->where('transport_mode', 'SEA')->orderByDesc('created_at')->first();
    expect($sea)->not->toBeNull()
        ->and(Iso6346::isValid((string) $sea->container_number))->toBeTrue()
        ->and($sea->formatted_container_number)->toMatch('/^[A-Z]{4}\s\d{6}\s\d$/')
        ->and($sea->primary_reference_label)->toBe('Container')
        ->and($sea->labeled_primary_reference)->toStartWith('Container: ');

    $road = Shipment::with('items')->where('transport_mode', 'ROAD')->orderByDesc('created_at')->first();
    expect($road)->not->toBeNull()
        ->and($road->vehicle_plate)->not->toBeEmpty()
        ->and($road->cmr_number)->not->toBeEmpty()
        ->and($road->formatted_container_number)->toBeNull()
        ->and($road->primary_reference_label)->toBe('Plate / CMR')
        ->and($road->labeled_primary_reference)->toContain('Plate: '.$road->vehicle_plate)
        ->and($road->labeled_primary_reference)->toContain('CMR: '.$road->cmr_number);

    // Shipments index shows explicit mode reference labels
    $this->actingAs($operator)->get(route('shipments.index', ['mode' => 'SEA']))
        ->assertOk()
        ->assertSee('Container:')
        ->assertSee($sea->formatted_container_number);

    $this->actingAs($operator)->get(route('shipments.index', ['mode' => 'ROAD']))
        ->assertOk()
        ->assertSee('Plate:')
        ->assertSee($road->vehicle_plate)
        ->assertSee('CMR:')
        ->assertSee($road->cmr_number);

    // Public tracking for ROAD shows Plate and CMR
    $this->get(route('public.track', $road->tracking_number))
        ->assertOk()
        ->assertSee('Plate:')
        ->assertSee($road->vehicle_plate)
        ->assertSee('CMR:')
        ->assertSee($road->cmr_number)
        ->assertDontSee('Container:');

    // API resource exposes mode reference metadata
    $air = Shipment::where('transport_mode', 'AIR')->first();
    $this->actingAs($operator, 'sanctum')->getJson('/api/shipments/'.$air->id)
        ->assertOk()
        ->assertJsonPath('data.transport_mode', 'AIR')
        ->assertJsonPath('data.container_number', null)
        ->assertJsonPath('data.formatted_awb_number', $air->formatted_awb_number)
        ->assertJsonPath('data.primary_reference_label', 'AWB');
});

test('location weather lists seaports, airports by IATA, and depots, and renders marine waves for seaports only', function () {
    $operator = User::where('role', 'operator')->first();

    Http::fake([
        'https://api.open-meteo.com/v1/forecast*' => Http::response([
            'current' => [
                'temperature_2m' => 29.1,
                'relative_humidity_2m' => 76,
                'rain' => 1.4,
                'weather_code' => 61,
                'wind_speed_10m' => 17.5,
                'visibility' => 9500,
            ],
            'daily' => [
                'time' => ['2026-11-25', '2026-11-26'],
                'weather_code' => [61, 1],
                'temperature_2m_max' => [31.0, 32.0],
                'temperature_2m_min' => [24.0, 24.5],
                'wind_speed_10m_max' => [18.0, 14.0],
                'precipitation_probability_max' => [65, 20],
            ],
        ], 200),
        'https://marine-api.open-meteo.com/v1/marine*' => Http::response([
            'current' => [
                'wave_height' => 1.35,
                'wave_direction' => 140,
                'wave_period' => 6.0,
                'swell_wave_height' => 0.75,
            ],
        ], 200),
    ]);

    // 1. Airport selected (KNO by IATA code): no marine block, no crane advisory, shows temp, wind, rain, visibility
    $airRes = $this->actingAs($operator)->get(route('weather.index', ['location' => 'KNO']));
    $airRes->assertOk()
        ->assertSee('Location Weather')
        ->assertSee('context only, not a delay prediction')
        ->assertSee('KNO')
        ->assertSee('IDJKT')
        ->assertSee('DEP-MES')
        ->assertSee('29.1°C')
        ->assertSee('17.5')
        ->assertSee('1.4')
        ->assertSee('9.5')
        ->assertSee('7-Day Airport Forecast')
        ->assertDontSee('Marine wave & swell')
        ->assertDontSee('Quay Crane')
        ->assertDontSee('container terminal');

    // 2. Depot selected (DEP-MES): no marine block, no crane advisory, shows depot wording
    $depotRes = $this->actingAs($operator)->get(route('weather.index', ['location' => 'DEP-MES']));
    $depotRes->assertOk()
        ->assertSee('7-Day Depot Forecast')
        ->assertSee('depot')
        ->assertDontSee('Marine wave & swell')
        ->assertDontSee('Quay Crane')
        ->assertDontSee('container terminal');

    // 3. Seaport selected (IDJKT): renders marine wave & swell block and port wording
    $seaRes = $this->actingAs($operator)->get(route('weather.index', ['location' => 'IDJKT']));
    $seaRes->assertOk()
        ->assertSee('Marine wave & swell')
        ->assertSee('1.35')
        ->assertSee('7-Day Port Forecast')
        ->assertDontSee('container terminal');

    // 4. Air shipment detail page shows airport weather without wave height or crane advisory
    $airShipment = Shipment::where('transport_mode', 'AIR')->first();
    $this->actingAs($operator)->get(route('shipments.show', $airShipment))
        ->assertOk()
        ->assertSee('Location weather at latest airport')
        ->assertSee('context only, not a delay prediction')
        ->assertSee('Rain')
        ->assertSee('Visibility')
        ->assertDontSee('Wave height')
        ->assertDontSee('Crane advisory');
});

test('freight news filters by All, Sea, Air, Road and matches airports, cities, and spice commodities', function () {
    $operator = User::where('role', 'operator')->first();

    $seaXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>gCaptain</title>
<item>
  <title>Belawan Nutmeg Vessel Schedule Updated</title>
  <link>https://gcaptain.com/belawan-nutmeg-update</link>
  <pubDate>Wed, 25 Nov 2026 10:00:00 +0000</pubDate>
  <category>Ocean</category>
  <description>Sea container feeders load Banda nutmeg at Belawan.</description>
</item>
</channel></rss>
XML;

    $airXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>Air Cargo News</title>
<item>
  <title>CDG Freighter Capacity Expands for Patchouli Oil</title>
  <link>https://www.aircargonews.net/cdg-patchouli-capacity</link>
  <pubDate>Wed, 25 Nov 2026 11:00:00 +0000</pubDate>
  <category>Airfreight</category>
  <description>Widebody flights into CDG handle Indonesian essential oils from KNO.</description>
</item>
</channel></rss>
XML;

    $roadXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0"><channel><title>FreightWaves</title>
<item>
  <title>Marseille to Grasse Road Corridor Adds Cloves Cold Chain</title>
  <link>https://www.freightwaves.com/marseille-grasse-cloves</link>
  <pubDate>Wed, 25 Nov 2026 09:00:00 +0000</pubDate>
  <category>Trucking</category>
  <description>Trucking fleets haul ginger, cinnamon, pepper, and cloves inland.</description>
</item>
</channel></rss>
XML;

    Http::fake([
        'https://gcaptain.com/feed/' => Http::response($seaXml, 200),
        'https://www.aircargonews.net/feed/' => Http::response($airXml, 200),
        'https://www.freightwaves.com/feed' => Http::response($roadXml, 200),
        '*' => Http::response('', 500),
    ]);

    // All shows all 3 and renders mode chips
    $allRes = $this->actingAs($operator)->get(route('news.index'));
    $allRes->assertOk()
        ->assertSee('Freight News')
        ->assertSee('All')
        ->assertSee('Sea')
        ->assertSee('Air')
        ->assertSee('Road')
        ->assertSee('Belawan Nutmeg Vessel Schedule Updated')
        ->assertSee('CDG Freighter Capacity Expands for Patchouli Oil')
        ->assertSee('Marseille to Grasse Road Corridor Adds Cloves Cold Chain');

    // Mode=AIR filters strictly to Air source articles
    $this->actingAs($operator)->get(route('news.index', ['mode' => 'AIR']))
        ->assertOk()
        ->assertSee('CDG Freighter Capacity Expands for Patchouli Oil')
        ->assertDontSee('Belawan Nutmeg Vessel Schedule Updated')
        ->assertDontSee('Marseille to Grasse Road Corridor Adds Cloves Cold Chain');

    // Mode=ROAD filters strictly to Road source articles
    $this->actingAs($operator)->get(route('news.index', ['mode' => 'ROAD']))
        ->assertOk()
        ->assertSee('Marseille to Grasse Road Corridor Adds Cloves Cold Chain')
        ->assertDontSee('CDG Freighter Capacity Expands for Patchouli Oil');

    // Mode=SEA filters strictly to Sea source articles
    $this->actingAs($operator)->get(route('news.index', ['mode' => 'SEA']))
        ->assertOk()
        ->assertSee('Belawan Nutmeg Vessel Schedule Updated')
        ->assertDontSee('CDG Freighter Capacity Expands for Patchouli Oil');

    // Relevance matching covers airports (CDG), cities (Grasse), all 6 spice commodities, and avoids partial-word false positives (e.g. SIN inside "business")
    $newsService = app(MaritimeNewsService::class);
    $allShipments = Shipment::with(['originLocation', 'destinationLocation', 'currentLocation', 'carrier', 'items'])->get();

    $probeArticles = [
        ['headline' => 'CDG ramp opens', 'summary' => 'Air cargo terminal', 'url' => 'https://example.com/1'],
        ['headline' => 'Grasse perfumery', 'summary' => 'Trucks arrive in Grasse', 'url' => 'https://example.com/2'],
        ['headline' => 'Nutmeg harvest', 'summary' => 'Banda export update', 'url' => 'https://example.com/3'],
        ['headline' => 'Cloves market', 'summary' => 'Lal pari grade update', 'url' => 'https://example.com/4'],
        ['headline' => 'Cinnamon trade', 'summary' => 'Korintje export lots', 'url' => 'https://example.com/5'],
        ['headline' => 'Pepper pricing', 'summary' => 'ASTA 570 grade report', 'url' => 'https://example.com/6'],
        ['headline' => 'Ginger root', 'summary' => 'Dried root shipments', 'url' => 'https://example.com/7'],
        ['headline' => 'Patchouli oil', 'summary' => 'Essential oil drums', 'url' => 'https://example.com/8'],
        ['headline' => 'Domestic business outlook', 'summary' => 'Unrelated corporate earnings', 'url' => 'https://example.com/9'],
    ];

    $tagged = $newsService->tagShipmentRelevance($probeArticles, $allShipments);
    for ($i = 0; $i < 8; $i++) {
        expect($tagged[$i]['is_relevant'])->toBeTrue("Article {$i} ({$tagged[$i]['headline']}) should be marked relevant");
    }
    expect($tagged[8]['is_relevant'])->toBeFalse();
});

test('currency defaults use seeded currencies and portfolio value by currency is role-scoped from database', function () {
    expect(CurrencyService::SEEDED_CURRENCIES)->toBe(['USD', 'EUR', 'INR', 'IDR'])
        ->and(array_slice(CurrencyService::PINNED_CURRENCIES, 0, 4))->toBe(['USD', 'EUR', 'INR', 'IDR']);

    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'result' => 'success',
            'time_last_update_utc' => 'Wed, 25 Nov 2026 00:00:00 +0000',
            'rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'INR' => 84.0,
                'IDR' => 15873.0,
                'SGD' => 1.34,
                'AED' => 3.67,
            ],
        ], 200),
        'https://api.frankfurter.dev/v1/currencies' => Http::response([
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'INR' => 'Indian Rupee',
            'IDR' => 'Indonesian Rupiah',
        ], 200),
        'https://api.frankfurter.dev/v1/*' => Http::response([
            'rates' => ['2026-11-24' => ['EUR' => 0.92]],
        ], 200),
    ]);

    $admin = User::where('role', 'admin')->first();
    $customer1 = User::where('email', 'customer@cargotrace.test')->first(); // IDR consignments only
    $customer2 = User::where('email', 'customer2@cargotrace.test')->first(); // EUR consignments only

    $adminRes = $this->actingAs($admin)->get(route('currency.index'));
    $adminRes->assertOk()
        ->assertViewHas('matrixCurrencies', ['USD', 'EUR', 'INR', 'IDR'])
        ->assertViewHas('totalShipmentsCount', Shipment::count())
        ->assertSee('Per 1 INR')
        ->assertSee('Per 1 IDR');

    $c1Count = Shipment::where('customer_id', $customer1->id)->count();
    $c1Res = $this->actingAs($customer1)->get(route('currency.index'));
    $c1Res->assertOk()
        ->assertViewHas('totalShipmentsCount', $c1Count);
    $c1Currencies = $c1Res->viewData('portfolioByCurrency')->pluck('currency_code')->all();
    expect($c1Currencies)->toBe(['IDR']);

    $c2Count = Shipment::where('customer_id', $customer2->id)->count();
    $c2Res = $this->actingAs($customer2)->get(route('currency.index'));
    $c2Res->assertOk()
        ->assertViewHas('totalShipmentsCount', $c2Count);
    $c2Currencies = $c2Res->viewData('portfolioByCurrency')->pluck('currency_code')->all();
    expect($c2Currencies)->toBe(['EUR']);
});

test('landing, login, and README share consistent multimodal tagline', function () {
    $tagline = 'Global spice freight by sea, air and road';

    $this->get('/')->assertOk()->assertSee($tagline);
    $this->get('/login')->assertOk()->assertSee($tagline);
    expect(file_get_contents(base_path('README.md')))->toContain($tagline);
});

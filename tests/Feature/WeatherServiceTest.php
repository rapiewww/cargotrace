<?php

use App\Models\Shipment;
use App\Models\User;
use App\Services\WeatherService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    Cache::flush();
});

test('getPortWeather fetches atmospheric data and caches per coordinates', function () {
    Http::fake([
        'https://api.open-meteo.com/v1/forecast*' => Http::response([
            'current' => [
                'temperature_2m' => 29.4,
                'relative_humidity_2m' => 80,
                'weather_code' => 1,
                'wind_speed_10m' => 18.5,
                'visibility' => 12500,
            ],
        ], 200),
    ]);

    $service = new WeatherService;
    $data = $service->getPortWeather(-6.1038, 106.8824, 'Port of Tanjung Priok');

    expect($data)->toBeArray()
        ->and($data['temperature_c'])->toBe(29.4)
        ->and($data['wind_kph'])->toBe(18.5)
        ->and($data['visibility_km'])->toBe(12.5)
        ->and($data['condition'])->toBe('Mainly Clear')
        ->and($data['data_source'])->toBe('live');

    // Second call should return cached payload
    Http::fake([
        'https://api.open-meteo.com/v1/forecast*' => Http::response([], 500),
    ]);

    $cached = $service->getPortWeather(-6.1038, 106.8824, 'Port of Tanjung Priok');
    expect($cached['data_source'])->toBe('cached')
        ->and($cached['temperature_c'])->toBe(29.4);
});

test('getMarineWeather fetches real wave and swell metrics', function () {
    Http::fake([
        'https://marine-api.open-meteo.com/v1/marine*' => Http::response([
            'current' => [
                'wave_height' => 1.45,
                'wave_direction' => 120,
                'wave_period' => 6.2,
                'swell_wave_height' => 0.85,
                'ocean_current_velocity' => 2.1,
                'ocean_current_direction' => 95,
            ],
            'daily' => [
                'time' => ['2026-11-12', '2026-11-13'],
                'wave_height_max' => [1.5, 1.3],
            ],
        ], 200),
    ]);

    $service = new WeatherService;
    $data = $service->getMarineWeather(1.264, 103.822);

    expect($data)->toBeArray()
        ->and($data['has_marine_data'])->toBeTrue()
        ->and($data['wave_height'])->toBe(1.45)
        ->and($data['wave_period'])->toBe(6.2)
        ->and($data['swell_wave_height'])->toBe(0.85)
        ->and($data['ocean_current_velocity'])->toBe(2.1);
});

test('getMarineWeather returns no marine data for inland or unsupported location', function () {
    Http::fake([
        'https://marine-api.open-meteo.com/v1/marine*' => Http::response([
            'current' => [
                'wave_height' => null,
                'wave_direction' => null,
                'wave_period' => null,
                'swell_wave_height' => null,
                'ocean_current_velocity' => null,
                'ocean_current_direction' => null,
            ],
            'daily' => [
                'time' => ['2026-11-12'],
                'wave_height_max' => [null],
            ],
        ], 200),
    ]);

    $service = new WeatherService;
    $data = $service->getMarineWeather(48.85, 2.35);

    expect($data)->toBeArray()
        ->and($data['has_marine_data'])->toBeFalse()
        ->and($data['message'])->toBe('No marine data for this location');
});

test('getPortWeather and getMarineWeather handle timeout gracefully', function () {
    Http::fake([
        'https://api.open-meteo.com/v1/forecast*' => fn () => throw new ConnectionException('Connection timed out'),
        'https://marine-api.open-meteo.com/v1/marine*' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $service = new WeatherService;
    $weather = $service->getPortWeather(-6.1038, 106.8824, 'Port of Tanjung Priok');
    $marine = $service->getMarineWeather(-6.1038, 106.8824);

    expect($weather)->toBeArray()
        ->and($weather['data_source'])->toBe('simulated')
        ->and($weather['temperature_c'])->toBeGreaterThan(20)
        ->and($marine['has_marine_data'])->toBeFalse();
});

test('getPortWeather and getMarineWeather handle empty responses gracefully', function () {
    Http::fake([
        'https://api.open-meteo.com/v1/forecast*' => Http::response([], 200),
        'https://marine-api.open-meteo.com/v1/marine*' => Http::response([], 200),
    ]);

    $service = new WeatherService;
    $weather = $service->getPortWeather(-6.1038, 106.8824, 'Port of Tanjung Priok');
    $marine = $service->getMarineWeather(-6.1038, 106.8824);

    expect($weather)->toBeArray()
        ->and(isset($weather['temperature_c']))->toBeTrue()
        ->and($marine['has_marine_data'])->toBeFalse()
        ->and($marine['message'])->toBe('No marine data for this location');
});

test('crane advisory and beaufort scale follow configuration thresholds', function () {
    $service = new WeatherService;

    // Calm / clear wind
    $crane1 = $service->getCraneAdvisory(15.0);
    expect($crane1['status'])->toBe('Full Clearance')
        ->and($crane1['color'])->toBe('emerald')
        ->and($crane1['basis'])->toBe('Rule-based advisory');

    // Cautionary
    $crane2 = $service->getCraneAdvisory(32.0);
    expect($crane2['status'])->toBe('Cautionary Operations')
        ->and($crane2['color'])->toBe('amber');

    // Suspended
    $crane3 = $service->getCraneAdvisory(45.0);
    expect($crane3['status'])->toBe('Quay Cranes Suspended')
        ->and($crane3['color'])->toBe('rose');

    // Beaufort
    $b1 = $service->getBeaufortScale(4.0);
    expect($b1['force'])->toBe(1)
        ->and($b1['basis'])->toBe('Derived from wind speed');

    $b7 = $service->getBeaufortScale(55.0);
    expect($b7['force'])->toBe(7);
});

test('weather page renders active route ports and comparison table', function () {
    $user = User::where('role', 'admin')->first();

    $response = $this->actingAs($user)->get(route('weather.index'));

    $response->assertOk()
        ->assertSee('Seaport Weather')
        ->assertSee('Open-Meteo')
        ->assertSee('Ship-to-Shore (STS) Quay Crane Advisory')
        ->assertSee('Derived from wind speed')
        ->assertSee('Rule-based advisory')
        ->assertSee('Seaport Meteorological Comparison Matrix');
});

test('shipment detail view displays weather at current location', function () {
    $user = User::where('role', 'admin')->first();
    $shipment = Shipment::first();

    $response = $this->actingAs($user)->get(route('shipments.show', $shipment));

    $response->assertOk()
        ->assertSee('Seaport Weather at Latest Location')
        ->assertSee('STS Crane Advisory');
});

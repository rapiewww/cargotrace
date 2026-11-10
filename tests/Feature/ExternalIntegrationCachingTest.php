<?php

namespace Tests\Feature;

use App\Services\ExternalIntegrationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('weather integration caches response and tags data source', function () {
    Cache::flush();
    config(['cargotrace.openweather.key' => 'mock-weather-key']);

    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'main' => [
                'temp' => 28.5,
                'feels_like' => 29.2,
                'humidity' => 65,
                'pressure' => 1012,
            ],
            'weather' => [
                ['description' => 'clear sky'],
            ],
            'wind' => [
                'speed' => 4.0,
                'deg' => 90,
            ],
        ], 200),
    ]);

    $service = app(ExternalIntegrationService::class);

    // First call (Live)
    $weather1 = $service->getWeatherByCoordinates(1.264, 103.822, 'Singapore');
    expect($weather1['temperature_c'])->toBe(28.5)
        ->and($weather1['data_source'])->toBe('live');

    // Second call should come from cache without HTTP request
    Http::fake([
        'api.openweathermap.org/*' => Http::response([], 500),
    ]);

    $weather2 = $service->getWeatherByCoordinates(1.264, 103.822, 'Singapore');
    expect($weather2['temperature_c'])->toBe(28.5)
        ->and($weather2['data_source'])->toBe('cached');
});

test('weather integration falls back to simulated telemetry when api fails', function () {
    Cache::flush();
    config(['cargotrace.openweather.key' => 'mock-weather-key']);

    Http::fake([
        'api.openweathermap.org/*' => Http::response([], 500),
    ]);

    $service = app(ExternalIntegrationService::class);
    $weather = $service->getWeatherByCoordinates(-33.868, 151.209, 'Sydney');

    expect($weather['data_source'])->toBe('simulated')
        ->and($weather)->toHaveKeys(['temperature_c', 'condition', 'wind_kph']);
});

test('currency converter caches fx rates snapshot', function () {
    Cache::flush();

    Http::fake([
        'open.er-api.com/*' => Http::response([
            'result' => 'success',
            'rates' => [
                'USD' => 1.085,
                'EUR' => 1.0,
                'IDR' => 17000.0,
            ],
        ], 200),
    ]);

    $service = app(ExternalIntegrationService::class);

    $conv1 = $service->convertCurrency(100, 'EUR', 'USD');
    expect($conv1['data_source'])->toBe('live')
        ->and($conv1['converted_amount'])->toBe(108.5);

    // Second call should return cached
    $conv2 = $service->convertCurrency(100, 'EUR', 'USD');
    expect($conv2['data_source'])->toBe('cached')
        ->and($conv2['converted_amount'])->toBe(108.5);
});

test('news service falls back to simulated bulletins on api failure', function () {
    Cache::flush();

    Http::fake([
        'newsdata.io/*' => Http::response([], 500),
    ]);

    $service = app(ExternalIntegrationService::class);
    $news = $service->getLogisticsNews();

    expect($news)->toBeArray()
        ->and(count($news))->toBeGreaterThan(0)
        ->and($news[0]['data_source'])->toBe('simulated');
});

<?php

namespace Tests\Feature;

use App\Services\CurrencyService;
use App\Services\ExternalIntegrationService;
use App\Services\MaritimeNewsService;
use App\Services\WeatherService;
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

test('open meteo weather service fetches and caches port weather', function () {
    Cache::flush();

    Http::fake([
        'api.open-meteo.com/*' => Http::response([
            'current' => [
                'temperature_2m' => 29.8,
                'relative_humidity_2m' => 72,
                'weather_code' => 1,
                'wind_speed_10m' => 16.2,
            ],
        ], 200),
    ]);

    $service = app(WeatherService::class);
    $data1 = $service->getPortWeather(-6.1, 106.88, 'Port of Tanjung Priok');

    expect($data1['temperature_c'])->toBe(29.8)
        ->and($data1['condition'])->toBe('Mainly Clear')
        ->and($data1['data_source'])->toBe('live');

    // Second call should be cached
    Http::fake([
        'api.open-meteo.com/*' => Http::response([], 500),
    ]);

    $data2 = $service->getPortWeather(-6.1, 106.88, 'Port of Tanjung Priok');
    expect($data2['data_source'])->toBe('cached')
        ->and($data2['temperature_c'])->toBe(29.8);
});

test('currency service converts currencies and exposes api route', function () {
    Cache::flush();

    Http::fake([
        'open.er-api.com/*' => Http::response([
            'result' => 'success',
            'time_last_update_utc' => 'Wed, 11 Nov 2026 00:02:32 +0000',
            'rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'IDR' => 16000.0,
                'SGD' => 1.35,
                'CNY' => 7.24,
                'AUD' => 1.53,
            ],
        ], 200),
        'api.frankfurter.app/*' => Http::response([
            'base' => 'USD',
            'rates' => [
                'EUR' => 0.92,
                'IDR' => 16000.0,
                'SGD' => 1.35,
                'CNY' => 7.24,
                'AUD' => 1.53,
            ],
        ], 200),
    ]);

    $service = app(CurrencyService::class);
    $conv = $service->convert(100, 'USD', 'EUR');

    expect($conv['converted_amount'])->toBe(92.0)
        ->and($conv['data_source'])->toBe('live');

    // Test the API endpoint /api/currency/convert
    $response = $this->getJson('/api/currency/convert?from=USD&to=EUR&amount=100');
    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => [
                'from' => 'USD',
                'to' => 'EUR',
                'amount' => 100,
                'converted_amount' => 92.0,
            ],
        ]);
});

test('maritime news service parses rss feed or falls back gracefully', function () {
    Cache::flush();

    $rssXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>gCaptain Maritime News</title>
    <item>
      <title>Global Container Freight Rates Normalize Along Transpacific Routes</title>
      <link>https://gcaptain.com/test-article</link>
      <pubDate>Wed, 11 Nov 2026 12:00:00 +0000</pubDate>
      <category>Container Shipping</category>
      <description>Freight spot rates between Asia and the US West Coast continue stabilization.</description>
    </item>
  </channel>
</rss>
XML;

    Http::fake([
        '*gcaptain.com*' => Http::response($rssXml, 200, ['Content-Type' => 'application/rss+xml']),
        '*' => Http::response('', 500),
    ]);

    $service = app(MaritimeNewsService::class);
    $news = $service->getMaritimeNews(5);

    expect($news)->toBeArray()
        ->and(count($news))->toBeGreaterThan(0)
        ->and($news[0]['headline'])->toContain('Container Freight Rates')
        ->and($news[0]['data_source'])->toBe('live');
});

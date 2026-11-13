<?php

use App\Models\User;
use App\Services\CurrencyService;
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

test('currency service fetches rates from open.er-api.com and caches result for 1 hour', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'result' => 'success',
            'provider' => 'https://www.exchangerate-api.com',
            'time_last_update_utc' => 'Wed, 11 Nov 2026 00:02:32 +0000',
            'rates' => [
                'USD' => 1.0,
                'EUR' => 0.92,
                'IDR' => 15873.0,
                'SGD' => 1.34,
                'JPY' => 154.5,
            ],
        ], 200),
    ]);

    $service = app(CurrencyService::class);
    $rates = $service->getRates('USD');

    expect($rates)->toHaveKey('rates')
        ->and($rates['data_source'])->toBe('live')
        ->and($rates['time_last_update_utc'])->toBe('Wed, 11 Nov 2026 00:02:32 +0000')
        ->and($rates['rates']['EUR'])->toBe(0.92);

    expect(Cache::has(CurrencyService::CACHE_KEY))->toBeTrue();

    // Second call uses cache
    $cached = $service->getRates('USD');
    expect($cached['data_source'])->toBe('cached');
});

test('currency convert accurately calculates amount without silent fallback', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'result' => 'success',
            'time_last_update_utc' => 'Wed, 11 Nov 2026 00:02:32 +0000',
            'rates' => [
                'USD' => 1.0,
                'EUR' => 0.90,
                'IDR' => 15000.0,
            ],
        ], 200),
    ]);

    $service = app(CurrencyService::class);

    // 100 USD to EUR at 0.90 = 90 EUR
    $res = $service->convert(100, 'USD', 'EUR');
    expect($res['converted_amount'])->toBe(90.0)
        ->and($res['from'])->toBe('USD')
        ->and($res['to'])->toBe('EUR');

    // 90 EUR to IDR: 90 / 0.90 * 15000 = 1,500,000 IDR
    $res2 = $service->convert(90, 'EUR', 'IDR');
    expect($res2['converted_amount'])->toBe(1500000.0);
});

test('currency convert throws InvalidArgumentException on unknown currency code', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'result' => 'success',
            'rates' => ['USD' => 1.0, 'EUR' => 0.92],
        ], 200),
    ]);

    $service = app(CurrencyService::class);

    expect(fn () => $service->convert(100, 'USD', 'UNKNOWN_XYZ'))
        ->toThrow(InvalidArgumentException::class, 'Unknown currency code [UNKNOWN_XYZ].');
});

test('api convert endpoint returns 422 when unknown currency is requested', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'result' => 'success',
            'rates' => ['USD' => 1.0, 'EUR' => 0.92],
        ], 200),
    ]);

    $response = $this->getJson('/api/currency/convert?amount=100&from=USD&to=BADCODE');

    $response->assertStatus(422)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unknown currency code [BADCODE].',
        ]);
});

test('api currencies endpoint returns list of all supported currencies', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'result' => 'success',
            'rates' => ['USD' => 1.0, 'EUR' => 0.92, 'IDR' => 15873.0, 'SGD' => 1.34],
        ], 200),
    ]);

    $response = $this->getJson('/api/currency/currencies');

    $response->assertOk()
        ->assertJson([
            'status' => 'success',
            'data' => ['EUR', 'IDR', 'SGD', 'USD'],
        ]);
});

test('currency service falls back to stale cache on network timeout', function () {
    // Prime stale cache
    Cache::forever(CurrencyService::STALE_CACHE_KEY, [
        'base' => 'USD',
        'rates' => ['USD' => 1.0, 'EUR' => 0.91],
        'time_last_update_utc' => 'Tue, 10 Nov 2026 00:00:00 +0000',
        'source' => 'ExchangeRate-API (open.er-api.com)',
        'data_source' => 'cached',
    ]);

    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::failedConnection(),
    ]);

    $service = app(CurrencyService::class);
    $rates = $service->getRates('USD');

    expect($rates['data_source'])->toBe('cached')
        ->and($rates['rates']['EUR'])->toBe(0.91);
});

test('currency service throws RuntimeException when network times out and no cache exists', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::failedConnection(),
    ]);

    $service = app(CurrencyService::class);

    expect(fn () => $service->getRates('USD'))
        ->toThrow(RuntimeException::class);
});

test('currency service handles empty response from open.er-api gracefully', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([], 200),
    ]);

    $service = app(CurrencyService::class);

    expect(fn () => $service->getRates('USD'))
        ->toThrow(RuntimeException::class);
});

test('currency service getHistory handles success, timeout, and empty response', function () {
    // 1. Success
    Http::fake([
        'https://api.frankfurter.dev/v1/currencies' => Http::response(['USD' => 'US Dollar', 'EUR' => 'Euro'], 200),
        'https://api.frankfurter.dev/v1/*' => Http::response([
            'rates' => [
                '2026-11-01' => ['EUR' => 0.92],
                '2026-11-02' => ['EUR' => 0.93],
            ],
        ], 200),
    ]);

    $service = app(CurrencyService::class);
    $history = $service->getHistory('USD', 'EUR', 30);
    expect($history)->toBeArray()
        ->and($history)->toHaveCount(2)
        ->and($history[0]['rate'])->toBe(0.92);

    // 2. Timeout
    Cache::flush();
    Http::fake([
        'https://api.frankfurter.dev/v1/*' => fn () => throw new ConnectionException('Timeout'),
    ]);
    expect($service->getHistory('USD', 'EUR', 30))->toBeNull();

    // 3. Empty response
    Cache::flush();
    Http::fake([
        'https://api.frankfurter.dev/v1/*' => Http::response([], 200),
    ]);
    expect($service->getHistory('USD', 'EUR', 30))->toBeNull();
});

test('currency web page displays portfolio value by currency and no fake bunker indices', function () {
    Http::fake([
        'https://open.er-api.com/v6/latest/USD' => Http::response([
            'result' => 'success',
            'time_last_update_utc' => 'Wed, 11 Nov 2026 00:02:32 +0000',
            'rates' => ['USD' => 1.0, 'EUR' => 0.92, 'IDR' => 15873.0, 'SGD' => 1.34, 'CNY' => 7.23],
        ], 200),
        'https://api.frankfurter.dev/v1/currencies' => Http::response(['USD' => 'US Dollar', 'EUR' => 'Euro'], 200),
        'https://api.frankfurter.dev/v1/*' => Http::response(['rates' => []], 200),
    ]);

    $admin = User::where('role', 'admin')->first();

    $response = $this->actingAs($admin)->get(route('currency.index'));

    $response->assertOk();
    $response->assertSee('Portfolio Value by Currency');
    $response->assertDontSee('Vessel Bunker Fuel Index');
    $response->assertDontSee('Quick Freight Benchmarks');
    $response->assertDontSee('Transpacific Eastbound');
});

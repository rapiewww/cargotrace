<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CurrencyService
{
    public const CACHE_KEY = 'currency_rates_matrix_USD';

    public const STALE_CACHE_KEY = 'currency_rates_matrix_USD_stale';

    public const CACHE_TTL = 3600; // 1 hour

    /**
     * Seeded CargoTrace portfolio currencies.
     *
     * @var array<string>
     */
    public const SEEDED_CURRENCIES = ['USD', 'EUR', 'INR', 'IDR'];

    /**
     * Commonly used freight trade currencies pinned at the top of comboboxes.
     *
     * @var array<string>
     */
    public const PINNED_CURRENCIES = ['USD', 'EUR', 'INR', 'IDR', 'SGD', 'AED', 'MYR', 'GBP', 'JPY', 'AUD', 'CNY'];

    /**
     * Fetch all world currency rates from open.er-api.com, cached for 1 hour.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException When API is unavailable and no cache exists.
     * @throws InvalidArgumentException When base currency is unknown.
     */
    public function getRates(string $base = 'USD'): array
    {
        $base = strtoupper(trim($base));

        // 1. Check fresh 1-hour cache
        $usdPayload = Cache::get(self::CACHE_KEY);

        if (! $usdPayload) {
            try {
                $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/USD');

                if ($response->successful()) {
                    $json = $response->json();
                    if (($json['result'] ?? '') === 'success' && isset($json['rates']) && is_array($json['rates'])) {
                        $rates = [];
                        foreach ($json['rates'] as $cur => $val) {
                            $rates[strtoupper($cur)] = (float) $val;
                        }

                        $updateUtc = $json['time_last_update_utc'] ?? Carbon::now()->toRfc7231String();
                        $nextUtc = $json['time_next_update_utc'] ?? null;

                        $usdPayload = [
                            'base' => 'USD',
                            'rates' => $rates,
                            'time_last_update_utc' => $updateUtc,
                            'time_next_update_utc' => $nextUtc,
                            'last_updated' => $updateUtc,
                            'source' => 'ExchangeRate-API (open.er-api.com)',
                            'provider_url' => 'https://www.exchangerate-api.com',
                            'attribution_text' => 'Rates By ExchangeRate-API',
                            'data_source' => 'live',
                        ];

                        Cache::put(self::CACHE_KEY, $usdPayload, self::CACHE_TTL);
                        Cache::forever(self::STALE_CACHE_KEY, $usdPayload);
                    }
                }
            } catch (Throwable $e) {
                Log::warning('ExchangeRate-API fetch failed: '.$e->getMessage());
            }
        } else {
            $usdPayload['data_source'] = 'cached';
        }

        // 2. If fresh fetch failed and no fresh cache, use stale cache marked CACHED
        if (! $usdPayload) {
            $stale = Cache::get(self::STALE_CACHE_KEY);
            if ($stale && is_array($stale)) {
                $stale['data_source'] = 'cached';
                $usdPayload = $stale;
            }
        }

        // 3. If no cache exists at all, do NOT invent numbers; fail cleanly
        if (! $usdPayload) {
            throw new RuntimeException('Exchange rates service is temporarily unavailable and no cached rates are available.');
        }

        if ($base === 'USD') {
            return $usdPayload;
        }

        // Rebase relative to requested base currency
        $rates = $usdPayload['rates'];
        if (! isset($rates[$base])) {
            throw new InvalidArgumentException("Unknown currency code [{$base}].");
        }

        $baseRate = (float) $rates[$base];
        if ($baseRate <= 0) {
            throw new InvalidArgumentException("Invalid rate for base currency [{$base}].");
        }

        $rebasedRates = [];
        foreach ($rates as $cur => $val) {
            $rebasedRates[$cur] = round(((float) $val) / $baseRate, 6);
        }

        $result = $usdPayload;
        $result['base'] = $base;
        $result['rates'] = $rebasedRates;

        return $result;
    }

    /**
     * Get all supported currency codes sorted alphabetically.
     *
     * @return array<string>
     */
    public function getCurrencies(): array
    {
        $ratesData = $this->getRates('USD');
        $codes = array_keys($ratesData['rates'] ?? []);
        sort($codes);

        return $codes;
    }

    /**
     * Get list of currencies supported by Frankfurter API (ECB reference rates).
     *
     * @return array<string>
     */
    public function getFrankfurterCurrencies(): array
    {
        return Cache::remember('frankfurter_supported_currencies_list', 86400, function () {
            try {
                $response = Http::timeout(4)->get('https://api.frankfurter.dev/v1/currencies');
                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        return array_keys($json);
                    }
                }
            } catch (Throwable $e) {
                Log::info('Frankfurter currencies fetch failed: '.$e->getMessage());
            }

            return ['AUD', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HUF', 'IDR', 'ILS', 'INR', 'ISK', 'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR'];
        });
    }

    /**
     * Get 30-day historical exchange rates from Frankfurter API.
     * Returns null if either currency is not supported by Frankfurter.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function getHistory(string $from, string $to, int $days = 30): ?array
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        if ($from === $to) {
            return null;
        }

        $supported = $this->getFrankfurterCurrencies();
        if (! in_array($from, $supported, true) || ! in_array($to, $supported, true)) {
            return null;
        }

        $cacheKey = "frankfurter_history_{$from}_{$to}_{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to, $days) {
            try {
                $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
                $endDate = Carbon::now()->format('Y-m-d');

                $response = Http::timeout(5)->get("https://api.frankfurter.dev/v1/{$startDate}..{$endDate}", [
                    'from' => $from,
                    'to' => $to,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $ratesMap = $json['rates'] ?? [];
                    $history = [];
                    foreach ($ratesMap as $date => $dayRates) {
                        if (isset($dayRates[$to])) {
                            $history[] = [
                                'date' => $date,
                                'rate' => (float) $dayRates[$to],
                            ];
                        }
                    }

                    return ! empty($history) ? $history : null;
                }
            } catch (Throwable $e) {
                Log::warning("Frankfurter history fetch failed for {$from}->{$to}: ".$e->getMessage());
            }

            return null;
        });
    }

    /**
     * Convert currency from one code to another.
     * Validates both codes; throws InvalidArgumentException on unknown code.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function convert(float $amount, string $from = 'USD', string $to = 'IDR'): array
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        $ratesData = $this->getRates('USD');
        $rates = $ratesData['rates'] ?? [];

        if (! isset($rates[$from])) {
            throw new InvalidArgumentException("Unknown currency code [{$from}].");
        }

        if (! isset($rates[$to])) {
            throw new InvalidArgumentException("Unknown currency code [{$to}].");
        }

        $rateFrom = (float) $rates[$from];
        $rateTo = (float) $rates[$to];

        if ($rateFrom <= 0) {
            throw new InvalidArgumentException("Invalid exchange rate for currency [{$from}].");
        }

        $effectiveRate = $rateTo / $rateFrom;
        $convertedAmount = $amount * $effectiveRate;

        // Zero decimals for IDR, JPY, KRW, otherwise 2 or 4 decimals
        $decimals = in_array($to, ['IDR', 'JPY', 'KRW', 'VND', 'CLP'], true) ? 0 : 2;
        $formatted = number_format($convertedAmount, $decimals);

        return [
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'rate' => round($effectiveRate, 6),
            'converted_amount' => round($convertedAmount, 4),
            'formatted_result' => "{$formatted} {$to}",
            'rates_snapshot' => $rates,
            'source' => $ratesData['source'] ?? 'ExchangeRate-API',
            'provider_url' => $ratesData['provider_url'] ?? 'https://www.exchangerate-api.com',
            'attribution_text' => $ratesData['attribution_text'] ?? 'Rates By ExchangeRate-API',
            'data_source' => $ratesData['data_source'] ?? 'live',
            'time_last_update_utc' => $ratesData['time_last_update_utc'] ?? null,
            'last_updated' => $ratesData['time_last_update_utc'] ?? null,
        ];
    }
}

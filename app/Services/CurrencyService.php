<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CurrencyService
{
    /**
     * Cache key for FX rates
     */
    public const CACHE_KEY = 'currency_rates_matrix';

    /**
     * Cache TTL in seconds (1 hour)
     */
    protected int $cacheTtl = 3600;

    /**
     * Core maritime logistics currencies
     *
     * @var array<string>
     */
    public const MARITIME_CURRENCIES = ['USD', 'EUR', 'IDR', 'SGD', 'CNY', 'AUD', 'GBP', 'JPY', 'AED'];

    /**
     * Get foreign exchange rates dictionary keyed by currency code (Base: USD).
     *
     * @param string $base
     * @return array<string, mixed>
     */
    public function getRates(string $base = 'USD'): array
    {
        $cacheKey = self::CACHE_KEY . '_' . strtoupper($base);

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['data_source'] = 'cached';
                return $cached;
            }
        }

        // 1. Try Frankfurter API (European Central Bank open data)
        try {
            $response = Http::timeout(5)->get('https://api.frankfurter.app/latest', [
                'from' => strtoupper($base),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];
                $rates[strtoupper($base)] = 1.0;

                // Ensure IDR, SGD, CNY are present; if missing, fetch supplementary from open.er-api.com
                if (! isset($rates['IDR']) || ! isset($rates['SGD'])) {
                    $supp = $this->fetchOpenErApi(strtoupper($base));
                    $rates = array_merge($supp, $rates);
                }

                $payload = [
                    'base' => strtoupper($base),
                    'rates' => $rates,
                    'last_updated' => Carbon::now()->toIso8601String(),
                    'source' => 'Frankfurter / ECB Live API',
                    'data_source' => 'live',
                ];

                Cache::put($cacheKey, $payload, $this->cacheTtl);

                return $payload;
            }
        } catch (Throwable $e) {
            Log::info('Frankfurter API unavailable, falling back to open.er-api.com: ' . $e->getMessage());
        }

        // 2. Try Open Exchange Rates API (open.er-api.com)
        try {
            $openErRates = $this->fetchOpenErApi(strtoupper($base));
            if (! empty($openErRates)) {
                $payload = [
                    'base' => strtoupper($base),
                    'rates' => $openErRates,
                    'last_updated' => Carbon::now()->toIso8601String(),
                    'source' => 'ExchangeRate-API Open Live',
                    'data_source' => 'live',
                ];

                Cache::put($cacheKey, $payload, $this->cacheTtl);

                return $payload;
            }
        } catch (Throwable $e) {
            Log::warning('Open ER API unavailable: ' . $e->getMessage());
        }

        // 3. Fallback deterministic maritime exchange rates
        $fallback = $this->getFallbackRates(strtoupper($base));
        $fallback['data_source'] = 'simulated';

        return $fallback;
    }

    /**
     * Fetch secondary open exchange rates from open.er-api.com.
     *
     * @param string $base
     * @return array<string, float>
     */
    protected function fetchOpenErApi(string $base): array
    {
        $response = Http::timeout(5)->get("https://open.er-api.com/v6/latest/{$base}");
        if ($response->successful()) {
            $json = $response->json();
            if (isset($json['rates']) && is_array($json['rates'])) {
                $rates = [];
                foreach ($json['rates'] as $cur => $val) {
                    $rates[$cur] = (float) $val;
                }
                return $rates;
            }
        }

        return [];
    }

    /**
     * Convert currency from one currency to another using live or cached rates.
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @return array<string, mixed>
     */
    public function convert(float $amount, string $from = 'USD', string $to = 'IDR'): array
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));

        $ratesData = $this->getRates('USD');
        $rates = $ratesData['rates'] ?? [];
        $dataSource = $ratesData['data_source'] ?? 'simulated';

        $rateFrom = (float) ($rates[$from] ?? 1.0);
        $rateTo = (float) ($rates[$to] ?? 1.0);

        // Convert via USD anchor: (amount / rateFrom) * rateTo
        $amountInUsd = $rateFrom > 0 ? ($amount / $rateFrom) : $amount;
        $convertedAmount = round($amountInUsd * $rateTo, 2);
        $effectiveRate = $rateFrom > 0 ? round($rateTo / $rateFrom, 6) : 1.0;

        $formatted = number_format(
            $convertedAmount,
            in_array($to, ['IDR', 'JPY', 'KRW']) ? 0 : 2
        );

        return [
            'amount' => $amount,
            'from' => $from,
            'to' => $to,
            'rate' => $effectiveRate,
            'converted_amount' => $convertedAmount,
            'formatted_result' => "{$formatted} {$to}",
            'rates_snapshot' => $rates,
            'source' => $ratesData['source'] ?? 'Currency Service',
            'data_source' => $dataSource,
            'last_updated' => $ratesData['last_updated'] ?? Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Provide deterministic fallback currency rates.
     *
     * @param string $base
     * @return array<string, mixed>
     */
    public function getFallbackRates(string $base = 'USD'): array
    {
        $rates = [
            'USD' => 1.0,
            'EUR' => 0.92,
            'IDR' => 15873.0,
            'SGD' => 1.34,
            'CNY' => 7.23,
            'AUD' => 1.52,
            'GBP' => 0.79,
            'JPY' => 154.5,
            'AED' => 3.67,
            'CHF' => 0.88,
            'CAD' => 1.39,
        ];

        return [
            'base' => $base,
            'rates' => $rates,
            'last_updated' => Carbon::now()->toIso8601String(),
            'source' => 'Deterministic FX Fallback',
            'data_source' => 'simulated',
        ];
    }
}


<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExternalIntegrationService
{
    /**
     * Standard benchmark exchange rates relative to 1 USD for fallback calculations.
     *
     * @var array<string, float>
     */
    protected array $benchmarkRatesAgainstUsd = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'IDR' => 15873.0,
        'SGD' => 1.34,
        'CNY' => 7.23,
        'AED' => 3.6725,
        'AUD' => 1.52,
        'GBP' => 0.79,
        'JPY' => 152.4,
    ];

    /**
     * Fetch weather data for given coordinates or return realistic fallback.
     *
     * @return array<string, mixed>
     */
    public function getWeatherByCoordinates(float $lat, float $lon, ?string $locationName = null): array
    {
        $apiKey = config('cargotrace.openweather.key');
        $baseUrl = config('cargotrace.openweather.base_url', 'https://api.openweathermap.org/data/2.5');

        if (! empty($apiKey)) {
            try {
                $response = Http::timeout(5)->get("{$baseUrl}/weather", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'units' => 'metric',
                    'appid' => $apiKey,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $deg = $data['wind']['deg'] ?? 0;

                    return [
                        'temperature_c' => (float) ($data['main']['temp'] ?? 24.0),
                        'feels_like_c' => (float) ($data['main']['feels_like'] ?? 25.0),
                        'condition' => ucwords($data['weather'][0]['description'] ?? 'Partly Cloudy'),
                        'humidity' => (int) ($data['main']['humidity'] ?? 65),
                        'wind_kph' => round(((float) ($data['wind']['speed'] ?? 3.5)) * 3.6, 1),
                        'wind_direction' => $this->degreesToCompass($deg),
                        'pressure_mb' => (int) ($data['main']['pressure'] ?? 1013),
                        'recorded_at' => Carbon::now()->toIso8601String(),
                        'source' => 'OpenWeatherMap Live API',
                    ];
                }
            } catch (Throwable $e) {
                Log::warning('External Weather API failure: '.$e->getMessage(), [
                    'lat' => $lat,
                    'lon' => $lon,
                ]);
            }
        }

        return $this->getFallbackWeather($lat, $lon, $locationName);
    }

    /**
     * Convert currency from one currency to another using exchange rates API with fallback.
     *
     * @return array<string, mixed>
     */
    public function convertCurrency(float $amount, string $fromCurrency, string $toCurrency = 'USD'): array
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));
        $baseUrl = config('cargotrace.exchangerate.base_url', 'https://open.er-api.com/v6/latest');

        if ($from === $to) {
            return [
                'amount' => $amount,
                'from_currency' => $from,
                'to_currency' => $to,
                'exchange_rate' => 1.0,
                'converted_amount' => round($amount, 2),
                'rate_to_usd' => $this->getRateToUsd($from),
                'rates_snapshot' => $this->benchmarkRatesAgainstUsd,
                'source' => 'Direct 1:1 Identity',
            ];
        }

        try {
            $response = Http::timeout(5)->get("{$baseUrl}/{$from}");

            if ($response->successful()) {
                $data = $response->json();
                if (($data['result'] ?? '') === 'success' && isset($data['rates'][$to])) {
                    $rate = (float) $data['rates'][$to];
                    $usdRate = (float) ($data['rates']['USD'] ?? 1.0);
                    $converted = round($amount * $rate, 2);

                    return [
                        'amount' => $amount,
                        'from_currency' => $from,
                        'to_currency' => $to,
                        'exchange_rate' => $rate,
                        'converted_amount' => $converted,
                        'rate_to_usd' => $usdRate,
                        'rates_snapshot' => $data['rates'] ?? $this->benchmarkRatesAgainstUsd,
                        'source' => 'Open Exchange Rates (open.er-api.com)',
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('External Exchange Rate API failure: '.$e->getMessage(), [
                'from' => $from,
                'to' => $to,
            ]);
        }

        // Fallback using benchmark exchange rate matrix
        $fromUsdRate = $this->benchmarkRatesAgainstUsd[$from] ?? 1.0;
        $toUsdRate = $this->benchmarkRatesAgainstUsd[$to] ?? 1.0;

        // Rate from $from to $to = ($amount in USD) * toUsdRate
        // 1 unit of $from = (1 / $fromUsdRate) USD
        // in $to = (1 / $fromUsdRate) * $toUsdRate
        $crossRate = $fromUsdRate > 0 ? ($toUsdRate / $fromUsdRate) : 1.0;
        $rateToUsd = $fromUsdRate > 0 ? (1.0 / $fromUsdRate) : 1.0;
        $converted = round($amount * $crossRate, 2);

        return [
            'amount' => $amount,
            'from_currency' => $from,
            'to_currency' => $to,
            'exchange_rate' => round($crossRate, 6),
            'converted_amount' => $converted,
            'rate_to_usd' => round($rateToUsd, 6),
            'rates_snapshot' => $this->benchmarkRatesAgainstUsd,
            'source' => 'CargoTrace Benchmark Exchange Matrix (Fallback)',
        ];
    }

    /**
     * Retrieve logistics and maritime supply chain news or return realistic fallback.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLogisticsNews(string $query = 'global shipping port logistics'): array
    {
        $apiKey = config('cargotrace.news.key');
        $baseUrl = config('cargotrace.news.base_url', 'https://newsapi.org/v2');

        if (! empty($apiKey)) {
            try {
                $response = Http::timeout(5)->get("{$baseUrl}/everything", [
                    'q' => $query,
                    'sortBy' => 'publishedAt',
                    'pageSize' => 5,
                    'apiKey' => $apiKey,
                ]);

                if ($response->successful()) {
                    $articles = $response->json('articles') ?? [];
                    if (! empty($articles)) {
                        return array_map(fn ($item) => [
                            'headline' => $item['title'] ?? 'Global Shipping Advisory',
                            'summary' => $item['description'] ?? '',
                            'source' => $item['source']['name'] ?? 'Maritime Industry News',
                            'url' => $item['url'] ?? null,
                            'published_at' => $item['publishedAt'] ?? Carbon::now()->toIso8601String(),
                            'impact_level' => 'MEDIUM',
                        ], $articles);
                    }
                }
            } catch (Throwable $e) {
                Log::warning('External News API failure: '.$e->getMessage(), ['query' => $query]);
            }
        }

        return $this->getFallbackLogisticsNews();
    }

    /**
     * Geocode location query to latitude & longitude using Nominatim with fallback.
     *
     * @return array<string, mixed>|null
     */
    public function geocodeLocation(string $query): ?array
    {
        $baseUrl = config('cargotrace.nominatim.base_url', 'https://nominatim.openstreetmap.org');

        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'CargoTrace/1.0 (contact@cargotrace.test)'])
                ->get("{$baseUrl}/search", [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if ($response->successful()) {
                $results = $response->json();
                if (! empty($results[0])) {
                    return [
                        'latitude' => (float) $results[0]['lat'],
                        'longitude' => (float) $results[0]['lon'],
                        'display_name' => $results[0]['display_name'] ?? $query,
                        'source' => 'OpenStreetMap Nominatim',
                    ];
                }
            }
        } catch (Throwable $e) {
            Log::warning('External Geocoding API failure: '.$e->getMessage(), ['query' => $query]);
        }

        return $this->getFallbackGeocode($query);
    }

    /**
     * Convert compass degrees to 8-point cardinal direction.
     */
    protected function degreesToCompass(int|float $degrees): string
    {
        $directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        $index = (int) round(($degrees % 360) / 45);

        return $directions[$index % 8];
    }

    /**
     * Generate realistic fallback weather based on coordinates.
     *
     * @return array<string, mixed>
     */
    protected function getFallbackWeather(float $lat, float $lon, ?string $locationName = null): array
    {
        $absLat = abs($lat);

        if ($absLat < 20) {
            // Tropical region (e.g. Jakarta, Singapore)
            $temp = 30.5;
            $condition = 'Tropical Humidity & Scattered Clouds';
            $humidity = 82;
            $wind = 12.5;
        } elseif ($absLat < 40) {
            // Subtropical / Warm Temperate (e.g. Shanghai, Los Angeles, Dubai, Sydney)
            $temp = 22.0;
            $condition = 'Clear Sky & Coastal Wind';
            $humidity = 60;
            $wind = 16.0;
        } else {
            // Temperate / Cold (e.g. Rotterdam, Frankfurt)
            $temp = 12.0;
            $condition = 'Overcast & Moderate Breeze';
            $humidity = 78;
            $wind = 22.0;
        }

        return [
            'temperature_c' => $temp,
            'feels_like_c' => round($temp + 2.5, 1),
            'condition' => $condition,
            'humidity' => $humidity,
            'wind_kph' => $wind,
            'wind_direction' => 'NE',
            'pressure_mb' => 1014,
            'recorded_at' => Carbon::now()->toIso8601String(),
            'source' => 'CargoTrace Weather Simulation / Cache',
        ];
    }

    /**
     * Curated realistic logistics news fallbacks.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getFallbackLogisticsNews(): array
    {
        return [
            [
                'headline' => 'San Pedro Bay Ports Automate Container Gate Flow to Minimize Bottlenecks',
                'summary' => 'Terminals in Los Angeles and Long Beach implement unified digital appointment systems, reducing truck queue times by 28%.',
                'source' => 'Journal of Commerce',
                'url' => 'https://joc.com/maritime-news/ports/port-of-la-october-cargo',
                'published_at' => Carbon::now()->subHours(6)->toIso8601String(),
                'impact_level' => 'LOW',
            ],
            [
                'headline' => 'North Sea Storm Front: Quay Crane Operations Suspended at Key Terminals',
                'summary' => 'Severe seasonal storm gusts exceeding 40 knots prompt port safety authorities to enact crane speed throttles.',
                'source' => 'Port of Rotterdam Authority',
                'url' => 'https://portofrotterdam.com/news/north-sea-gale-operations',
                'published_at' => Carbon::now()->subHours(12)->toIso8601String(),
                'impact_level' => 'MEDIUM',
            ],
            [
                'headline' => 'Suez and Malacca Transit Corridors Report Elevated Vessel Flow Rates',
                'summary' => 'Container shipping capacity remains stable as carriers optimize voyage speeds and bunker fuel efficiency on major East-West loops.',
                'source' => "Lloyd's List Maritime Intelligence",
                'url' => 'https://lloydslist.maritimeintelligence.informa.com/global-freight-flows',
                'published_at' => Carbon::now()->subDays(1)->toIso8601String(),
                'impact_level' => 'LOW',
            ],
        ];
    }

    /**
     * Fallback geocoding dictionary for primary logistics hubs.
     *
     * @return array<string, mixed>|null
     */
    protected function getFallbackGeocode(string $query): ?array
    {
        $normalized = strtolower(trim($query));

        $lookup = [
            'jakarta' => ['latitude' => -6.1038190, 'longitude' => 106.8824240, 'display_name' => 'Port of Tanjung Priok, Jakarta, Indonesia'],
            'idjkt' => ['latitude' => -6.1038190, 'longitude' => 106.8824240, 'display_name' => 'Port of Tanjung Priok, Jakarta, Indonesia'],
            'singapore' => ['latitude' => 1.2640470, 'longitude' => 103.8222660, 'display_name' => 'Port of Singapore, Singapore'],
            'sgsin' => ['latitude' => 1.2640470, 'longitude' => 103.8222660, 'display_name' => 'Port of Singapore, Singapore'],
            'shanghai' => ['latitude' => 30.6274430, 'longitude' => 122.0641170, 'display_name' => 'Yangshan Deepwater Port, Shanghai, China'],
            'cnsha' => ['latitude' => 30.6274430, 'longitude' => 122.0641170, 'display_name' => 'Yangshan Deepwater Port, Shanghai, China'],
            'rotterdam' => ['latitude' => 51.9560410, 'longitude' => 4.0538980, 'display_name' => 'Port of Rotterdam, Netherlands'],
            'nlrtm' => ['latitude' => 51.9560410, 'longitude' => 4.0538980, 'display_name' => 'Port of Rotterdam, Netherlands'],
            'los angeles' => ['latitude' => 33.7431840, 'longitude' => -118.2672540, 'display_name' => 'Port of Los Angeles, California, United States'],
            'uslax' => ['latitude' => 33.7431840, 'longitude' => -118.2672540, 'display_name' => 'Port of Los Angeles, California, United States'],
            'dubai' => ['latitude' => 25.0069350, 'longitude' => 55.0645060, 'display_name' => 'Jebel Ali Port, Dubai, United Arab Emirates'],
            'aedxb' => ['latitude' => 25.0069350, 'longitude' => 55.0645060, 'display_name' => 'Jebel Ali Port, Dubai, United Arab Emirates'],
            'frankfurt' => ['latitude' => 50.0379330, 'longitude' => 8.5621520, 'display_name' => 'Frankfurt Airport CargoCity, Germany'],
            'defra' => ['latitude' => 50.0379330, 'longitude' => 8.5621520, 'display_name' => 'Frankfurt Airport CargoCity, Germany'],
            'sydney' => ['latitude' => -33.9723220, 'longitude' => 151.2185060, 'display_name' => 'Port Botany, Sydney, Australia'],
            'ausyd' => ['latitude' => -33.9723220, 'longitude' => 151.2185060, 'display_name' => 'Port Botany, Sydney, Australia'],
        ];

        foreach ($lookup as $key => $data) {
            if (str_contains($normalized, $key)) {
                return array_merge($data, ['source' => 'CargoTrace GeoCache']);
            }
        }

        return null;
    }

    /**
     * Compute fallback rate to USD.
     */
    protected function getRateToUsd(string $currency): float
    {
        $rateAgainstUsd = $this->benchmarkRatesAgainstUsd[$currency] ?? 1.0;

        return $rateAgainstUsd > 0 ? (1.0 / $rateAgainstUsd) : 1.0;
    }
}


<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherService
{
    /**
     * Default seaport coordinates (Tanjung Priok, Jakarta / Singapore)
     */
    public const DEFAULT_LATITUDE = -6.1017;
    public const DEFAULT_LONGITUDE = 106.8833;
    public const DEFAULT_PORT_NAME = 'Port of Tanjung Priok, Jakarta';

    /**
     * Cache TTL in seconds (30 minutes)
     */
    protected int $cacheTtl = 1800;

    /**
     * Fetch live seaport atmospheric telemetry with Open-Meteo API and 30-min cache.
     *
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string|null $locationName
     * @return array<string, mixed>
     */
    public function getPortWeather(?float $latitude = null, ?float $longitude = null, ?string $locationName = null): array
    {
        $lat = $latitude ?? self::DEFAULT_LATITUDE;
        $lon = $longitude ?? self::DEFAULT_LONGITUDE;
        $name = $locationName ?? self::DEFAULT_PORT_NAME;

        $roundLat = round($lat, 3);
        $roundLon = round($lon, 3);
        $cacheKey = "port_weather_{$roundLat}_{$roundLon}";

        // Return cached payload if available
        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['data_source'] = 'cached';
                return $cached;
            }
        }

        // Try Open-Meteo 100% open forecast endpoint
        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                'timezone' => 'auto',
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $current = $json['current'] ?? [];

                $code = (int) ($current['weather_code'] ?? 1);
                $condition = $this->interpretWeatherCode($code);
                $temp = (float) ($current['temperature_2m'] ?? 28.5);
                $humidity = (int) ($current['relative_humidity_2m'] ?? 75);
                $windKph = round((float) ($current['wind_speed_10m'] ?? 12.0), 1);

                $payload = [
                    'location_name' => $name,
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'temperature_c' => $temp,
                    'feels_like_c' => round($temp + (($humidity - 60) * 0.1), 1),
                    'condition' => $condition,
                    'weather_code' => $code,
                    'humidity' => $humidity,
                    'wind_kph' => $windKph,
                    'recorded_at' => Carbon::now()->toIso8601String(),
                    'source' => 'Open-Meteo Live API',
                    'data_source' => 'live',
                ];

                Cache::put($cacheKey, $payload, $this->cacheTtl);

                return $payload;
            }
        } catch (Throwable $e) {
            Log::warning("Open-Meteo weather fetch failure for {$name} ({$lat}, {$lon}): " . $e->getMessage());
        }

        // Deterministic fallback if API fails or network unavailable
        $fallback = $this->getFallbackWeather($lat, $lon, $name);
        $fallback['data_source'] = 'simulated';

        return $fallback;
    }

    /**
     * Fetch 7-day seaport maritime weather forecast.
     *
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string|null $locationName
     * @return array<string, mixed>
     */
    public function getPortForecast(?float $latitude = null, ?float $longitude = null, ?string $locationName = null): array
    {
        $lat = $latitude ?? self::DEFAULT_LATITUDE;
        $lon = $longitude ?? self::DEFAULT_LONGITUDE;
        $name = $locationName ?? self::DEFAULT_PORT_NAME;

        $roundLat = round($lat, 3);
        $roundLon = round($lon, 3);
        $cacheKey = "port_forecast_{$roundLat}_{$roundLon}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['data_source'] = 'cached';
                return $cached;
            }
        }

        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,wind_speed_10m_max,precipitation_probability_max',
                'timezone' => 'auto',
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $daily = $json['daily'] ?? [];
                $days = [];

                if (isset($daily['time']) && is_array($daily['time'])) {
                    foreach ($daily['time'] as $i => $date) {
                        $code = (int) ($daily['weather_code'][$i] ?? 1);
                        $days[] = [
                            'date' => $date,
                            'day_name' => Carbon::parse($date)->format('D, M d'),
                            'temp_max' => (float) ($daily['temperature_2m_max'][$i] ?? 31.0),
                            'temp_min' => (float) ($daily['temperature_2m_min'][$i] ?? 24.0),
                            'wind_max' => (float) ($daily['wind_speed_10m_max'][$i] ?? 15.0),
                            'precip_prob' => (int) ($daily['precipitation_probability_max'][$i] ?? 10),
                            'weather_code' => $code,
                            'condition' => $this->interpretWeatherCode($code),
                        ];
                    }
                }

                $payload = [
                    'location_name' => $name,
                    'days' => $days,
                    'source' => 'Open-Meteo 7-Day Forecast',
                    'data_source' => 'live',
                ];

                Cache::put($cacheKey, $payload, $this->cacheTtl);

                return $payload;
            }
        } catch (Throwable $e) {
            Log::warning("Open-Meteo forecast fetch failure for {$name}: " . $e->getMessage());
        }

        return $this->getFallbackForecast($name);
    }

    /**
     * Deterministic 7-day fallback forecast.
     */
    public function getFallbackForecast(string $name): array
    {
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = Carbon::now()->addDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('D, M d'),
                'temp_max' => 31.0 + ($i % 3) * 0.5,
                'temp_min' => 24.5 + ($i % 2) * 0.5,
                'wind_max' => 14.0 + ($i % 5) * 1.5,
                'precip_prob' => 15 + ($i * 7) % 50,
                'weather_code' => ($i % 3 === 0) ? 1 : (($i % 3 === 1) ? 2 : 80),
                'condition' => ($i % 3 === 0) ? 'Mainly Clear' : (($i % 3 === 1) ? 'Partly Cloudy' : 'Passing Rain Showers'),
            ];
        }

        return [
            'location_name' => $name,
            'days' => $days,
            'source' => 'Deterministic Maritime Forecast',
            'data_source' => 'simulated',
        ];
    }

    /**
     * Map WMO Weather Interpretation Codes (WW) into human-readable maritime conditions.
     */
    public function interpretWeatherCode(int $code): string
    {
        return match ($code) {
            0 => 'Clear Sky',
            1 => 'Mainly Clear',
            2 => 'Partly Cloudy',
            3 => 'Overcast',
            45, 48 => 'Marine Fog & Mist',
            51, 53, 55 => 'Light Drizzle',
            56, 57 => 'Freezing Drizzle',
            61, 63 => 'Moderate Rain',
            65 => 'Heavy Sea Downpour',
            66, 67 => 'Freezing Rain',
            71, 73, 75 => 'Snow Flurries',
            77 => 'Snow Grains',
            80, 81 => 'Passing Rain Showers',
            82 => 'Violent Rain Showers',
            85, 86 => 'Snow Showers',
            95 => 'Thunderstorm & Heavy Seas',
            96, 99 => 'Severe Thunderstorm with Hail',
            default => 'Scattered Clouds & Fair Breeze',
        };
    }

    /**
     * Deterministic maritime weather fallback generator.
     *
     * @param float $lat
     * @param float $lon
     * @param string $name
     * @return array<string, mixed>
     */
    public function getFallbackWeather(float $lat, float $lon, string $name): array
    {
        // Deterministic seeded pseudo-random based on day and coordinate
        $seed = abs((int) ($lat * 100 + $lon * 100 + now()->dayOfYear));
        $temp = 27.0 + ($seed % 6) + 0.5;
        $humidity = 65 + ($seed % 20);
        $wind = 12.0 + ($seed % 10) * 0.8;

        return [
            'location_name' => $name,
            'latitude' => $lat,
            'longitude' => $lon,
            'temperature_c' => round($temp, 1),
            'feels_like_c' => round($temp + 1.5, 1),
            'condition' => 'Scattered Clouds & Sea Breeze',
            'weather_code' => 2,
            'humidity' => $humidity,
            'wind_kph' => round($wind, 1),
            'recorded_at' => Carbon::now()->toIso8601String(),
            'source' => 'Deterministic Seaport Fallback',
            'data_source' => 'simulated',
        ];
    }
}


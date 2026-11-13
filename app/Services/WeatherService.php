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
     * Default seaport coordinates (Port of Tanjung Priok, Jakarta)
     */
    public const DEFAULT_LATITUDE = -6.1038;

    public const DEFAULT_LONGITUDE = 106.8824;

    public const DEFAULT_PORT_NAME = 'Port of Tanjung Priok';

    /**
     * Cache TTL in seconds (30 minutes)
     */
    protected int $cacheTtl = 1800;

    /**
     * Fetch live seaport atmospheric observations with Open-Meteo API and 30-min cache.
     *
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
                'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,visibility',
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
                $visibilityM = isset($current['visibility']) ? (float) $current['visibility'] : null;
                $visibilityKm = $visibilityM !== null ? round($visibilityM / 1000, 1) : null;

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
                    'visibility_km' => $visibilityKm,
                    'visibility_m' => $visibilityM,
                    'recorded_at' => Carbon::now()->toIso8601String(),
                    'source' => 'Open-Meteo Weather API',
                    'data_source' => 'live',
                ];

                Cache::put($cacheKey, $payload, $this->cacheTtl);

                return $payload;
            }
        } catch (Throwable $e) {
            Log::warning("Open-Meteo weather fetch failure for {$name} ({$lat}, {$lon}): ".$e->getMessage());
        }

        // Deterministic fallback if API fails or network unavailable
        $fallback = $this->getFallbackWeather($lat, $lon, $name);
        $fallback['data_source'] = 'simulated';

        return $fallback;
    }

    /**
     * Fetch real Open-Meteo Marine API observations (wave height, swell, current, period, direction).
     *
     * @return array<string, mixed>
     */
    public function getMarineWeather(?float $latitude = null, ?float $longitude = null): array
    {
        $lat = $latitude ?? self::DEFAULT_LATITUDE;
        $lon = $longitude ?? self::DEFAULT_LONGITUDE;

        $roundLat = round($lat, 3);
        $roundLon = round($lon, 3);
        $cacheKey = "port_marine_{$roundLat}_{$roundLon}";

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['data_source'] = 'cached';

                return $cached;
            }
        }

        try {
            $response = Http::timeout(5)->get('https://marine-api.open-meteo.com/v1/marine', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'wave_height,wave_direction,wave_period,swell_wave_height,ocean_current_velocity,ocean_current_direction',
                'daily' => 'wave_height_max',
                'timezone' => 'auto',
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $current = $json['current'] ?? [];

                // Check if marine data actually exists (wave_height is null for inland points)
                if (! isset($current['wave_height']) || $current['wave_height'] === null) {
                    $noData = [
                        'has_marine_data' => false,
                        'message' => 'No marine data for this location',
                        'data_source' => 'live',
                        'source' => 'Open-Meteo Marine API',
                    ];
                    Cache::put($cacheKey, $noData, $this->cacheTtl);

                    return $noData;
                }

                $daily = $json['daily'] ?? [];
                $dailyMax = [];
                if (! empty($daily['time']) && is_array($daily['time'])) {
                    foreach ($daily['time'] as $idx => $dayStr) {
                        $dailyMax[] = [
                            'date' => $dayStr,
                            'day_name' => Carbon::parse($dayStr)->format('D, M d'),
                            'wave_height_max' => isset($daily['wave_height_max'][$idx]) ? (float) $daily['wave_height_max'][$idx] : null,
                        ];
                    }
                }

                $payload = [
                    'has_marine_data' => true,
                    'wave_height' => (float) $current['wave_height'],
                    'wave_period' => isset($current['wave_period']) ? (float) $current['wave_period'] : null,
                    'wave_direction' => isset($current['wave_direction']) ? (int) $current['wave_direction'] : null,
                    'swell_wave_height' => isset($current['swell_wave_height']) ? (float) $current['swell_wave_height'] : null,
                    'ocean_current_velocity' => isset($current['ocean_current_velocity']) ? (float) $current['ocean_current_velocity'] : null,
                    'ocean_current_direction' => isset($current['ocean_current_direction']) ? (int) $current['ocean_current_direction'] : null,
                    'daily_max' => $dailyMax,
                    'source' => 'Open-Meteo Marine API',
                    'data_source' => 'live',
                    'recorded_at' => Carbon::now()->toIso8601String(),
                ];

                Cache::put($cacheKey, $payload, $this->cacheTtl);

                return $payload;
            }
        } catch (Throwable $e) {
            Log::warning("Open-Meteo Marine API fetch failure for ({$lat}, {$lon}): ".$e->getMessage());
        }

        // Return honest no marine data state when network/endpoint fails or is unavailable
        return [
            'has_marine_data' => false,
            'message' => 'No marine data for this location',
            'data_source' => 'simulated',
            'source' => 'Open-Meteo Marine API',
        ];
    }

    /**
     * Compute Beaufort scale force and description derived from wind speed.
     *
     * @return array<string, mixed>
     */
    public function getBeaufortScale(float $windKph): array
    {
        $thresholds = config('cargotrace.weather.beaufort_thresholds', []);

        foreach ($thresholds as $t) {
            if ($windKph <= $t['max_kph']) {
                return [
                    'force' => $t['force'],
                    'desc' => $t['desc'],
                    'color' => $t['color'],
                    'basis' => 'Derived from wind speed',
                ];
            }
        }

        return [
            'force' => 7,
            'desc' => 'Near Gale / Heavy Seas',
            'color' => 'rose',
            'basis' => 'Derived from wind speed',
        ];
    }

    /**
     * Compute rule-based Quay Crane advisory based on wind speed.
     *
     * @return array<string, mixed>
     */
    public function getCraneAdvisory(float $windKph): array
    {
        $caution = (float) config('cargotrace.weather.crane_thresholds.caution_kph', 28.0);
        $suspended = (float) config('cargotrace.weather.crane_thresholds.suspended_kph', 40.0);

        if ($windKph < $caution) {
            return [
                'status' => 'Full Clearance',
                'color' => 'emerald',
                'note' => 'Standard Ship-to-Shore (STS) container quay crane operations safe.',
                'basis' => 'Rule-based advisory',
            ];
        }

        if ($windKph < $suspended) {
            return [
                'status' => 'Cautionary Operations',
                'color' => 'amber',
                'note' => "Wind speed ({$windKph} km/h) approaches caution limit ({$caution} km/h). Speed throttled on high-tier container stacks.",
                'basis' => 'Rule-based advisory',
            ];
        }

        return [
            'status' => 'Quay Cranes Suspended',
            'color' => 'rose',
            'note' => "Severe gust threshold ({$suspended} km/h) exceeded at {$windKph} km/h. Quay crane boom raised for safety.",
            'basis' => 'Rule-based advisory',
        ];
    }

    /**
     * Fetch 7-day seaport maritime weather forecast.
     *
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
                    'source' => 'Open-Meteo Weather API',
                    'data_source' => 'live',
                ];

                Cache::put($cacheKey, $payload, $this->cacheTtl);

                return $payload;
            }
        } catch (Throwable $e) {
            Log::warning("Open-Meteo forecast fetch failure for {$name}: ".$e->getMessage());
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
            'source' => 'Open-Meteo Weather API (Deterministic fallback)',
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
     * @return array<string, mixed>
     */
    public function getFallbackWeather(float $lat, float $lon, string $name): array
    {
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
            'visibility_km' => null,
            'visibility_m' => null,
            'recorded_at' => Carbon::now()->toIso8601String(),
            'source' => 'Open-Meteo Weather API (Deterministic fallback)',
            'data_source' => 'simulated',
        ];
    }
}

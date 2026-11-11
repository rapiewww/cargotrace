<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeatherWebController extends Controller
{
    public function __construct(
        protected WeatherService $weatherService
    ) {}

    /**
     * Display the dedicated Maritime Seaport Weather Hub.
     */
    public function index(Request $request): View
    {
        // 1. Fetch available maritime seaports from database or curated global ports
        $dbPorts = Location::where('type', 'PORT')->orderBy('name')->get();

        $curatedPorts = [
            [
                'id' => 'SGSIN',
                'name' => 'Port of Singapore',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'unlocode' => 'SGSIN',
                'latitude' => 1.264,
                'longitude' => 103.840,
            ],
            [
                'id' => 'IDJKT',
                'name' => 'Port of Tanjung Priok',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'unlocode' => 'IDJKT',
                'latitude' => -6.1017,
                'longitude' => 106.8833,
            ],
            [
                'id' => 'CNSHA',
                'name' => 'Port of Shanghai',
                'city' => 'Shanghai',
                'country' => 'China',
                'unlocode' => 'CNSHA',
                'latitude' => 31.2304,
                'longitude' => 121.4737,
            ],
            [
                'id' => 'NLRTM',
                'name' => 'Port of Rotterdam',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'unlocode' => 'NLRTM',
                'latitude' => 51.9244,
                'longitude' => 4.4777,
            ],
            [
                'id' => 'USLAX',
                'name' => 'Port of Los Angeles',
                'city' => 'Los Angeles',
                'country' => 'United States',
                'unlocode' => 'USLAX',
                'latitude' => 33.7432,
                'longitude' => -118.2673,
            ],
            [
                'id' => 'AEDXB',
                'name' => 'Jebel Ali Port',
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'unlocode' => 'AEJEA',
                'latitude' => 24.9857,
                'longitude' => 55.0273,
            ],
            [
                'id' => 'DEHAM',
                'name' => 'Port of Hamburg',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'unlocode' => 'DEHAM',
                'latitude' => 53.5488,
                'longitude' => 9.9872,
            ],
        ];

        // Merge DB ports with curated list
        $portsList = collect($curatedPorts);
        foreach ($dbPorts as $loc) {
            if (! $portsList->contains('unlocode', $loc->unlocode)) {
                $portsList->push([
                    'id' => $loc->unlocode ?: (string) $loc->id,
                    'name' => $loc->name,
                    'city' => $loc->city,
                    'country' => $loc->country,
                    'unlocode' => $loc->unlocode ?: 'SEAPORT',
                    'latitude' => (float) $loc->latitude,
                    'longitude' => (float) $loc->longitude,
                ]);
            }
        }

        // 2. Resolve selected port
        $selectedUnlocode = $request->query('port', 'IDJKT');
        $selectedPort = $portsList->firstWhere('id', $selectedUnlocode)
            ?? $portsList->firstWhere('unlocode', $selectedUnlocode)
            ?? $portsList->first();

        // 3. Fetch current live telemetry and multi-day forecast
        $currentWeather = $this->weatherService->getPortWeather(
            (float) $selectedPort['latitude'],
            (float) $selectedPort['longitude'],
            $selectedPort['name']
        );

        $forecast = $this->weatherService->getPortForecast(
            (float) $selectedPort['latitude'],
            (float) $selectedPort['longitude'],
            $selectedPort['name']
        );

        // 4. Compute Maritime Specific Telemetry (Beaufort scale, swell, crane safety)
        $windKph = (float) ($currentWeather['wind_kph'] ?? 14.0);
        $beaufort = match (true) {
            $windKph < 6 => ['force' => 1, 'desc' => 'Light Air', 'color' => 'emerald'],
            $windKph < 20 => ['force' => 3, 'desc' => 'Gentle Breeze', 'color' => 'emerald'],
            $windKph < 29 => ['force' => 4, 'desc' => 'Moderate Breeze', 'color' => 'cyan'],
            $windKph < 39 => ['force' => 5, 'desc' => 'Fresh Breeze', 'color' => 'amber'],
            $windKph < 50 => ['force' => 6, 'desc' => 'Strong Breeze', 'color' => 'orange'],
            default => ['force' => 7, 'desc' => 'Near Gale / Heavy Seas', 'color' => 'rose'],
        };

        $craneStatus = match (true) {
            $windKph < 28 => ['status' => 'Full Clearance', 'color' => 'emerald', 'note' => 'Standard STS container crane operations safe.'],
            $windKph < 40 => ['status' => 'Cautionary Operations', 'color' => 'amber', 'note' => 'Speed throttled on high-tier container tier stacks.'],
            default => ['status' => 'Quay Cranes Suspended', 'color' => 'rose', 'note' => 'Severe gust limits exceeded. Quay crane boom raised for safety.'],
        };

        $swellEstimate = match (true) {
            $windKph < 15 => '0.4 m to 0.8 m (Calm Seaway)',
            $windKph < 30 => '1.0 m to 1.6 m (Moderate Swell)',
            $windKph < 45 => '1.8 m to 2.8 m (Rough Channel)',
            default => '3.0 m+ (Heavy Sea Swell)',
        };

        return view('weather.index', compact(
            'portsList',
            'selectedPort',
            'currentWeather',
            'forecast',
            'beaufort',
            'craneStatus',
            'swellEstimate'
        ));
    }
}


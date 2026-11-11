<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Shipment;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        // 1. Fetch available seaport locations from database (no hardcoded hubs)
        $allLocations = Location::where('type', 'PORT')->orderBy('name')->get();

        if ($allLocations->isEmpty()) {
            $allLocations = Location::orderBy('name')->get();
        }

        // 2. Identify ports on active routes for the current user
        $shipmentsQuery = Shipment::whereNotIn('status', ['DELIVERED', 'CANCELLED']);
        if ($user && $user->role === 'customer') {
            $shipmentsQuery->where('customer_id', $user->id);
        }
        $activeShipments = $shipmentsQuery->get(['origin_location_id', 'destination_location_id']);

        $activePortIds = $activeShipments->flatMap(fn ($s) => [
            $s->origin_location_id,
            $s->destination_location_id,
        ])->filter()->unique()->values()->all();

        // Split into active route ports and other global ports
        $activeRoutePorts = $allLocations->whereIn('id', $activePortIds)->values();
        $otherPorts = $allLocations->whereNotIn('id', $activePortIds)->values();

        // 3. Resolve selected port
        $selectedParam = $request->query('port');
        $selectedLocation = null;

        if ($selectedParam) {
            $selectedLocation = $allLocations->first(function ($loc) use ($selectedParam) {
                return (string) $loc->id === (string) $selectedParam
                    || strtoupper($loc->unlocode ?? '') === strtoupper($selectedParam);
            });
        }

        if (! $selectedLocation) {
            $selectedLocation = $activeRoutePorts->first() ?? $allLocations->first();
        }

        if ($selectedLocation) {
            $selectedPort = [
                'id' => (string) $selectedLocation->id,
                'name' => $selectedLocation->name,
                'city' => $selectedLocation->city ?? '',
                'country' => $selectedLocation->country ?? '',
                'unlocode' => $selectedLocation->unlocode ?: 'PORT',
                'latitude' => (float) $selectedLocation->latitude,
                'longitude' => (float) $selectedLocation->longitude,
                'is_active_route' => in_array($selectedLocation->id, $activePortIds),
            ];
        } else {
            $selectedPort = [
                'id' => '1',
                'name' => 'Port of Tanjung Priok',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'unlocode' => 'IDJKT',
                'latitude' => -6.1038,
                'longitude' => 106.8824,
                'is_active_route' => false,
            ];
        }

        // 4. Fetch current atmospheric weather and multi-day forecast
        $currentWeather = $this->weatherService->getPortWeather(
            $selectedPort['latitude'],
            $selectedPort['longitude'],
            $selectedPort['name']
        );

        $marineWeather = $this->weatherService->getMarineWeather(
            $selectedPort['latitude'],
            $selectedPort['longitude']
        );

        $forecast = $this->weatherService->getPortForecast(
            $selectedPort['latitude'],
            $selectedPort['longitude'],
            $selectedPort['name']
        );

        // 5. Compute Rule-based Advisory & Beaufort derived from wind
        $windKph = (float) ($currentWeather['wind_kph'] ?? 12.0);
        $beaufort = $this->weatherService->getBeaufortScale($windKph);
        $craneStatus = $this->weatherService->getCraneAdvisory($windKph);

        // 6. Build Compare Table across selected / active ports
        $compareIds = $request->query('compare');
        if (is_array($compareIds) && ! empty($compareIds)) {
            $compareLocations = $allLocations->whereIn('id', $compareIds)->take(6)->values();
        } else {
            // Default compare: active route ports + top ports (up to 4)
            $compareLocations = $activeRoutePorts->merge($otherPorts)->take(4)->values();
        }

        $compareData = [];
        foreach ($compareLocations as $loc) {
            $cWeather = $this->weatherService->getPortWeather((float) $loc->latitude, (float) $loc->longitude, $loc->name);
            $cMarine = $this->weatherService->getMarineWeather((float) $loc->latitude, (float) $loc->longitude);
            $cCrane = $this->weatherService->getCraneAdvisory((float) ($cWeather['wind_kph'] ?? 0));
            $cBeaufort = $this->weatherService->getBeaufortScale((float) ($cWeather['wind_kph'] ?? 0));

            $compareData[] = [
                'id' => $loc->id,
                'name' => $loc->name,
                'unlocode' => $loc->unlocode ?? 'PORT',
                'city' => $loc->city ?? '',
                'country' => $loc->country ?? '',
                'temp_c' => $cWeather['temperature_c'] ?? null,
                'condition' => $cWeather['condition'] ?? 'Clear',
                'wind_kph' => $cWeather['wind_kph'] ?? null,
                'beaufort_force' => $cBeaufort['force'] ?? 1,
                'has_marine_data' => $cMarine['has_marine_data'] ?? false,
                'wave_height' => $cMarine['wave_height'] ?? null,
                'crane_status' => $cCrane['status'] ?? 'Full Clearance',
                'crane_color' => $cCrane['color'] ?? 'emerald',
            ];
        }

        return view('weather.index', compact(
            'allLocations',
            'activeRoutePorts',
            'otherPorts',
            'selectedPort',
            'currentWeather',
            'marineWeather',
            'forecast',
            'beaufort',
            'craneStatus',
            'compareData'
        ));
    }
}

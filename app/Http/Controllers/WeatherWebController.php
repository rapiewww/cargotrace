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
     * Display the dedicated Location Weather Hub across seaports, airports, and depots.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Fetch every row of the locations table
        $allLocations = Location::orderBy('name')->get();

        // 2. Identify locations on active routes for the current user
        $shipmentsQuery = Shipment::whereNotIn('status', ['DELIVERED', 'CANCELLED']);
        if ($user && $user->role === 'customer') {
            $shipmentsQuery->where('customer_id', $user->id);
        }
        $activeShipments = $shipmentsQuery->get(['origin_location_id', 'destination_location_id']);

        $activePortIds = $activeShipments->flatMap(fn ($s) => [
            $s->origin_location_id,
            $s->destination_location_id,
        ])->filter()->unique()->values()->all();

        // Split into active route locations and categorized groups
        $activeRoutePorts = $allLocations->whereIn('id', $activePortIds)->values();
        $otherPorts = $allLocations->whereNotIn('id', $activePortIds)->values();
        $seaports = $otherPorts->filter(fn (Location $loc) => $loc->isSeaport())->values();
        $airports = $otherPorts->filter(fn (Location $loc) => strtoupper((string) $loc->type) === 'AIRPORT')->values();
        $depots = $otherPorts->filter(fn (Location $loc) => ! $loc->isSeaport() && strtoupper((string) $loc->type) !== 'AIRPORT')->values();

        // 3. Resolve selected location (?location= or ?port=)
        $selectedParam = $request->query('location') ?? $request->query('port');
        $selectedLocation = null;

        if ($selectedParam) {
            $selectedLocation = $allLocations->first(function ($loc) use ($selectedParam) {
                return (string) $loc->id === (string) $selectedParam
                    || strtoupper($loc->unlocode ?? '') === strtoupper($selectedParam)
                    || strtoupper($loc->iata_code ?? '') === strtoupper($selectedParam);
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
                'type' => strtoupper((string) $selectedLocation->type),
                'is_seaport' => $selectedLocation->isSeaport(),
                'facility_kind' => $selectedLocation->facility_kind_label,
                'facility_title' => $selectedLocation->facility_kind_title,
                'code' => $selectedLocation->display_code ?: 'LOC',
                'code_type' => $selectedLocation->code_type_label,
                'unlocode' => $selectedLocation->display_code ?: 'LOC',
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
                'type' => 'PORT',
                'is_seaport' => true,
                'facility_kind' => 'port',
                'facility_title' => 'Port',
                'code' => 'IDJKT',
                'code_type' => 'UN/LOCODE',
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

        $forecast = $this->weatherService->getPortForecast(
            $selectedPort['latitude'],
            $selectedPort['longitude'],
            $selectedPort['name']
        );

        // 5. Marine wave & swell and quay crane advisories apply to seaports only
        $marineWeather = null;
        $beaufort = null;
        $craneStatus = null;
        if ($selectedPort['is_seaport']) {
            $marineWeather = $this->weatherService->getMarineWeather(
                $selectedPort['latitude'],
                $selectedPort['longitude']
            );
            $windKph = (float) ($currentWeather['wind_kph'] ?? 12.0);
            $beaufort = $this->weatherService->getBeaufortScale($windKph);
            $craneStatus = $this->weatherService->getCraneAdvisory($windKph);
        }

        // 6. Build Compare Table across selected / active locations
        $compareIds = $request->query('compare');
        if (is_array($compareIds) && ! empty($compareIds)) {
            $compareLocations = $allLocations->whereIn('id', $compareIds)->take(6)->values();
        } else {
            $compareLocations = $activeRoutePorts->merge($otherPorts)->take(6)->values();
        }

        $compareData = [];
        foreach ($compareLocations as $loc) {
            $isSeaport = $loc->isSeaport();
            $cWeather = $this->weatherService->getPortWeather((float) $loc->latitude, (float) $loc->longitude, $loc->name);
            $cMarine = $isSeaport ? $this->weatherService->getMarineWeather((float) $loc->latitude, (float) $loc->longitude) : null;
            $cCrane = $isSeaport ? $this->weatherService->getCraneAdvisory((float) ($cWeather['wind_kph'] ?? 0)) : null;
            $cBeaufort = $isSeaport ? $this->weatherService->getBeaufortScale((float) ($cWeather['wind_kph'] ?? 0)) : null;

            $compareData[] = [
                'id' => $loc->id,
                'name' => $loc->name,
                'type' => strtoupper((string) $loc->type),
                'is_seaport' => $isSeaport,
                'facility_kind' => $loc->facility_kind_label,
                'facility_title' => $loc->facility_kind_title,
                'code' => $loc->display_code ?: 'LOC',
                'code_type' => $loc->code_type_label,
                'unlocode' => $loc->display_code ?: 'LOC',
                'city' => $loc->city ?? '',
                'country' => $loc->country ?? '',
                'temp_c' => $cWeather['temperature_c'] ?? null,
                'condition' => $cWeather['condition'] ?? 'Clear',
                'wind_kph' => $cWeather['wind_kph'] ?? null,
                'rain_mm' => $cWeather['rain_mm'] ?? 0.0,
                'visibility_km' => $cWeather['visibility_km'] ?? null,
                'beaufort_force' => $cBeaufort['force'] ?? null,
                'has_marine_data' => $isSeaport && ($cMarine['has_marine_data'] ?? false),
                'wave_height' => $isSeaport ? ($cMarine['wave_height'] ?? null) : null,
                'crane_status' => $cCrane['status'] ?? null,
                'crane_color' => $cCrane['color'] ?? null,
            ];
        }

        return view('weather.index', compact(
            'allLocations',
            'activeRoutePorts',
            'otherPorts',
            'seaports',
            'airports',
            'depots',
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

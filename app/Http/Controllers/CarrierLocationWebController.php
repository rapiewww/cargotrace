<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Services\ExternalIntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarrierLocationWebController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService
    ) {}

    public function carriers(): View
    {
        $carriers = Carrier::withCount('shipments')->orderBy('name')->get();

        return view('carriers.index', compact('carriers'));
    }

    public function storeCarrier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:carriers,code',
            'name' => 'required|string|max:255',
            'mode' => 'required|in:SEA,AIR,LAND,MULTIMODAL',
            'headquarters_country' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'website' => 'nullable|url',
        ]);

        $validated['is_active'] = true;
        Carrier::create($validated);

        return back()->with('success', 'Carrier created successfully.');
    }

    public function locations(): View
    {
        $locations = Location::withCount(['originShipments', 'destinationShipments'])->orderBy('name')->get();

        return view('locations.index', compact('locations'));
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'unlocode' => 'required|string|max:10|unique:locations,unlocode',
            'name' => 'required|string|max:255',
            'city' => 'required|string',
            'country' => 'required|string',
            'country_code' => 'required|string|size:2',
            'currency_code' => 'required|string|size:3',
            'type' => 'required|in:PORT,AIRPORT,HUB,WAREHOUSE',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone' => 'nullable|string',
        ]);

        if (empty($validated['latitude']) || empty($validated['longitude'])) {
            $geo = $this->externalService->geocodeLocation("{$validated['name']}, {$validated['city']}");
            $validated['latitude'] = $geo['latitude'] ?? 0;
            $validated['longitude'] = $geo['longitude'] ?? 0;
        }

        $validated['timezone'] ??= 'UTC';
        $validated['weather_snapshot'] = $this->externalService->getWeatherByCoordinates(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            $validated['name']
        );

        $news = $this->externalService->getLogisticsNews("{$validated['city']} port logistics");
        $validated['news_snapshot'] = $news[0] ?? null;

        Location::create($validated);

        return back()->with('success', 'Location created successfully with coordinates and telemetry.');
    }
}

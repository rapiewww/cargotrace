<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Services\AuditService;
use App\Services\ExternalIntegrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CarrierLocationWebController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService,
        protected AuditService $auditService
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
            'mode' => 'required|in:SEA,AIR,ROAD,LAND,MULTIMODAL',
            'headquarters_country' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'website' => 'nullable|url',
        ]);

        $validated['is_active'] = true;
        $carrier = Carrier::create($validated);

        $this->auditService->log('CARRIER_CREATED', $carrier, null, $validated);

        return back()->with('success', 'Carrier created successfully.');
    }

    public function updateCarrier(Request $request, Carrier $carrier): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('carriers')->ignore($carrier->id)],
            'name' => 'required|string|max:255',
            'mode' => 'required|in:SEA,AIR,ROAD,LAND,MULTIMODAL',
            'headquarters_country' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'website' => 'nullable|url',
            'is_active' => 'nullable|boolean',
        ]);

        $oldValues = $carrier->toArray();
        $validated['is_active'] = $request->boolean('is_active', true);
        $carrier->update($validated);

        $this->auditService->log('CARRIER_UPDATED', $carrier, $oldValues, $carrier->toArray());

        return back()->with('success', "Carrier {$carrier->name} updated successfully.");
    }

    public function destroyCarrier(Carrier $carrier): RedirectResponse
    {
        if ($carrier->shipments()->exists()) {
            return back()->withErrors(['carrier' => "Carrier {$carrier->name} cannot be deleted because it is assigned to existing shipments."]);
        }

        $oldValues = $carrier->toArray();
        $name = $carrier->name;
        $carrier->delete();

        $this->auditService->log('CARRIER_DELETED', $carrier, $oldValues, null);

        return back()->with('success', "Carrier {$name} deleted successfully.");
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
            'type' => 'required|in:SEAPORT,AIRPORT,DEPOT,BORDER,PORT,HUB,WAREHOUSE',
            'iata_code' => 'nullable|string|max:10',
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

        $location = Location::create($validated);

        $this->auditService->log('LOCATION_CREATED', $location, null, $validated);

        return back()->with('success', 'Location created successfully with coordinates and weather observations.');
    }

    public function updateLocation(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'unlocode' => ['required', 'string', 'max:10', Rule::unique('locations')->ignore($location->id)],
            'name' => 'required|string|max:255',
            'city' => 'required|string',
            'country' => 'required|string',
            'country_code' => 'required|string|size:2',
            'currency_code' => 'required|string|size:3',
            'type' => 'required|in:SEAPORT,AIRPORT,DEPOT,BORDER,PORT,HUB,WAREHOUSE',
            'iata_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone' => 'nullable|string',
        ]);

        $oldValues = $location->toArray();
        $validated['timezone'] ??= 'UTC';
        $location->update($validated);

        $this->auditService->log('LOCATION_UPDATED', $location, $oldValues, $location->toArray());

        return back()->with('success', "Location {$location->name} updated successfully.");
    }

    public function destroyLocation(Location $location): RedirectResponse
    {
        if ($location->originShipments()->exists() || $location->destinationShipments()->exists() || $location->currentShipments()->exists()) {
            return back()->withErrors(['location' => "Location {$location->name} ({$location->unlocode}) cannot be deleted because it is referenced in active shipment routes."]);
        }

        $oldValues = $location->toArray();
        $name = $location->name;
        $location->delete();

        $this->auditService->log('LOCATION_DELETED', $location, $oldValues, null);

        return back()->with('success', "Location {$name} deleted successfully.");
    }
}

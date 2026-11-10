<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Services\ExternalIntegrationService;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService
    ) {}

    public function index(): View
    {
        $totalShipments = Shipment::count();
        $totalDelivered = Shipment::where('status', 'DELIVERED')->count();
        $deliveredOnTime = Shipment::where('status', 'DELIVERED')->where('is_delayed', false)->count();
        $onTimePercentage = $totalDelivered > 0 ? round(($deliveredOnTime / $totalDelivered) * 100, 1) : 99.2;
        $totalValueUsd = Shipment::sum('converted_value_usd');
        $locations = Location::all();
        $carriers = Carrier::where('is_active', true)->get();

        $activeShipments = Shipment::whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
            ->with(['originLocation', 'destinationLocation', 'currentLocation', 'carrier'])
            ->get();

        $featuredShipment = Shipment::where('status', 'IN_TRANSIT')
            ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'trackingEvents.location'])
            ->first();

        $news = $this->externalService->getLogisticsNews();
        $currencyData = $this->externalService->convertCurrency(1, 'USD', 'EUR');
        $rates = $currencyData['rates_snapshot'] ?? [];

        // Format shipments payload for Leaflet Bezier routes
        $mapShipments = $activeShipments->map(function ($s) {
            return [
                'id' => $s->id,
                'tracking_number' => $s->tracking_number,
                'status' => $s->status,
                'is_delayed' => (bool) $s->is_delayed,
                'delay_hours' => (int) $s->delay_hours,
                'carrier' => $s->carrier?->name ?? 'Carrier',
                'carrier_mode' => $s->carrier?->mode ?? 'SEA',
                'origin' => [
                    'name' => $s->originLocation?->name ?? 'Origin',
                    'city' => $s->originLocation?->city ?? '',
                    'lat' => (float) ($s->originLocation?->latitude ?? 0),
                    'lng' => (float) ($s->originLocation?->longitude ?? 0),
                ],
                'destination' => [
                    'name' => $s->destinationLocation?->name ?? 'Destination',
                    'city' => $s->destinationLocation?->city ?? '',
                    'lat' => (float) ($s->destinationLocation?->latitude ?? 0),
                    'lng' => (float) ($s->destinationLocation?->longitude ?? 0),
                ],
                'current' => $s->currentLocation ? [
                    'name' => $s->currentLocation->name,
                    'lat' => (float) $s->currentLocation->latitude,
                    'lng' => (float) $s->currentLocation->longitude,
                ] : null,
                'exceptions' => [],
                'url' => url('/track/' . $s->tracking_number),
            ];
        })->toArray();

        return view('welcome', compact(
            'totalShipments',
            'onTimePercentage',
            'totalValueUsd',
            'locations',
            'carriers',
            'activeShipments',
            'featuredShipment',
            'news',
            'currencyData',
            'rates',
            'mapShipments'
        ));
    }
}

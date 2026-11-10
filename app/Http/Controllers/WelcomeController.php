<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Services\ExternalIntegrationService;

class WelcomeController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService
    ) {}

    public function index()
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

        return view('welcome', compact(
            'totalShipments',
            'onTimePercentage',
            'totalValueUsd',
            'locations',
            'carriers',
            'activeShipments',
            'featuredShipment',
            'news',
            'rates'
        ));
    }
}

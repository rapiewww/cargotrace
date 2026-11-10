<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExternalIntegrationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        // Base query scope for customers
        $shipmentQuery = Shipment::query();
        if ($user->role === 'customer') {
            $shipmentQuery->where('customer_id', $user->id);
        }

        // Stats
        $totalShipments = (clone $shipmentQuery)->count();
        $inTransit = (clone $shipmentQuery)->where('status', 'IN_TRANSIT')->count();
        $delivered = (clone $shipmentQuery)->where('status', 'DELIVERED')->count();
        $delayed = (clone $shipmentQuery)->where('is_delayed', true)->whereNotIn('status', ['DELIVERED'])->count();
        $totalValueUsd = (clone $shipmentQuery)->sum('converted_value_usd');

        $totalDelivered = (clone $shipmentQuery)->where('status', 'DELIVERED')->count();
        $deliveredOnTime = (clone $shipmentQuery)->where('status', 'DELIVERED')->where('is_delayed', false)->count();
        $onTimeRate = $totalDelivered > 0 ? round(($deliveredOnTime / $totalDelivered) * 100, 1) : 100.0;

        $openExceptions = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])->count();

        // Shipments by status for chart
        $statusCounts = (clone $shipmentQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Shipments by carrier for chart
        $carrierCounts = (clone $shipmentQuery)
            ->join('carriers', 'shipments.carrier_id', '=', 'carriers.id')
            ->select('carriers.code', DB::raw('count(*) as count'))
            ->groupBy('carriers.code')
            ->pluck('count', 'carriers.code')
            ->toArray();

        // Featured shipment
        $featuredShipment = (clone $shipmentQuery)
            ->where('status', 'IN_TRANSIT')
            ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'trackingEvents.location', 'exceptions'])
            ->first();

        // Active shipments for map
        $activeShipments = (clone $shipmentQuery)
            ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
            ->with(['originLocation', 'destinationLocation', 'currentLocation', 'carrier'])
            ->get();

        // Active routes for schedule panel
        $activeRoutes = (clone $shipmentQuery)
            ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP'])
            ->with(['originLocation', 'destinationLocation', 'carrier'])
            ->latest()
            ->take(5)
            ->get();

        $locations = Location::all();
        $carriers = Carrier::where('is_active', true)->get();

        // User notifications
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
        $unreadCount = Notification::where('user_id', $user->id)->whereNull('read_at')->count();

        // External data
        $news = $this->externalService->getLogisticsNews();
        $currencyData = $this->externalService->convertCurrency(1, 'USD', 'EUR');
        $rates = $currencyData['rates_snapshot'] ?? [];

        // Weather for key ports
        $weatherPorts = [];
        foreach ($locations->take(4) as $loc) {
            $weatherPorts[] = [
                'name' => $loc->name,
                'city' => $loc->city,
                'weather' => $loc->weather_snapshot ?? $this->externalService->getWeatherByCoordinates(
                    (float) $loc->latitude,
                    (float) $loc->longitude,
                    $loc->name
                ),
            ];
        }

        return view('dashboard', compact(
            'user',
            'totalShipments',
            'inTransit',
            'delivered',
            'delayed',
            'totalValueUsd',
            'onTimeRate',
            'openExceptions',
            'statusCounts',
            'carrierCounts',
            'featuredShipment',
            'activeShipments',
            'activeRoutes',
            'locations',
            'carriers',
            'notifications',
            'unreadCount',
            'news',
            'rates',
            'weatherPorts'
        ));
    }
}

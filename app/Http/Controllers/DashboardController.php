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

        // Scoped open exceptions count and distribution
        $openExceptions = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->when($user->role === 'customer', function ($q) use ($user) {
                $q->whereHas('shipment', fn ($sq) => $sq->where('customer_id', $user->id));
            })->count();

        $exceptionCounts = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->when($user->role === 'customer', function ($q) use ($user) {
                $q->whereHas('shipment', fn ($sq) => $sq->where('customer_id', $user->id));
            })
            ->select('exception_code', DB::raw('count(*) as count'))
            ->groupBy('exception_code')
            ->pluck('count', 'exception_code')
            ->toArray();

        // Shipments by status for doughnut chart
        $statusCounts = (clone $shipmentQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Carrier distribution for horizontal bar chart
        $carrierStats = Carrier::where('is_active', true)
            ->withCount(['shipments' => function ($q) use ($user) {
                if ($user->role === 'customer') {
                    $q->where('customer_id', $user->id);
                }
            }])
            ->get()
            ->filter(fn ($c) => $c->shipments_count > 0)
            ->values()
            ->map(fn ($c) => [
                'name' => $c->name,
                'code' => $c->code,
                'count' => $c->shipments_count,
            ])
            ->toArray();

        // 8-week trend data (On-Time vs Delayed)
        $trendData = [];
        for ($i = 7; $i >= 0; $i--) {
            $startOfWeek = now()->subWeeks($i)->startOfWeek();
            $endOfWeek = now()->subWeeks($i)->endOfWeek();
            $weekLabel = $startOfWeek->format('d M');

            $wQuery = (clone $shipmentQuery)->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            $onTimeCount = (clone $wQuery)->where('is_delayed', false)->count();
            $delayedCount = (clone $wQuery)->where('is_delayed', true)->count();

            $trendData[] = [
                'week' => $weekLabel,
                'on_time' => $onTimeCount,
                'delayed' => $delayedCount,
            ];
        }

        // Featured shipment (prefer active in-transit with exceptions or delay)
        $featuredShipment = (clone $shipmentQuery)
            ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP'])
            ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'trackingEvents.location', 'exceptions'])
            ->orderByDesc('is_delayed')
            ->latest()
            ->first();

        if (! $featuredShipment) {
            $featuredShipment = (clone $shipmentQuery)
                ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'trackingEvents.location', 'exceptions'])
                ->latest()
                ->first();
        }

        // Active shipments for world map
        $activeShipments = (clone $shipmentQuery)
            ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
            ->with(['originLocation', 'destinationLocation', 'currentLocation', 'carrier', 'exceptions'])
            ->get();

        // Active routes for schedule panel
        $activeRoutes = (clone $shipmentQuery)
            ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP'])
            ->with(['originLocation', 'destinationLocation', 'carrier'])
            ->latest()
            ->take(5)
            ->get();

        // Real hero floating chips data (from actual DB records)
        $latestDelayed = (clone $shipmentQuery)
            ->where('is_delayed', true)
            ->with('currentLocation')
            ->latest('updated_at')
            ->first();

        $latestException = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->when($user->role === 'customer', function ($q) use ($user) {
                $q->whereHas('shipment', fn ($sq) => $sq->where('customer_id', $user->id));
            })
            ->with('shipment')
            ->latest()
            ->first();

        $locations = Location::all();
        $carriers = Carrier::where('is_active', true)->get();

        // User notifications
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
        $unreadCount = Notification::where('user_id', $user->id)->whereNull('read_at')->count();

        // External data (cached with data_source indicator)
        $news = $this->externalService->getLogisticsNews();
        $currencyData = $this->externalService->convertCurrency(1, 'USD', 'EUR');
        $rates = $currencyData['rates_snapshot'] ?? [];

        // Weather for key ports
        $weatherPorts = [];
        foreach ($locations->take(4) as $loc) {
            $weather = $loc->weather_snapshot ?? $this->externalService->getWeatherByCoordinates(
                (float) $loc->latitude,
                (float) $loc->longitude,
                $loc->name
            );

            $weatherPorts[] = [
                'name' => $loc->name,
                'city' => $loc->city,
                'unlocode' => $loc->unlocode,
                'weather' => $weather,
                'data_source' => $weather['data_source'] ?? 'simulated',
            ];
        }

        // Map payload formatting for client-side Leaflet
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
                'exceptions' => $s->exceptions->map(fn ($e) => [
                    'code' => $e->exception_code,
                    'severity' => $e->severity,
                    'description' => $e->description,
                ])->toArray(),
                'url' => route('shipments.show', $s),
            ];
        })->toArray();

        return view('dashboard', compact(
            'user',
            'totalShipments',
            'inTransit',
            'delivered',
            'delayed',
            'totalValueUsd',
            'onTimeRate',
            'openExceptions',
            'exceptionCounts',
            'statusCounts',
            'carrierStats',
            'trendData',
            'featuredShipment',
            'activeShipments',
            'activeRoutes',
            'latestDelayed',
            'latestException',
            'locations',
            'carriers',
            'notifications',
            'unreadCount',
            'news',
            'currencyData',
            'rates',
            'weatherPorts',
            'mapShipments'
        ));
    }
}

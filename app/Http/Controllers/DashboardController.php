<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\CurrencyService;
use App\Services\ExternalIntegrationService;
use App\Services\FreeTimeService;
use App\Services\MaritimeNewsService;
use App\Services\WeatherService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService,
        protected WeatherService $weatherService,
        protected CurrencyService $currencyService,
        protected MaritimeNewsService $newsService
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

        // Active shipments count and real exception rate (open exceptions / active shipments)
        $activeShipmentsCount = (clone $shipmentQuery)->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])->count();
        $exceptionRate = $activeShipmentsCount > 0 ? round(($openExceptions / $activeShipmentsCount) * 100, 1) : 0.0;
        $totalVolumeCbm = (clone $shipmentQuery)->sum('total_volume_cbm');

        // Dynamic delay thresholds from database settings
        $delayLowHours = (float) (Setting::get('delay_low_hours') ?? config('cargotrace.delay.low_hours', 6));
        $delayMediumHours = (float) (Setting::get('delay_medium_hours') ?? config('cargotrace.delay.medium_hours', 24));

        // Real 14-day creation series for Sparkline 1
        $sparklineShipments = [];
        for ($d = 13; $d >= 0; $d--) {
            $date = now()->subDays($d)->startOfDay();
            $nextDate = (clone $date)->endOfDay();
            $cnt = (clone $shipmentQuery)->whereBetween('created_at', [$date, $nextDate])->count();
            $sparklineShipments[] = $cnt;
        }
        $spark1NonZero = array_filter($sparklineShipments, fn ($v) => $v > 0);
        $spark1Flat = (count($sparklineShipments) < 3) || (min($sparklineShipments) === max($sparklineShipments)) || (count($spark1NonZero) < 2);

        $maxSpark = max(max($sparklineShipments), 1);
        $sparkPoints = [];
        foreach ($sparklineShipments as $idx => $val) {
            $x = round(($idx / 13) * 200, 1);
            $y = round(34 - (($val / $maxSpark) * 26), 1);
            $sparkPoints[] = "{$x},{$y}";
        }
        $sparkline1Path = 'M'.implode(' L', $sparkPoints);
        $sparkline1Area = $sparkline1Path.' L200,40 L0,40 Z';

        // Real 8-week on-time performance rate for Sparkline 2
        $onTimeRates = array_map(function ($tw) {
            $totalWk = $tw['on_time'] + $tw['delayed'];

            return $totalWk > 0 ? round(($tw['on_time'] / $totalWk) * 100, 1) : null;
        }, $trendData);
        $validRates = array_values(array_filter($onTimeRates, fn ($v) => $v !== null));
        $spark2Flat = (count($validRates) < 3) || (min($validRates) === max($validRates));

        $spark2Points = [];
        foreach ($trendData as $idx => $tw) {
            $totalWk = $tw['on_time'] + $tw['delayed'];
            $pct = $totalWk > 0 ? ($tw['on_time'] / $totalWk) * 100 : 100.0;
            $x = round(($idx / 7) * 200, 1);
            $y = round(34 - (($pct / 100) * 26), 1);
            $spark2Points[] = "{$x},{$y}";
        }
        $sparkline2Path = 'M'.implode(' L', $spark2Points);
        $sparkline2Area = $sparkline2Path.' L200,40 L0,40 Z';

        // Comparison with previous 30-day period for on-time rate
        $currPeriodDelivered = (clone $shipmentQuery)->where('status', 'DELIVERED')->where('updated_at', '>=', now()->subDays(30))->count();
        $currPeriodOnTime = (clone $shipmentQuery)->where('status', 'DELIVERED')->where('is_delayed', false)->where('updated_at', '>=', now()->subDays(30))->count();
        $currPeriodRate = $currPeriodDelivered > 0 ? round(($currPeriodOnTime / $currPeriodDelivered) * 100, 1) : null;

        $prevPeriodDelivered = (clone $shipmentQuery)->where('status', 'DELIVERED')->whereBetween('updated_at', [now()->subDays(60), now()->subDays(30)])->count();
        $prevPeriodOnTime = (clone $shipmentQuery)->where('status', 'DELIVERED')->where('is_delayed', false)->whereBetween('updated_at', [now()->subDays(60), now()->subDays(30)])->count();
        $prevPeriodRate = $prevPeriodDelivered > 0 ? round(($prevPeriodOnTime / $prevPeriodDelivered) * 100, 1) : null;

        $onTimeComparison = null;
        if ($currPeriodRate !== null && $prevPeriodRate !== null) {
            $diff = round($currPeriodRate - $prevPeriodRate, 1);
            $sign = $diff >= 0 ? '+' : '';
            $onTimeComparison = "{$sign}{$diff}% vs prev 30d";
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

        // Free time evaluation for attention list
        $freeTimeService = app(FreeTimeService::class);
        $freeDaysAllowed = (int) (Setting::get('free_days_after_arrival') ?? config('cargotrace.free_time.days', 5));

        $arrivedShipments = (clone $shipmentQuery)
            ->where(function ($q) {
                $q->whereNotNull('actual_arrival_at')
                    ->orWhere('status', 'DELIVERED');
            })
            ->with(['originLocation', 'destinationLocation', 'carrier', 'items', 'trackingEvents'])
            ->get();

        $overFreeTimeShipments = $arrivedShipments->filter(function ($s) use ($freeTimeService, $freeDaysAllowed) {
            $eval = $freeTimeService->calculate($s, null, $freeDaysAllowed);
            if ($eval['is_over_limit']) {
                $s->free_time_eval = $eval;

                return true;
            }

            return false;
        })->values();

        // User notifications
        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
        $unreadCount = Notification::where('user_id', $user->id)->whereNull('read_at')->count();

        // 1. Live Maritime News Feed (RSS with 1-hr Cache & Shipment Relevance)
        $rawNews = $this->newsService->getMaritimeNews(6);
        $news = $this->newsService->tagShipmentRelevance($rawNews, $activeShipments);

        // 2. Live Maritime FX Currency Conversion (Open ER with 1-hr Cache)
        try {
            $currencyData = $this->currencyService->convert(1000, 'USD', 'IDR');
        } catch (\Throwable $e) {
            $currencyData = [
                'amount' => 1000,
                'from' => 'USD',
                'to' => 'IDR',
                'rate' => 0.0,
                'converted_amount' => 0.0,
                'formatted_result' => 'Service Unavailable',
                'rates_snapshot' => [],
                'source' => 'ExchangeRate-API',
                'data_source' => 'simulated',
                'last_updated' => null,
            ];
        }
        $rates = $currencyData['rates_snapshot'] ?? [];

        // 3. Live Seaport Atmospheric Weather (Open-Meteo with 30-min Cache)
        $weatherPorts = [];
        foreach ($locations->take(4) as $loc) {
            $weather = $this->weatherService->getPortWeather(
                (float) $loc->latitude,
                (float) $loc->longitude,
                $loc->name
            );

            $weatherPorts[] = [
                'name' => $loc->name,
                'city' => $loc->city,
                'unlocode' => $loc->unlocode,
                'weather' => $weather,
                'data_source' => $weather['data_source'] ?? 'live',
            ];
        }

        // Dedicated selected port for Seaport Live Weather Widget
        $selectedPort = $weatherPorts[0] ?? [
            'name' => 'Port of Tanjung Priok',
            'city' => 'Jakarta',
            'unlocode' => 'IDJKT',
            'weather' => $this->weatherService->getPortWeather(WeatherService::DEFAULT_LATITUDE, WeatherService::DEFAULT_LONGITUDE, WeatherService::DEFAULT_PORT_NAME),
            'data_source' => 'live',
        ];

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
            'selectedPort',
            'mapShipments',
            'activeShipmentsCount',
            'exceptionRate',
            'totalVolumeCbm',
            'sparkline1Path',
            'sparkline1Area',
            'sparkline2Path',
            'sparkline2Area',
            'delayLowHours',
            'delayMediumHours',
            'spark1Flat',
            'spark2Flat',
            'onTimeComparison',
            'overFreeTimeShipments',
            'freeTimeService'
        ));
    }
}

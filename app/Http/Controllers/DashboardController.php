<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\CurrencyService;
use App\Services\ExternalIntegrationService;
use App\Services\FreeTimeService;
use App\Services\MaritimeNewsService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService,
        protected WeatherService $weatherService,
        protected CurrencyService $currencyService,
        protected MaritimeNewsService $newsService,
        protected AnalyticsService $analyticsService
    ) {}

    public function index(): View
    {
        $user = Auth::user();

        // Base query scope for customers
        $shipmentQuery = Shipment::query();
        if ($user->role === 'customer') {
            $shipmentQuery->where('customer_id', $user->id);
        }

        // Core role-scoped KPIs and chart distributions from AnalyticsService
        $kpis = $this->analyticsService->getDashboardKpis($user);
        $statusDistribution = $this->analyticsService->getShipmentsByStatus($user);
        $carrierStats = $this->analyticsService->getShipmentsByCarrier($user, 6);

        $totalShipments = $kpis['total_shipments'];
        $inTransit = $kpis['in_transit'];
        $delivered = $kpis['delivered'];
        $delayed = $kpis['delayed'];
        $activeShipmentsCount = $kpis['active_shipments'];
        $totalValueUsd = $kpis['total_value_usd'];
        $totalVolumeCbm = $kpis['total_volume_cbm'];
        $onTimeRate = $kpis['on_time_rate'];
        $openExceptions = $kpis['open_exceptions'];
        $delayRate = $kpis['delay_rate'];
        $exceptionRate = $kpis['exception_rate'];

        $statusCounts = $statusDistribution['counts'];
        $statusBreakdown = $statusDistribution['rows'];

        $exceptionCounts = ShipmentException::whereIn('status', ['OPEN', 'INVESTIGATING'])
            ->when($user->role === 'customer', function ($q) use ($user) {
                $q->whereHas('shipment', fn ($sq) => $sq->where('customer_id', $user->id));
            })
            ->select('exception_code', DB::raw('count(*) as count'))
            ->groupBy('exception_code')
            ->pluck('count', 'exception_code')
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
            $onTimeComparison = __(':sign:diff% vs prev 30d', ['sign' => $sign, 'diff' => $diff]);
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
            ->with(['originLocation', 'destinationLocation', 'currentLocation', 'carrier', 'exceptions', 'items'])
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
            ->where('status', '!=', 'DELIVERED')
            ->whereNotNull('actual_arrival_at')
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

        // 3. Live Location Atmospheric Weather (Open-Meteo with 30-min Cache)
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
                'type' => strtoupper((string) $loc->type),
                'facility_kind' => $loc->facility_kind_label,
                'facility_title' => $loc->facility_kind_title,
                'code' => $loc->display_code,
                'code_type' => $loc->code_type_label,
                'unlocode' => $loc->display_code,
                'weather' => $weather,
                'data_source' => $weather['data_source'] ?? 'live',
            ];
        }

        // Dedicated selected location for Dashboard Weather Widget
        $selectedPort = $weatherPorts[0] ?? [
            'name' => 'Port of Tanjung Priok',
            'city' => 'Jakarta',
            'type' => 'PORT',
            'facility_kind' => 'port',
            'facility_title' => 'Port',
            'code' => 'IDJKT',
            'code_type' => 'UN/LOCODE',
            'unlocode' => 'IDJKT',
            'weather' => $this->weatherService->getPortWeather(WeatherService::DEFAULT_LATITUDE, WeatherService::DEFAULT_LONGITUDE, WeatherService::DEFAULT_PORT_NAME),
            'data_source' => 'live',
        ];

        // Active shipments per mode for dashboard cards / segmented bar
        $activeModesCount = [
            'SEA' => (clone $shipmentQuery)->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])->where('transport_mode', 'SEA')->count(),
            'AIR' => (clone $shipmentQuery)->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])->where('transport_mode', 'AIR')->count(),
            'ROAD' => (clone $shipmentQuery)->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])->where('transport_mode', 'ROAD')->count(),
        ];

        $totalModesCount = [
            'SEA' => (clone $shipmentQuery)->where('transport_mode', 'SEA')->count(),
            'AIR' => (clone $shipmentQuery)->where('transport_mode', 'AIR')->count(),
            'ROAD' => (clone $shipmentQuery)->where('transport_mode', 'ROAD')->count(),
        ];

        $modeMix = [];
        foreach (['SEA' => __('Sea'), 'AIR' => __('Air'), 'ROAD' => __('Road')] as $modeKey => $modeLabel) {
            $cnt = $totalModesCount[$modeKey];
            $modeMix[$modeKey] = [
                'code' => $modeKey,
                'label' => $modeLabel,
                'count' => $cnt,
                'active' => $activeModesCount[$modeKey],
                'share' => $totalShipments > 0 ? round(($cnt / $totalShipments) * 100, 1) : 0.0,
            ];
        }

        // Unified role-scoped map payload for both 3D globe and 2D Leaflet
        $mapData = $this->buildMapDataPayload($user);
        $mapShipments = $mapData['shipments'];

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
            'statusBreakdown',
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
            'mapData',
            'mapShipments',
            'activeShipmentsCount',
            'delayRate',
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
            'freeTimeService',
            'activeModesCount',
            'modeMix'
        ));
    }

    /**
     * Role-scoped JSON endpoint consumed by both the 3D globe and 2D Leaflet map.
     */
    public function mapData(Request $request): JsonResponse
    {
        $user = Auth::user();
        $payload = $this->buildMapDataPayload(
            $user,
            $request->query('mode'),
            $request->query('filter')
        );

        return response()->json(['data' => $payload]);
    }

    /**
     * Build the unified, role-scoped shipment route and location payload for map views.
     *
     * @return array<string, mixed>
     */
    protected function buildMapDataPayload(User $user, ?string $mode = null, ?string $filter = null): array
    {
        $rawMode = strtoupper(trim((string) $mode));
        $normalizedMode = in_array($rawMode, ['SEA', 'AIR', 'ROAD'], true) ? $rawMode : 'ALL';

        $rawFilter = strtoupper(trim((string) $filter));
        $normalizedFilter = in_array($rawFilter, ['DELAYED', 'EXCEPTIONS', 'SEA', 'AIR', 'ROAD'], true) ? $rawFilter : 'ALL';

        if ($normalizedMode === 'ALL' && in_array($normalizedFilter, ['SEA', 'AIR', 'ROAD'], true)) {
            $normalizedMode = $normalizedFilter;
            $normalizedFilter = 'ALL';
        }

        $query = Shipment::query()
            ->whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
            ->with([
                'originLocation',
                'destinationLocation',
                'currentLocation',
                'latestTrackingEvent.location',
                'carrier',
                'exceptions',
            ]);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }

        if (in_array($normalizedMode, ['SEA', 'AIR', 'ROAD'], true)) {
            $query->where('transport_mode', $normalizedMode);
        }

        if ($normalizedFilter === 'DELAYED') {
            $query->where('is_delayed', true);
        } elseif ($normalizedFilter === 'EXCEPTIONS') {
            $query->whereHas('exceptions', fn ($q) => $q->whereIn('status', ['OPEN', 'INVESTIGATING']));
        }

        $shipments = $query->latest()->get();

        $locationsById = [];
        $registerLocation = static function (?Location $loc) use (&$locationsById): void {
            if (! $loc || isset($locationsById[$loc->id])) {
                return;
            }
            if ($loc->latitude === null || $loc->longitude === null) {
                return;
            }

            $locationsById[$loc->id] = [
                'id' => (int) $loc->id,
                'name' => $loc->name,
                'short_name' => $loc->short_name,
                'code' => $loc->display_code,
                'display_code' => $loc->display_code,
                'code_type' => $loc->code_type_label,
                'code_type_label' => $loc->code_type_label,
                'unlocode' => $loc->display_code,
                'city' => $loc->city,
                'country' => $loc->country,
                'type' => $loc->type,
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
            ];
        };

        $formatEndpoint = static function (?Location $loc, string $fallbackName, string $fallbackCode): array {
            return [
                'id' => $loc?->id ? (int) $loc->id : null,
                'name' => $loc?->name ?? $fallbackName,
                'short_name' => $loc?->short_name ?? $fallbackName,
                'code' => $loc?->display_code ?? $fallbackCode,
                'code_type' => $loc?->code_type_label ?? 'Code',
                'unlocode' => $loc?->display_code ?? $fallbackCode,
                'city' => $loc?->city ?? '',
                'country' => $loc?->country ?? '',
                'lat' => $loc?->latitude !== null ? (float) $loc->latitude : null,
                'lng' => $loc?->longitude !== null ? (float) $loc->longitude : null,
            ];
        };

        $mappedShipments = $shipments->map(function (Shipment $s) use ($registerLocation, $formatEndpoint): array {
            $modeCode = strtoupper((string) ($s->transport_mode ?? 'SEA'));
            $modeLabel = match ($modeCode) {
                'AIR' => 'Air',
                'ROAD' => 'Road',
                default => 'Sea',
            };

            $registerLocation($s->originLocation);
            $registerLocation($s->destinationLocation);
            $registerLocation($s->currentLocation);
            $registerLocation($s->latestTrackingEvent?->location);

            $openExceptions = $s->exceptions
                ->filter(fn (ShipmentException $e) => in_array($e->status, ['OPEN', 'INVESTIGATING'], true))
                ->values();

            $latestEvent = $s->latestTrackingEvent;
            $eventLocation = $latestEvent?->location ?? $s->currentLocation;

            $trackingLat = $latestEvent?->latitude !== null
                ? (float) $latestEvent->latitude
                : ($eventLocation?->latitude !== null ? (float) $eventLocation->latitude : null);
            $trackingLng = $latestEvent?->longitude !== null
                ? (float) $latestEvent->longitude
                : ($eventLocation?->longitude !== null ? (float) $eventLocation->longitude : null);

            $latestTracking = ($trackingLat !== null && $trackingLng !== null) ? [
                'location_name' => $eventLocation?->name ?? $latestEvent?->title ?? 'Latest checkpoint',
                'short_name' => $eventLocation?->short_name ?? $eventLocation?->name ?? 'Latest checkpoint',
                'code' => $eventLocation?->display_code ?? '',
                'code_type' => $eventLocation?->code_type_label ?? 'Code',
                'lat' => $trackingLat,
                'lng' => $trackingLng,
                'occurred_at' => $latestEvent?->occurred_at?->toIso8601String(),
                'occurred_at_formatted' => $latestEvent?->occurred_at?->format('M d, Y H:i'),
                'title' => $latestEvent?->title ?? $s->status_display_label,
                'status' => $latestEvent?->status ?? $s->status,
                'event_type' => $latestEvent?->event_type,
            ] : null;

            $currentPoint = $latestTracking ? [
                'name' => $latestTracking['location_name'],
                'short_name' => $latestTracking['short_name'],
                'code' => $latestTracking['code'],
                'code_type' => $latestTracking['code_type'],
                'unlocode' => $latestTracking['code'],
                'lat' => $latestTracking['lat'],
                'lng' => $latestTracking['lng'],
            ] : null;

            return [
                'id' => (int) $s->id,
                'tracking_number' => $s->tracking_number,
                'mode' => $modeCode,
                'mode_label' => $modeLabel,
                'transport_mode' => $modeCode,
                'primary_reference' => $s->primary_reference,
                'primary_reference_label' => $s->primary_reference_label,
                'labeled_primary_reference' => $s->labeled_primary_reference,
                'status' => $s->status,
                'status_label' => $s->status_display_label,
                'is_delayed' => (bool) $s->is_delayed,
                'delay_hours' => (float) $s->delay_hours,
                'delay_severity' => strtoupper((string) ($s->delay_severity ?? 'NONE')),
                'open_exceptions_count' => $openExceptions->count(),
                'has_exception' => $openExceptions->isNotEmpty(),
                'carrier' => $s->carrier?->name ?? 'Carrier',
                'carrier_mode' => $s->carrier?->mode ?? $modeCode,
                'origin' => $formatEndpoint($s->originLocation, 'Origin', 'ORI'),
                'destination' => $formatEndpoint($s->destinationLocation, 'Destination', 'DST'),
                'latest_tracking' => $latestTracking,
                'current' => $currentPoint,
                'exceptions' => $openExceptions->map(fn (ShipmentException $e) => [
                    'code' => $e->exception_code,
                    'severity' => $e->severity,
                    'status' => $e->status,
                    'description' => $e->description,
                ])->toArray(),
                'url' => route('shipments.show', $s),
            ];
        })->values()->toArray();

        return [
            'shipments' => $mappedShipments,
            'locations' => array_values($locationsById),
            'filters' => [
                'mode' => $normalizedMode,
                'filter' => $normalizedFilter,
            ],
            'meta' => [
                'total' => count($mappedShipments),
                'role' => $user->role,
                'tile_url' => config('cargotrace.map.tile_url'),
                'tile_attribution' => config('cargotrace.map.attribution'),
            ],
        ];
    }
}

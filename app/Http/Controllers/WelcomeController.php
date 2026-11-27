<?php

namespace App\Http\Controllers;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\EtaService;
use App\Services\ExternalIntegrationService;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function __construct(
        protected ExternalIntegrationService $externalService,
        protected EtaService $etaService
    ) {}

    /**
     * Validate that a latitude/longitude pair is finite, in range, and not Null Island (0, 0).
     */
    protected function isValidCoordinate(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null || ! is_finite($lat) || ! is_finite($lng)) {
            return false;
        }

        if ($lat < -90.0 || $lat > 90.0 || $lng < -180.0 || $lng > 180.0) {
            return false;
        }

        if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
            return false;
        }

        return true;
    }

    public function index(): View
    {
        $totalShipments = Shipment::count();
        $totalDelivered = Shipment::where('status', 'DELIVERED')->count();
        $deliveredOnTime = Shipment::where('status', 'DELIVERED')->where('is_delayed', false)->count();
        $onTimePercentage = $totalDelivered > 0 ? round(($deliveredOnTime / $totalDelivered) * 100, 1) : 99.2;
        $totalValueUsd = Shipment::sum('converted_value_usd');
        $locations = Location::all();
        $carriers = Carrier::where('is_active', true)->get();

        $modeTotals = [
            'SEA' => Shipment::where('transport_mode', 'SEA')->count(),
            'AIR' => Shipment::where('transport_mode', 'AIR')->count(),
            'ROAD' => Shipment::where('transport_mode', 'ROAD')->count(),
        ];

        $modeThresholds = [
            'SEA' => $this->etaService->getThresholdsForMode('SEA'),
            'AIR' => $this->etaService->getThresholdsForMode('AIR'),
            'ROAD' => $this->etaService->getThresholdsForMode('ROAD'),
        ];

        $freeTimeDays = (int) (Setting::get('free_days_after_arrival') ?? config('cargotrace.free_time.days', 5));

        $activeShipments = Shipment::whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
            ->with(['originLocation', 'destinationLocation', 'currentLocation', 'latestTrackingEvent.location', 'carrier', 'exceptions'])
            ->get();

        $featuredShipment = Shipment::where('status', 'IN_TRANSIT')
            ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'trackingEvents.location'])
            ->first();

        if (request()->filled('tracking')) {
            $t = trim(request('tracking'));
            $cleanT = strtoupper(preg_replace('/\s+/', '', $t));
            $searched = Shipment::where('tracking_number', $t)
                ->orWhereHas('items', function ($iq) use ($t, $cleanT) {
                    $iq->where('container_number', $t)
                        ->orWhere('container_number', $cleanT)
                        ->orWhere('sku', $t)
                        ->orWhere('sku', $cleanT);
                })
                ->with(['carrier', 'originLocation', 'destinationLocation', 'currentLocation', 'trackingEvents.location'])
                ->first();
            if ($searched) {
                $featuredShipment = $searched;
            }
        }

        $news = $this->externalService->getLogisticsNews();
        $currencyData = $this->externalService->convertCurrency(1, 'USD', 'EUR');
        $rates = $currencyData['rates_snapshot'] ?? [];

        // Format normalized shipments payload for Leaflet world map (matching dashboard payload contract)
        $mapShipments = $activeShipments
            ->filter(function (Shipment $s): bool {
                $origLat = $s->originLocation?->latitude !== null ? (float) $s->originLocation->latitude : null;
                $origLng = $s->originLocation?->longitude !== null ? (float) $s->originLocation->longitude : null;
                $destLat = $s->destinationLocation?->latitude !== null ? (float) $s->destinationLocation->latitude : null;
                $destLng = $s->destinationLocation?->longitude !== null ? (float) $s->destinationLocation->longitude : null;

                return $this->isValidCoordinate($origLat, $origLng)
                    && $this->isValidCoordinate($destLat, $destLng);
            })
            ->map(function (Shipment $s): array {
                $modeCode = strtoupper((string) ($s->transport_mode ?? $s->carrier?->mode ?? 'SEA'));
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
                $hasValidTracking = $this->isValidCoordinate($trackingLat, $trackingLng);

                $currentPoint = $hasValidTracking ? [
                    'name' => $eventLocation?->name ?? $latestEvent?->title ?? 'Latest checkpoint',
                    'short_name' => $eventLocation?->short_name ?? $eventLocation?->name ?? 'Latest checkpoint',
                    'code' => $eventLocation?->display_code ?? '',
                    'code_type' => $eventLocation?->code_type_label ?? 'Code',
                    'unlocode' => $eventLocation?->display_code ?? '',
                    'lat' => $trackingLat,
                    'lng' => $trackingLng,
                ] : null;

                $latestTracking = $hasValidTracking ? [
                    'location_name' => $currentPoint['name'],
                    'short_name' => $currentPoint['short_name'],
                    'code' => $currentPoint['code'],
                    'code_type' => $currentPoint['code_type'],
                    'lat' => $trackingLat,
                    'lng' => $trackingLng,
                    'occurred_at' => $latestEvent?->occurred_at?->toIso8601String(),
                    'occurred_at_formatted' => $latestEvent?->occurred_at?->format('M d, Y H:i'),
                ] : null;

                return [
                    'id' => (int) $s->id,
                    'tracking_number' => $s->tracking_number,
                    'status' => $s->status,
                    'status_label' => $s->status_display_label,
                    'mode' => $modeCode,
                    'transport_mode' => $modeCode,
                    'is_delayed' => (bool) $s->is_delayed,
                    'delay_hours' => (int) $s->delay_hours,
                    'delay_severity' => strtoupper((string) ($s->delay_severity ?? 'NONE')),
                    'has_exception' => $openExceptions->isNotEmpty(),
                    'open_exceptions_count' => $openExceptions->count(),
                    'carrier' => $s->carrier?->name ?? 'Carrier',
                    'carrier_mode' => $s->carrier?->mode ?? $modeCode,
                    'origin' => [
                        'name' => $s->originLocation?->name ?? 'Origin',
                        'short_name' => $s->originLocation?->short_name ?? 'Origin',
                        'code' => $s->originLocation?->display_code ?? 'ORI',
                        'code_type' => $s->originLocation?->code_type_label ?? 'Code',
                        'unlocode' => $s->originLocation?->display_code ?? 'ORI',
                        'city' => $s->originLocation?->city ?? '',
                        'lat' => $s->originLocation?->latitude !== null ? (float) $s->originLocation->latitude : null,
                        'lng' => $s->originLocation?->longitude !== null ? (float) $s->originLocation->longitude : null,
                    ],
                    'destination' => [
                        'name' => $s->destinationLocation?->name ?? 'Destination',
                        'short_name' => $s->destinationLocation?->short_name ?? 'Destination',
                        'code' => $s->destinationLocation?->display_code ?? 'DST',
                        'code_type' => $s->destinationLocation?->code_type_label ?? 'Code',
                        'unlocode' => $s->destinationLocation?->display_code ?? 'DST',
                        'city' => $s->destinationLocation?->city ?? '',
                        'lat' => $s->destinationLocation?->latitude !== null ? (float) $s->destinationLocation->latitude : null,
                        'lng' => $s->destinationLocation?->longitude !== null ? (float) $s->destinationLocation->longitude : null,
                    ],
                    'current' => $currentPoint,
                    'latest_tracking' => $latestTracking,
                    'exceptions' => $openExceptions->map(fn (ShipmentException $e) => [
                        'code' => $e->exception_code,
                        'severity' => $e->severity,
                        'status' => $e->status,
                        'description' => $e->description,
                    ])->toArray(),
                    'url' => url('/track/'.$s->tracking_number),
                ];
            })
            ->values()
            ->toArray();

        $mapLocations = $locations
            ->filter(function (Location $loc): bool {
                $lat = $loc->latitude !== null ? (float) $loc->latitude : null;
                $lng = $loc->longitude !== null ? (float) $loc->longitude : null;

                return $this->isValidCoordinate($lat, $lng);
            })
            ->map(fn (Location $loc) => [
                'id' => (int) $loc->id,
                'name' => $loc->name,
                'short_name' => $loc->short_name,
                'display_code' => $loc->display_code,
                'code_type_label' => $loc->code_type_label,
                'country' => $loc->country,
                'type' => $loc->type,
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
            ])
            ->values()
            ->all();

        return view('welcome', compact(
            'totalShipments',
            'onTimePercentage',
            'totalValueUsd',
            'locations',
            'carriers',
            'modeTotals',
            'modeThresholds',
            'freeTimeDays',
            'activeShipments',
            'featuredShipment',
            'news',
            'currencyData',
            'rates',
            'mapShipments',
            'mapLocations'
        ));
    }
}

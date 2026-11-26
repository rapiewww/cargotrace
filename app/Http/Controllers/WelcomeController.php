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

        $modeTotals = [
            'SEA' => Shipment::where('transport_mode', 'SEA')->count(),
            'AIR' => Shipment::where('transport_mode', 'AIR')->count(),
            'ROAD' => Shipment::where('transport_mode', 'ROAD')->count(),
        ];

        $activeShipments = Shipment::whereIn('status', ['IN_TRANSIT', 'PICKED_UP', 'BOOKED'])
            ->with(['originLocation', 'destinationLocation', 'currentLocation', 'carrier'])
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

        // Format shipments payload for Leaflet Bezier routes
        $mapShipments = $activeShipments->map(function ($s) {
            return [
                'id' => $s->id,
                'tracking_number' => $s->tracking_number,
                'status' => $s->status,
                'transport_mode' => strtoupper((string) ($s->transport_mode ?? $s->carrier?->mode ?? 'SEA')),
                'is_delayed' => (bool) $s->is_delayed,
                'delay_hours' => (int) $s->delay_hours,
                'carrier' => $s->carrier?->name ?? 'Carrier',
                'carrier_mode' => $s->carrier?->mode ?? 'SEA',
                'origin' => [
                    'name' => $s->originLocation?->name ?? 'Origin',
                    'short_name' => $s->originLocation?->short_name ?? 'Origin',
                    'code' => $s->originLocation?->display_code ?? 'ORI',
                    'code_type' => $s->originLocation?->code_type_label ?? 'Code',
                    'unlocode' => $s->originLocation?->display_code ?? 'ORI',
                    'city' => $s->originLocation?->city ?? '',
                    'lat' => (float) ($s->originLocation?->latitude ?? 0),
                    'lng' => (float) ($s->originLocation?->longitude ?? 0),
                ],
                'destination' => [
                    'name' => $s->destinationLocation?->name ?? 'Destination',
                    'short_name' => $s->destinationLocation?->short_name ?? 'Destination',
                    'code' => $s->destinationLocation?->display_code ?? 'DST',
                    'code_type' => $s->destinationLocation?->code_type_label ?? 'Code',
                    'unlocode' => $s->destinationLocation?->display_code ?? 'DST',
                    'city' => $s->destinationLocation?->city ?? '',
                    'lat' => (float) ($s->destinationLocation?->latitude ?? 0),
                    'lng' => (float) ($s->destinationLocation?->longitude ?? 0),
                ],
                'current' => $s->currentLocation ? [
                    'name' => $s->currentLocation->name,
                    'short_name' => $s->currentLocation->short_name,
                    'code' => $s->currentLocation->display_code,
                    'code_type' => $s->currentLocation->code_type_label,
                    'unlocode' => $s->currentLocation->display_code,
                    'lat' => (float) $s->currentLocation->latitude,
                    'lng' => (float) $s->currentLocation->longitude,
                ] : null,
                'exceptions' => [],
                'url' => url('/track/'.$s->tracking_number),
            ];
        })->toArray();

        $mapLocations = $locations
            ->filter(fn (Location $loc) => $loc->latitude !== null && $loc->longitude !== null)
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

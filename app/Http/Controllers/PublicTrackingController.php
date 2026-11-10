<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    /**
     * Show public tracking page for a given tracking number.
     * Accessible without authentication, sanitized to non-sensitive fields.
     */
    public function show(string $tracking_number): View
    {
        $shipment = Shipment::where('tracking_number', $tracking_number)
            ->with([
                'carrier',
                'originLocation',
                'destinationLocation',
                'currentLocation',
                'trackingEvents' => fn ($q) => $q->orderBy('occurred_at', 'desc')->with('location'),
            ])
            ->firstOrFail();

        // Prepare map payload
        $mapPayload = [
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'is_delayed' => (bool) $shipment->is_delayed,
            'delay_hours' => (int) $shipment->delay_hours,
            'originLocation' => [
                'name' => $shipment->originLocation->name,
                'unlocode' => $shipment->originLocation->unlocode,
                'latitude' => (float) $shipment->originLocation->latitude,
                'longitude' => (float) $shipment->originLocation->longitude,
            ],
            'destinationLocation' => [
                'name' => $shipment->destinationLocation->name,
                'unlocode' => $shipment->destinationLocation->unlocode,
                'latitude' => (float) $shipment->destinationLocation->latitude,
                'longitude' => (float) $shipment->destinationLocation->longitude,
            ],
            'currentLocation' => $shipment->currentLocation ? [
                'id' => $shipment->currentLocation->id,
                'name' => $shipment->currentLocation->name,
                'unlocode' => $shipment->currentLocation->unlocode,
                'latitude' => (float) $shipment->currentLocation->latitude,
                'longitude' => (float) $shipment->currentLocation->longitude,
            ] : null,
        ];

        return view('tracking.show', compact('shipment', 'mapPayload'));
    }
}

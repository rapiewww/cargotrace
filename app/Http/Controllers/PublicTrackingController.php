<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\FreeTimeService;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    /**
     * Show public tracking page for a given tracking number.
     * Accessible without authentication, sanitized to non-sensitive fields.
     */
    public function show(string $tracking_number): View
    {
        $clean = strtoupper(preg_replace('/\s+/', '', $tracking_number));
        $cleanDigits = preg_replace('/[^\d]/', '', $tracking_number);
        $shipment = Shipment::where('tracking_number', $tracking_number)
            ->orWhere('container_number', $clean)
            ->orWhere('awb_number', $tracking_number)
            ->when($cleanDigits !== '', fn ($q) => $q->orWhere('awb_number', $cleanDigits))
            ->orWhere('vehicle_plate', $tracking_number)
            ->orWhere('cmr_number', $tracking_number)
            ->orWhereHas('items', function ($iq) use ($tracking_number, $clean) {
                $iq->where('container_number', $tracking_number)
                    ->orWhere('container_number', $clean)
                    ->orWhere('sku', $tracking_number)
                    ->orWhere('sku', $clean);
            })
            ->with([
                'carrier',
                'originLocation',
                'destinationLocation',
                'currentLocation',
                'items',
                'trackingEvents' => fn ($q) => $q->orderBy('occurred_at', 'desc')->with('location'),
            ])
            ->firstOrFail();

        $freeTime = app(FreeTimeService::class)->calculate($shipment);

        // Prepare map payload
        $mapPayload = [
            'id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'status' => $shipment->status,
            'is_delayed' => (bool) $shipment->is_delayed,
            'delay_hours' => (int) $shipment->delay_hours,
            'originLocation' => [
                'name' => $shipment->originLocation->name,
                'short_name' => $shipment->originLocation->short_name,
                'code' => $shipment->originLocation->display_code,
                'code_type' => $shipment->originLocation->code_type_label,
                'unlocode' => $shipment->originLocation->display_code,
                'latitude' => (float) $shipment->originLocation->latitude,
                'longitude' => (float) $shipment->originLocation->longitude,
            ],
            'destinationLocation' => [
                'name' => $shipment->destinationLocation->name,
                'short_name' => $shipment->destinationLocation->short_name,
                'code' => $shipment->destinationLocation->display_code,
                'code_type' => $shipment->destinationLocation->code_type_label,
                'unlocode' => $shipment->destinationLocation->display_code,
                'latitude' => (float) $shipment->destinationLocation->latitude,
                'longitude' => (float) $shipment->destinationLocation->longitude,
            ],
            'currentLocation' => $shipment->currentLocation ? [
                'id' => $shipment->currentLocation->id,
                'name' => $shipment->currentLocation->name,
                'short_name' => $shipment->currentLocation->short_name,
                'code' => $shipment->currentLocation->display_code,
                'code_type' => $shipment->currentLocation->code_type_label,
                'unlocode' => $shipment->currentLocation->display_code,
                'latitude' => (float) $shipment->currentLocation->latitude,
                'longitude' => (float) $shipment->currentLocation->longitude,
            ] : null,
        ];

        return view('tracking.show', compact('shipment', 'mapPayload', 'freeTime'));
    }
}

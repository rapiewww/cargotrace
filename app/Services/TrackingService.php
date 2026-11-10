<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    public function __construct(
        protected ExternalIntegrationService $externalService,
        protected ShipmentService $shipmentService,
        protected EtaService $etaService,
        protected AuditService $auditService
    ) {}

    /**
     * Add a verified tracking event to a shipment while preserving chronology and fetching live weather.
     *
     * @param  array<string, mixed>  $data
     */
    public function addTrackingEvent(Shipment $shipment, array $data): TrackingEvent
    {
        $occurredAt = isset($data['occurred_at'])
            ? Carbon::parse($data['occurred_at'])
            : now();

        // 1. Chronological order validation: cannot be earlier than the most recent event
        $latestEvent = $shipment->trackingEvents()->orderByDesc('occurred_at')->first();
        if ($latestEvent && $occurredAt->lt($latestEvent->occurred_at)) {
            throw ValidationException::withMessages([
                'occurred_at' => sprintf(
                    'Tracking event timestamp (%s) cannot be earlier than the most recent recorded event (%s).',
                    $occurredAt->toIso8601String(),
                    $latestEvent->occurred_at->toIso8601String()
                ),
            ]);
        }

        // 2. Resolve latitude, longitude, and location name
        $locationId = $data['location_id'] ?? null;
        $latitude = isset($data['latitude']) ? (float) $data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float) $data['longitude'] : null;
        $locationName = null;

        if ($locationId) {
            $location = Location::find($locationId);
            if ($location) {
                $locationName = $location->name;
                $latitude ??= (float) $location->latitude;
                $longitude ??= (float) $location->longitude;
            }
        }

        // 3. Automatically fetch weather snapshot for coordinates
        $weatherSnapshot = null;
        if ($latitude !== null && $longitude !== null) {
            $weatherSnapshot = $this->externalService->getWeatherByCoordinates($latitude, $longitude, $locationName);
        }

        $eventStatus = $data['status'] ?? $shipment->status;

        // 4. Create new tracking event (never overwriting old ones)
        $event = TrackingEvent::create([
            'shipment_id' => $shipment->id,
            'location_id' => $locationId,
            'status' => $eventStatus,
            'event_type' => $data['event_type'] ?? 'NORMAL',
            'title' => $data['title'] ?? "Shipment Update: {$eventStatus}",
            'description' => $data['description'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'weather_snapshot' => $weatherSnapshot,
            'occurred_at' => $occurredAt,
            'created_by' => Auth::id() ?? ($data['created_by'] ?? null),
        ]);

        // 5. If status differs, trigger strict status transition on shipment
        if ($eventStatus !== $shipment->status) {
            $this->shipmentService->updateStatus(
                $shipment,
                $eventStatus,
                $locationId,
                $data['description'] ?? "Status updated via tracking event '{$event->title}'"
            );
        } elseif ($locationId && $shipment->current_location_id !== $locationId) {
            $shipment->update(['current_location_id' => $locationId]);
        }

        // 6. If estimated arrival is provided/updated, re-evaluate delay
        if (! empty($data['estimated_arrival_at'])) {
            $shipment->estimated_arrival_at = Carbon::parse($data['estimated_arrival_at']);
            $this->etaService->evaluateDelay($shipment);
        }

        // 7. Audit log the event addition
        $this->auditService->log('TRACKING_EVENT_ADDED', $event, null, [
            'shipment_id' => $shipment->id,
            'tracking_number' => $shipment->tracking_number,
            'event_title' => $event->title,
            'status' => $event->status,
            'occurred_at' => $event->occurred_at->toIso8601String(),
        ]);

        return $event;
    }
}

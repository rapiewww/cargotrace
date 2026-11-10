<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ExceptionService
{
    /**
     * Allowed exception codes and severities.
     *
     * @var list<string>
     */
    protected array $allowedCodes = [
        'CUSTOMS_HOLD',
        'WEATHER_DELAY',
        'PORT_CONGESTION',
        'DAMAGED',
        'MISROUTED',
        'DOCUMENTATION_ISSUE',
    ];

    /**
     * @var list<string>
     */
    protected array $allowedSeverities = ['LOW', 'MEDIUM', 'HIGH'];

    /**
     * @var list<string>
     */
    protected array $allowedStatuses = ['OPEN', 'INVESTIGATING', 'RESOLVED'];

    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Report a shipment exception, log tracking event, notify customer, and audit.
     *
     * @param  array<string, mixed>  $data
     */
    public function reportException(Shipment $shipment, array $data): ShipmentException
    {
        $code = strtoupper($data['exception_code'] ?? '');
        $severity = strtoupper($data['severity'] ?? 'MEDIUM');

        if (! in_array($code, $this->allowedCodes, true)) {
            throw ValidationException::withMessages([
                'exception_code' => "Invalid exception code: {$code}. Must be one of: ".implode(', ', $this->allowedCodes),
            ]);
        }

        if (! in_array($severity, $this->allowedSeverities, true)) {
            throw ValidationException::withMessages([
                'severity' => "Invalid severity: {$severity}. Must be one of: ".implode(', ', $this->allowedSeverities),
            ]);
        }

        $detectedAt = isset($data['detected_at'])
            ? Carbon::parse($data['detected_at'])
            : now();

        $reporterId = Auth::id() ?? ($data['reported_by'] ?? null);

        // 1. Create shipment exception record
        $exception = ShipmentException::create([
            'shipment_id' => $shipment->id,
            'exception_code' => $code,
            'severity' => $severity,
            'status' => 'OPEN',
            'title' => $data['title'] ?? "Exception: {$code}",
            'description' => $data['description'] ?? "Incident reported for shipment {$shipment->tracking_number}.",
            'related_news_url' => $data['related_news_url'] ?? null,
            'resolution_notes' => null,
            'detected_at' => $detectedAt,
            'resolved_at' => null,
            'reported_by' => $reporterId,
            'resolved_by' => null,
        ]);

        // 2. Create tracking event with event_type = EXCEPTION
        TrackingEvent::create([
            'shipment_id' => $shipment->id,
            'location_id' => $shipment->current_location_id ?? $shipment->origin_location_id,
            'status' => $shipment->status,
            'event_type' => 'EXCEPTION',
            'title' => "EXCEPTION: {$exception->title}",
            'description' => $exception->description,
            'latitude' => $shipment->currentLocation?->latitude ?? $shipment->originLocation?->latitude,
            'longitude' => $shipment->currentLocation?->longitude ?? $shipment->originLocation?->longitude,
            'weather_snapshot' => null,
            'occurred_at' => $detectedAt,
            'created_by' => $reporterId,
        ]);

        // 3. Notify customer
        Notification::create([
            'user_id' => $shipment->customer_id,
            'shipment_id' => $shipment->id,
            'type' => 'SHIPMENT_EXCEPTION_ALERT',
            'title' => "Incident Alert ({$severity}): {$exception->title}",
            'message' => "An issue ({$code}) was detected on shipment {$shipment->tracking_number}: {$exception->description}",
            'read_at' => null,
        ]);

        // 4. Audit Log
        $this->auditService->log('EXCEPTION_REPORTED', $exception, null, [
            'shipment_tracking' => $shipment->tracking_number,
            'exception_code' => $code,
            'severity' => $severity,
            'title' => $exception->title,
        ]);

        return $exception;
    }

    /**
     * Transition exception status (OPEN -> INVESTIGATING -> RESOLVED).
     */
    public function updateExceptionStatus(ShipmentException $exception, string $status, ?string $resolutionNotes = null): ShipmentException
    {
        $status = strtoupper($status);

        if (! in_array($status, $this->allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid exception status: {$status}. Must be one of: ".implode(', ', $this->allowedStatuses),
            ]);
        }

        $oldValues = [
            'status' => $exception->status,
            'resolution_notes' => $exception->resolution_notes,
            'resolved_at' => $exception->resolved_at?->toIso8601String(),
            'resolved_by' => $exception->resolved_by,
        ];

        $exception->status = $status;

        if ($resolutionNotes !== null) {
            $exception->resolution_notes = $resolutionNotes;
        }

        if ($status === 'RESOLVED') {
            $exception->resolved_at = now();
            $exception->resolved_by = Auth::id() ?? $exception->resolved_by;
        }

        $exception->save();

        // Audit Log
        $this->auditService->log('EXCEPTION_STATUS_UPDATED', $exception, $oldValues, [
            'status' => $status,
            'resolution_notes' => $exception->resolution_notes,
            'resolved_at' => $exception->resolved_at?->toIso8601String(),
            'resolved_by' => $exception->resolved_by,
        ]);

        // If resolved, notify customer
        if ($status === 'RESOLVED') {
            Notification::create([
                'user_id' => $exception->shipment->customer_id,
                'shipment_id' => $exception->shipment_id,
                'type' => 'SHIPMENT_EXCEPTION_RESOLVED',
                'title' => "Exception Resolved: {$exception->title}",
                'message' => "The exception on shipment {$exception->shipment->tracking_number} has been resolved: ".($resolutionNotes ?? 'All operational issues resolved.'),
                'read_at' => null,
            ]);
        }

        return $exception;
    }
}

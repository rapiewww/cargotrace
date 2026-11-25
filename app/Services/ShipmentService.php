<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\TrackingEvent;
use App\Support\Iso6346;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    /**
     * Allowed status transitions: current_status => [allowed_next_statuses]
     *
     * @var array<string, list<string>>
     */
    protected array $transitions = [
        'CREATED' => ['BOOKED'],
        'BOOKED' => ['PICKED_UP'],
        'PICKED_UP' => ['IN_TRANSIT'],
        'IN_TRANSIT' => ['DELIVERED'],
        'DELIVERED' => [], // Terminal status
    ];

    public function __construct(
        protected ExternalIntegrationService $externalService,
        protected AuditService $auditService,
        protected EtaService $etaService
    ) {}

    /**
     * Determine if a transition from current to new status is valid.
     */
    public function canTransitionTo(string $currentStatus, string $newStatus): bool
    {
        if ($currentStatus === 'DELIVERED') {
            return false;
        }

        $allowed = $this->transitions[$currentStatus] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Create a new shipment along with cargo items and initial tracking event in a transaction.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createShipment(array $data, array $items = []): Shipment
    {
        return DB::transaction(function () use ($data, $items) {
            $currencyCode = strtoupper($data['currency_code'] ?? 'USD');

            // Calculate total declared value, weight, and volume from items if not provided
            $declaredValue = (float) ($data['declared_value'] ?? 0);
            $totalWeight = (float) ($data['total_weight_kg'] ?? 0);
            $totalVolume = (float) ($data['total_volume_cbm'] ?? 0);

            if (! empty($items)) {
                $calcValue = 0;
                $calcWeight = 0;
                $calcVolume = 0;

                foreach ($items as $item) {
                    $qty = (int) ($item['quantity'] ?? 1);
                    $unitVal = (float) ($item['unit_value'] ?? 0);
                    $calcValue += ($qty * $unitVal);
                    $calcWeight += (float) ($item['weight_kg'] ?? 0);
                    $calcVolume += (float) ($item['volume_cbm'] ?? 0);
                }

                if ($declaredValue <= 0) {
                    $declaredValue = $calcValue;
                }
                if ($totalWeight <= 0) {
                    $totalWeight = $calcWeight;
                }
                if ($totalVolume <= 0) {
                    $totalVolume = $calcVolume;
                }
            }

            // Convert declared value to USD using ExternalIntegrationService
            $conversion = $this->externalService->convertCurrency($declaredValue, $currencyCode, 'USD');
            $convertedUsd = (float) ($conversion['converted_amount'] ?? $declaredValue);
            $rateToUsd = (float) ($conversion['rate_to_usd'] ?? 1.0);
            $ratesSnapshot = $conversion['rates_snapshot'] ?? null;

            $mode = strtoupper($data['transport_mode'] ?? 'SEA');
            // Generate tracking number if absent
            $trackingNumber = $data['tracking_number'] ?? $this->generateTrackingNumber($mode);

            $shipment = Shipment::create([
                'tracking_number' => $trackingNumber,
                'transport_mode' => $mode,
                'reference_number' => $data['reference_number'] ?? null,
                'container_number' => $data['container_number'] ?? null,
                'bill_of_lading_number' => $data['bill_of_lading_number'] ?? null,
                'awb_number' => $data['awb_number'] ?? null,
                'vehicle_plate' => $data['vehicle_plate'] ?? null,
                'cmr_number' => $data['cmr_number'] ?? null,
                'customer_id' => $data['customer_id'],
                'carrier_id' => $data['carrier_id'],
                'origin_location_id' => $data['origin_location_id'],
                'destination_location_id' => $data['destination_location_id'],
                'current_location_id' => $data['origin_location_id'],
                'cargo_type' => $data['cargo_type'] ?? 'FCL',
                'total_weight_kg' => $totalWeight,
                'total_volume_cbm' => $totalVolume,
                'declared_value' => $declaredValue,
                'currency_code' => $currencyCode,
                'shipping_cost' => (float) ($data['shipping_cost'] ?? 0),
                'exchange_rate_to_usd' => $rateToUsd,
                'converted_value_usd' => $convertedUsd,
                'currency_snapshot' => $ratesSnapshot,
                'status' => 'CREATED',
                'expected_departure_at' => $data['expected_departure_at'],
                'expected_arrival_at' => $data['expected_arrival_at'],
                'estimated_arrival_at' => $data['estimated_arrival_at'] ?? $data['expected_arrival_at'],
                'actual_arrival_at' => null,
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => 'NONE',
                'delay_reason' => null,
                'route_news_snapshot' => $data['route_news_snapshot'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Save items
            foreach ($items as $itemData) {
                $containerNumber = $itemData['container_number'] ?? ($data['container_number'] ?? null);
                if (! $containerNumber && isset($itemData['sku']) && Iso6346::isValid($itemData['sku'])) {
                    $containerNumber = $itemData['sku'];
                }

                $sku = $itemData['sku'] ?? ($containerNumber ?? 'SKU-'.strtoupper(Str::random(6)));

                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'container_number' => $containerNumber ? Iso6346::normalize($containerNumber) : null,
                    'sku' => $sku,
                    'hs_code' => $itemData['hs_code'] ?? null,
                    'packaging' => $itemData['packaging'] ?? null,
                    'description' => $itemData['description'] ?? 'Cargo item',
                    'quantity' => (int) ($itemData['quantity'] ?? 1),
                    'unit_value' => (float) ($itemData['unit_value'] ?? 0),
                    'currency_code' => strtoupper($itemData['currency_code'] ?? $currencyCode),
                    'weight_kg' => (float) ($itemData['weight_kg'] ?? 0),
                    'volume_cbm' => (float) ($itemData['volume_cbm'] ?? 0),
                    'is_hazardous' => (bool) ($itemData['is_hazardous'] ?? false),
                ]);
            }

            // Create initial CREATED tracking event
            $origin = Location::find($shipment->origin_location_id);
            $weather = null;
            if ($origin) {
                $weather = $this->externalService->getWeatherByCoordinates(
                    (float) $origin->latitude,
                    (float) $origin->longitude,
                    $origin->name
                );
            }

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $shipment->origin_location_id,
                'status' => 'CREATED',
                'event_type' => 'NORMAL',
                'title' => 'Shipment Created',
                'description' => 'Shipment booked and initial cargo manifest registered in platform.',
                'latitude' => $origin?->latitude,
                'longitude' => $origin?->longitude,
                'weather_snapshot' => $weather,
                'occurred_at' => now(),
                'created_by' => Auth::id() ?? ($data['created_by'] ?? null),
            ]);

            // Audit Log
            $this->auditService->log('SHIPMENT_CREATED', $shipment, null, [
                'tracking_number' => $shipment->tracking_number,
                'customer_id' => $shipment->customer_id,
                'declared_value' => $shipment->declared_value,
                'currency_code' => $shipment->currency_code,
                'converted_value_usd' => $shipment->converted_value_usd,
            ]);

            // Initial delay check if estimated_arrival_at is provided
            if ($shipment->estimated_arrival_at) {
                $this->etaService->evaluateDelay($shipment);
            }

            return $shipment->load(['items', 'trackingEvents', 'customer', 'carrier', 'originLocation', 'destinationLocation']);
        });
    }

    /**
     * Update shipment manifest data and re-evaluate delay.
     */
    public function updateShipment(Shipment $shipment, array $data): Shipment
    {
        return DB::transaction(function () use ($shipment, $data) {
            $oldValues = $shipment->only([
                'transport_mode', 'container_number', 'bill_of_lading_number', 'awb_number', 'vehicle_plate', 'cmr_number',
                'carrier_id', 'origin_location_id', 'destination_location_id', 'current_location_id',
                'expected_departure_at', 'expected_arrival_at', 'estimated_arrival_at',
                'declared_value', 'currency_code', 'cargo_type', 'reference_number', 'notes',
            ]);

            // Handle status change through strict transition validation
            if (isset($data['status']) && $data['status'] !== $shipment->status) {
                $this->updateStatus($shipment, $data['status'], $data['current_location_id'] ?? null, $data['notes'] ?? null);
            }

            // Update currency conversion if declared_value or currency_code changed
            if (isset($data['declared_value']) || isset($data['currency_code'])) {
                $declaredValue = (float) ($data['declared_value'] ?? $shipment->declared_value);
                $currencyCode = strtoupper($data['currency_code'] ?? $shipment->currency_code);
                $conversion = $this->externalService->convertCurrency($declaredValue, $currencyCode, 'USD');
                $data['converted_value_usd'] = (float) ($conversion['converted_amount'] ?? $declaredValue);
                $data['exchange_rate_to_usd'] = (float) ($conversion['rate_to_usd'] ?? 1.0);
            }

            $shipment->fill(collect($data)->except(['status'])->toArray());
            $shipment->save();

            // Re-evaluate delay after date changes
            $this->etaService->evaluateDelay($shipment);

            $this->auditService->log('SHIPMENT_UPDATED', $shipment, $oldValues, $shipment->only(array_keys($oldValues)));

            return $shipment;
        });
    }

    /**
     * Strictly update status of a shipment, ensuring terminal and transition constraints.
     */
    public function updateStatus(Shipment $shipment, string $newStatus, ?int $locationId = null, ?string $description = null): Shipment
    {
        $currentStatus = $shipment->status;

        if ($currentStatus === $newStatus) {
            return $shipment;
        }

        if ($currentStatus === 'DELIVERED') {
            throw ValidationException::withMessages([
                'status' => 'Shipment is already DELIVERED and has reached terminal state. Status cannot be modified.',
            ]);
        }

        if (! $this->canTransitionTo($currentStatus, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition from {$currentStatus} to {$newStatus}.",
            ]);
        }

        $oldValues = [
            'status' => $currentStatus,
            'current_location_id' => $shipment->current_location_id,
            'actual_arrival_at' => $shipment->actual_arrival_at?->toIso8601String(),
        ];

        $shipment->status = $newStatus;

        if ($locationId !== null) {
            $shipment->current_location_id = $locationId;
        }

        if ($newStatus === 'DELIVERED') {
            $shipment->actual_arrival_at = now();
            if ($shipment->destination_location_id) {
                $shipment->current_location_id = $shipment->destination_location_id;
            }
        }

        $shipment->save();

        // Audit Log
        $this->auditService->log('SHIPMENT_STATUS_UPDATED', $shipment, $oldValues, [
            'status' => $newStatus,
            'current_location_id' => $shipment->current_location_id,
            'actual_arrival_at' => $shipment->actual_arrival_at?->toIso8601String(),
            'notes' => $description,
        ]);

        // Customer in-app notification
        Notification::create([
            'user_id' => $shipment->customer_id,
            'shipment_id' => $shipment->id,
            'type' => 'SHIPMENT_STATUS_UPDATED',
            'title' => "Shipment {$shipment->tracking_number} is now {$newStatus}",
            'message' => $description ?? "Shipment status has advanced to {$newStatus}.",
            'read_at' => null,
        ]);

        return $shipment;
    }

    /**
     * Generate unique tracking code.
     */
    protected function generateTrackingNumber(string $mode = 'SEA'): string
    {
        $prefix = match (strtoupper($mode)) {
            'AIR', 'AIR_FREIGHT' => 'CTG-AIR',
            'ROAD' => 'CTG-ROAD',
            'MULTIMODAL' => 'CTG-MUL',
            'LAND' => 'CTG-LND',
            default => 'CTG-SEA',
        };

        $year = date('Y');

        do {
            $number = sprintf('%s-%s-%05d', $prefix, $year, random_int(1000, 99999));
        } while (Shipment::where('tracking_number', $number)->exists());

        return $number;
    }
}

<?php

namespace App\Models;

use App\Support\Awb;
use App\Support\Iso6346;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shipment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tracking_number',
        'transport_mode',
        'reference_number',
        'container_number',
        'bill_of_lading_number',
        'awb_number',
        'vehicle_plate',
        'cmr_number',
        'customer_id',
        'carrier_id',
        'origin_location_id',
        'destination_location_id',
        'current_location_id',
        'cargo_type',
        'total_weight_kg',
        'total_volume_cbm',
        'declared_value',
        'currency_code',
        'shipping_cost',
        'exchange_rate_to_usd',
        'converted_value_usd',
        'currency_snapshot',
        'status',
        'expected_departure_at',
        'expected_arrival_at',
        'estimated_arrival_at',
        'actual_arrival_at',
        'is_delayed',
        'delay_hours',
        'delay_severity',
        'delay_reason',
        'route_news_snapshot',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_weight_kg' => 'decimal:2',
            'total_volume_cbm' => 'decimal:2',
            'declared_value' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'exchange_rate_to_usd' => 'decimal:6',
            'converted_value_usd' => 'decimal:2',
            'currency_snapshot' => 'array',
            'expected_departure_at' => 'datetime',
            'expected_arrival_at' => 'datetime',
            'estimated_arrival_at' => 'datetime',
            'actual_arrival_at' => 'datetime',
            'is_delayed' => 'boolean',
            'delay_hours' => 'decimal:2',
            'route_news_snapshot' => 'array',
        ];
    }

    /**
     * Customer who owns this shipment.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Carrier handling this shipment.
     */
    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    /**
     * Origin location for this shipment.
     */
    public function originLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    /**
     * Final destination location for this shipment.
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    /**
     * Current checkpoint / location of this shipment.
     */
    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    /**
     * Cargo items contained within this shipment.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * Tracking timeline events for this shipment.
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('occurred_at', 'desc');
    }

    /**
     * Latest tracking timeline event.
     */
    public function latestTrackingEvent(): HasOne
    {
        return $this->hasOne(TrackingEvent::class)->latestOfMany('occurred_at');
    }

    /**
     * Exceptions and delays reported for this shipment.
     */
    public function exceptions(): HasMany
    {
        return $this->hasMany(ShipmentException::class);
    }

    /**
     * Notifications associated with this shipment.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Great-circle (haversine) nautical miles between origin and destination seaports.
     */
    public function getGreatCircleDistanceNmAttribute(): ?int
    {
        $orig = $this->originLocation;
        $dest = $this->destinationLocation;

        if (! $orig || ! $dest || is_null($orig->latitude) || is_null($orig->longitude) || is_null($dest->latitude) || is_null($dest->longitude)) {
            return null;
        }

        $lat1 = (float) $orig->latitude;
        $lon1 = (float) $orig->longitude;
        $lat2 = (float) $dest->latitude;
        $lon2 = (float) $dest->longitude;

        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = $earthRadiusKm * $c;

        return (int) round($distanceKm * 0.539957);
    }

    /**
     * Formatted ISO 6346 container number on shipment level (SEA only).
     */
    public function getFormattedContainerNumberAttribute(): ?string
    {
        if (strtoupper((string) ($this->transport_mode ?? 'SEA')) !== 'SEA') {
            return null;
        }

        $firstItem = $this->items->first();
        $num = $this->container_number
            ?: $firstItem?->container_number
            ?: ($firstItem && Iso6346::isValid((string) $firstItem->sku) ? $firstItem->sku : null);

        return $num ? Iso6346::format($num) : null;
    }

    /**
     * All formatted ISO 6346 container numbers associated with a SEA shipment.
     *
     * @return list<string>
     */
    public function getFormattedContainerNumbersAttribute(): array
    {
        if (strtoupper((string) ($this->transport_mode ?? 'SEA')) !== 'SEA') {
            return [];
        }

        $containers = collect([$this->container_number ? Iso6346::format($this->container_number) : null])
            ->merge($this->items->map(fn (ShipmentItem $it) => $it->formatted_container_number))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $containers;
    }

    /**
     * Formatted Air Waybill (e.g. "020-06002706") for AIR shipments only.
     */
    public function getFormattedAwbNumberAttribute(): ?string
    {
        if (strtoupper((string) ($this->transport_mode ?? 'SEA')) !== 'AIR') {
            return null;
        }

        return Awb::format($this->awb_number);
    }

    /**
     * Mode-appropriate label for the primary reference.
     */
    public function getPrimaryReferenceLabelAttribute(): string
    {
        return match (strtoupper((string) ($this->transport_mode ?? 'SEA'))) {
            'AIR' => 'AWB',
            'ROAD' => 'Plate / CMR',
            default => 'Container',
        };
    }

    /**
     * Structured mode-appropriate reference parts for views.
     *
     * @return list<array{label: string, value: string}>
     */
    public function getFormattedReferencePartsAttribute(): array
    {
        $mode = strtoupper((string) ($this->transport_mode ?? 'SEA'));

        if ($mode === 'AIR') {
            return [
                ['label' => 'AWB', 'value' => $this->formatted_awb_number ?? 'Pending'],
            ];
        }

        if ($mode === 'ROAD') {
            $parts = [];
            if ($this->vehicle_plate) {
                $parts[] = ['label' => 'Plate', 'value' => $this->vehicle_plate];
            }
            if ($this->cmr_number) {
                $parts[] = ['label' => 'CMR', 'value' => $this->cmr_number];
            }

            if (empty($parts)) {
                return [
                    ['label' => 'Plate', 'value' => 'Pending'],
                    ['label' => 'CMR', 'value' => 'Pending'],
                ];
            }

            return $parts;
        }

        $containers = $this->formatted_container_numbers;
        if (! empty($containers)) {
            return [
                ['label' => 'Container', 'value' => implode(', ', $containers)],
            ];
        }

        if ($this->bill_of_lading_number) {
            return [
                ['label' => 'B/L', 'value' => $this->bill_of_lading_number],
            ];
        }

        return [
            ['label' => 'Container', 'value' => 'Pending'],
        ];
    }

    /**
     * Mode-labeled primary reference summary (e.g. "Container: MSCU 201476 1", "AWB: 020-06002706", "Plate: BK 8419 XZ · CMR: CMR-2026-00419").
     */
    public function getLabeledPrimaryReferenceAttribute(): string
    {
        $parts = array_map(
            fn (array $part) => "{$part['label']}: {$part['value']}",
            $this->formatted_reference_parts
        );

        return implode(' · ', $parts);
    }

    /**
     * Primary reference summary per mode.
     */
    public function getPrimaryReferenceAttribute(): string
    {
        $mode = strtoupper($this->transport_mode ?? 'SEA');

        return match ($mode) {
            'AIR' => $this->formatted_awb_number ?? $this->awb_number ?? 'AWB Pending',
            'ROAD' => trim(($this->vehicle_plate ? $this->vehicle_plate : '').($this->cmr_number ? ' / '.$this->cmr_number : '')) ?: 'Plate / CMR Pending',
            default => $this->formatted_container_number ?? $this->container_number ?? $this->bill_of_lading_number ?? 'Container Pending',
        };
    }

    /**
     * Human-readable display label for status per transport mode.
     */
    public function getStatusDisplayLabelAttribute(): string
    {
        return self::statusDisplayLabel($this->status, $this->transport_mode ?? 'SEA');
    }

    /**
     * Get display label for status and mode.
     */
    public static function statusDisplayLabel(?string $status, ?string $mode = 'SEA'): string
    {
        $status = strtoupper($status ?? 'CREATED');
        $mode = strtoupper($mode ?? 'SEA');

        $labels = [
            'SEA' => [
                'CREATED' => __('Created'),
                'BOOKED' => __('Vessel Space Booked'),
                'PICKED_UP' => __('Gated in at Origin Terminal'),
                'IN_TRANSIT' => __('At Sea / In Transit'),
                'DELIVERED' => __('Discharged & Delivered'),
            ],
            'AIR' => [
                'CREATED' => __('Created'),
                'BOOKED' => __('Flight Space Booked'),
                'PICKED_UP' => __('Received at Origin Airport'),
                'IN_TRANSIT' => __('In Flight / In Transit'),
                'DELIVERED' => __('Arrived & Delivered'),
            ],
            'ROAD' => [
                'CREATED' => __('Created'),
                'BOOKED' => __('Truck / Haulier Dispatched'),
                'PICKED_UP' => __('Loaded at Depot / Warehouse'),
                'IN_TRANSIT' => __('On Road / In Transit'),
                'DELIVERED' => __('Delivered to Consignee'),
            ],
        ];

        return $labels[$mode][$status] ?? ucfirst(strtolower(str_replace('_', ' ', $status)));
    }
}

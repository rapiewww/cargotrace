<?php

namespace App\Models;

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
     * Formatted ISO 6346 container number on shipment level.
     */
    public function getFormattedContainerNumberAttribute(): ?string
    {
        $num = $this->container_number ?: $this->items->first()?->container_number ?: $this->items->first()?->sku;

        return $num ? Iso6346::format($num) : null;
    }

    /**
     * Formatted Air Waybill (e.g. "126-12345675").
     */
    public function getFormattedAwbNumberAttribute(): ?string
    {
        if (! $this->awb_number) {
            return null;
        }

        $clean = preg_replace('/[\s\-]+/', '', (string) $this->awb_number);
        if (strlen($clean) === 11) {
            return substr($clean, 0, 3).'-'.substr($clean, 3);
        }

        return $this->awb_number;
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
                'CREATED' => 'Created',
                'BOOKED' => 'Vessel Space Booked',
                'PICKED_UP' => 'Gated in at Origin Terminal',
                'IN_TRANSIT' => 'At Sea / In Transit',
                'DELIVERED' => 'Discharged & Delivered',
            ],
            'AIR' => [
                'CREATED' => 'Created',
                'BOOKED' => 'Flight Space Booked',
                'PICKED_UP' => 'Received at Origin Airport',
                'IN_TRANSIT' => 'In Flight / In Transit',
                'DELIVERED' => 'Arrived & Delivered',
            ],
            'ROAD' => [
                'CREATED' => 'Created',
                'BOOKED' => 'Truck / Haulier Dispatched',
                'PICKED_UP' => 'Loaded at Depot / Warehouse',
                'IN_TRANSIT' => 'On Road / In Transit',
                'DELIVERED' => 'Delivered to Consignee',
            ],
        ];

        return $labels[$mode][$status] ?? ucfirst(strtolower(str_replace('_', ' ', $status)));
    }
}

<?php

namespace App\Models;

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
        'reference_number',
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
}

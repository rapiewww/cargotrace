<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'unlocode',
        'iata_code',
        'name',
        'city',
        'country',
        'country_code',
        'currency_code',
        'type',
        'latitude',
        'longitude',
        'timezone',
        'weather_snapshot',
        'news_snapshot',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'weather_snapshot' => 'array',
            'news_snapshot' => 'array',
        ];
    }

    /**
     * Shipments originating from this location.
     */
    public function originShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_location_id');
    }

    /**
     * Shipments arriving at this location.
     */
    public function destinationShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'destination_location_id');
    }

    /**
     * Shipments currently located at this location.
     */
    public function currentShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'current_location_id');
    }

    /**
     * Tracking events recorded at this location.
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class, 'location_id');
    }
}
